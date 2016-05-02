<?php
use \Curl\Curl;



function processURL($oURL) {
	$ret = [];		
	$ret['file'] = trim(strval($oURL->getURL()), '"');
	$ret['ext'] = pathinfo($ret['file'], PATHINFO_EXTENSION);

	return $ret;
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
				$ret[] = urlencode($name.':'.$style);
			}
		} else {
			$ret[] = urlencode($name);
		}
	}

	return $ret;
}

function fetchCombinedCss($family, $userAgentList = []) {
	$ret = [
		'error' => null,
		'data' => null
	];

	foreach($userAgentList as $type => $ua) {

		if($type == "eot") {

			$family_breakdown = breakDownFamily($family);

			foreach($family_breakdown as $font) {
				$fetchedCSS = fetchCSS($font, $ua);
				if(!is_null($fetchedCSS['error'])) {
					return $fetchCSS;
				} 
				$ret['data'] .= $fetchedCSS['data'];
			}

		} else {

			$fetchedCSS = fetchCSS($family, $ua);
			if(!is_null($fetchedCSS['error'])) {
				return $fetchCSS;
			} 
			$ret['data'] .= $fetchedCSS['data'];

		}
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