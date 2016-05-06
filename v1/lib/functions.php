<?php
use \Curl\Curl;
use Sabberworm\CSS\Parser as CSSParser;



function processURL($oURL) {
	$ret = [];		
	$ret['file'] = trim(strval($oURL->getURL()), '"');
	$ret['ext'] = pathinfo($ret['file'], PATHINFO_EXTENSION);

	return $ret;
}

function encodeSpacing($str) {
	return str_replace(" ", "+", $str);
}


function breakDownFamily($family) {
	$ret = [];

	$fonts = explode('|', urldecode($family));

	foreach($fonts as $font) {
		$name_style = explode(':', $font);
		
		$name = $name_style[0];
		$styles = explode(',', $name_style[1]);

		if(sizeof($name_style)>1) {
			foreach($styles as $style) {
				$ret[] = encodeSpacing($name.':'.$style);
			}
		} else {
			$ret[] = encodeSpacing($name);
		}
	}

	return $ret;
}

function fetchCombinedCss($family, $userAgentList = []) {
	$userAgentList = [
		"woff2" => "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/38.0.2125.104 Safari/537.36",
		"woff" => "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko",
		"ttf" => "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.54.16 (KHTML, like Gecko) Version/5.1.4 Safari/534.54.16"
	];

	$ret = [
		'error' => null,
		'data' => null
	];

	// Every format but EOT
	foreach($userAgentList as $type => $ua) {
		$fetchedCSS = fetchCSS($family, $ua);
		if(!is_null($fetchedCSS['error'])) {
			return $fetchCSS;
		} 
		$ret['data'] .= $fetchedCSS['data'];
	}

	// EOT needs one request per style and weight
	$ua = "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)";
	$family_breakdown = breakDownFamily($family);

	foreach($family_breakdown as $font) {
		$fetchedCSS = fetchCSS($font, $ua);
		if(!is_null($fetchedCSS['error'])) {
			return $fetchCSS;
		} 
		$ret['data'] .= $fetchedCSS['data'];
	}


	$ret['data'] = "/* fontperf API - api.fontperf.com */\n" . $ret['data'];

	return $ret;
}


function fetchCSS($family, $userAgent = '', $addComment = false) {

	$GFURL = 'https://fonts.googleapis.com/css?family=';

	$ret = [
		'error' => null,
		'data' => null
	];

	$url = $GFURL . $family;

	$curl = new Curl();
	$curl->setUserAgent($userAgent);
	$curl->get($url);

	if ($curl->error) {
		$ret['error'] = [
			"statusCode" => $curl->errorCode,
			"message" => $curl->errorMessage
		];
	} else {
		$ret['data'] = $curl->response;
		if($addComment) {
			$ret['data'] = "/* fontperf API - api.fontperf.com */\n" . $ret['data'];
		}
	}
	$curl->close();

	return $ret;
}


function processCSS($stringCSS) {
	$font_list = [];

	$oParser = new CSSParser($stringCSS);

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

		$styleID = encodeSpacing($name. ':' . $weight . ($style!="normal" ? $style : ''));

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
		$font_list[$name]['types'][$styleID]->id = $styleID;
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
		$font_list[$key] = (object) $font_list[$key];
	}

	return array_values($font_list);
}