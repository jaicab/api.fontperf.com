<?php
use Sabberworm\CSS\Parser as CSSParser;

require "lib/functions.php";


// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com/docs/');
});


$app->get('/v1/gfonts/test', function($req, $res){

	$userAgentList = [
		"eot" => "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)"
	];

	$fetchedCSS = fetchCombinedCss(urlencode($req->getQueryParams()['family']), $userAgentList);

	if(!is_null($fetchedCSS['error'])) {
		echo $fetchedCSS['error']['message'];
		return $res->withStatus($fetchedCSS['error']['statusCode']);
	} 

	echo $fetchedCSS['data'];
	return $res->withHeader('Content-type', 'text/css');
});

$app->get('/v1/gfonts', function($req, $res){

	$userAgentList = [
		"woff2" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36",
		"woff" => "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko",
		"eot" => "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)",
		"ttf" => ""
	];

	$fetchedCSS = fetchCombinedCss(urlencode($req->getQueryParams()['family']), $userAgentList);

	if(!is_null($fetchedCSS['error'])) {
		echo $fetchedCSS['error']['message'];
		return $res->withStatus($fetchedCSS['error']['statusCode']);
	} 

	$font_list = [];

	$oParser = new CSSParser($fetchedCSS['data']);

	$oCss = $oParser->parse();
	$oCssContents = $oCss->getContents();

	foreach($oCssContents as $font) {
		$fontRuleList = $font->getRules();
		$name = null;
		$weight = 400;
		$style = 'normal';
		$src = null;
		
		// Get name
		foreach($fontRuleList as $fontRule) {
			if($fontRule->getRule() == "font-family") {
				$name = trim(strval($fontRule->getValue()), '"');
			}
			if($fontRule->getRule() == "font-weight") {
				$weight = intval(strval($fontRule->getValue()));
			}
			if($fontRule->getRule() == "font-style") {
				$style = strval($fontRule->getValue());
			}
			if($fontRule->getRule() == "src") {
				$src = $fontRule->getValue();
			}
		}

		$styleID = $style . $weight;

		// Ignore if no name
		if(is_null($name) || is_null($src))
			break;

		// Add family to font list
		if(!isset($font_list[$name])) {
			$font_list[$name] = [];
			$font_list[$name]['family'] = $name;
			$font_list[$name]['types'] = [];
		}

		// Add font variation
		if(!isset($font_list[$name]['types'][$styleID])) {
			$font_list[$name]['types'][$styleID] = new stdClass();
		}
		$font_list[$name]['types'][$styleID]->weight = $weight;
		$font_list[$name]['types'][$styleID]->style = $style;


		// Files
		if(!isset($font_list[$name]['types'][$styleID]->files)) {
			$font_list[$name]['types'][$styleID]->files = new stdClass();
		}
		// Break down src
		if($src instanceof Sabberworm\CSS\Value\URL) {
			$details = processURL($src);
			$font_list[$name]['types'][$styleID]->files->$details['ext'] = $details['file'];
		
		} elseif($src instanceof Sabberworm\CSS\Value\RuleValueList) {
		
			$components = $src->getListComponents();
			if($components[0] instanceof Sabberworm\CSS\Value\RuleValueList) {
				$components = $components[0]->getListComponents();
			}

			foreach($components as $bit) {
				if($bit instanceof Sabberworm\CSS\Value\URL) {
					$details = processURL($bit);
					$font_list[$name]['types'][$styleID]->files->$details['ext'] = $details['file'];
					break;
				}
			}
		} else {
			var_dump($src);
			die();
		}
	}

	// Make list of styles an unordered array
	foreach($font_list as $key => $family) {
		$font_list[$key]['types'] = array_values($family['types']);
	}

	$font_list = array_values($font_list);
	
	echo json_encode($font_list, JSON_PRETTY_PRINT);
    //return $res->withHeader('Content-type', 'text/css');

    return $res->withHeader('Content-type', 'application/json');
});