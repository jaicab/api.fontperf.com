<?php
use \Curl\Curl;
use Sabberworm\CSS\Parser as CSSParser;

define('GFURL', 'https://fonts.googleapis.com/css?family=');

// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com');
});


// Fix OPTIONS on CORS
// return HTTP 200 for HTTP OPTIONS requests
$app->options('/(:x+)', function($req, $res) {
    return $res;
});

$app->get('/v1/gfonts/test/{family}', function($req, $res){
	$url = GFURL . $req->getAttribute('family');

	$curl = new Curl();
	$curl->setUserAgent('Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)');
	$curl->get($url);

	if ($curl->error) {
		echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
	    return $res->withStatus($curl->errorCode);
	} else {
		echo $curl->response;
	}
	$curl->close();

	return $res->withHeader('Content-type', 'text/css');
});

$app->get('/v1/gfonts/{family}', function($req, $res){

	$url = GFURL . $req->getAttribute('family');

	$curl = new Curl();
	$curl->setUserAgent('Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)');
	$curl->get($url);
	$font_list = [];


	if ($curl->error) {
	    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
	    return $res->withStatus($curl->errorCode);
	} else {
		$oParser = new CSSParser($curl->response);
		$curl->close();

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
					$name = strval($fontRule->getValue());
				}
				if($fontRule->getRule() == "font-weight") {
					$weight = intval(strval($fontRule->getValue()));
				}
				if($fontRule->getRule() == "font-style") {
					$style = strval($fontRule->getValue());
				}
				if($fontRule->getRule() == "src") {
					$src = $fontRule->getValue();
					// EOT requests don't have local("Font")
					//$src = $fontRule->getValue()->getListComponents();
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
			if(!isset($font_list[$name]['types'][$styleID])) $font_list[$name]['types'][$styleID] = new stdClass();
			$font_list[$name]['types'][$styleID]->weight = $weight;
			$font_list[$name]['types'][$styleID]->style = $style;

			// Break down src
			foreach($src as $bit) {
				//var_dump($bit->getListComponents());
				//die();
			}




		}

		// Make list of styles an unordered array
		foreach($font_list as $key => $family) {
			$font_list[$key]['types'] = array_values($family['types']);
		}

		$font_list = array_values($font_list);
		
		echo json_encode($font_list);
	    //return $res->withHeader('Content-type', 'text/css');
	}

    return $res->withHeader('Content-type', 'application/json');
});