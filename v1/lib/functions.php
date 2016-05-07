<?php
use \Curl\Curl;
use Sabberworm\CSS\Parser as CSSParser;


function fontfaceFormat($format) {
	$ret = $format;

	switch($format) {
		case 'ttf':
			$ret = "truetype";
			break;
		case 'eot':
			$ret = "embedded-opentype";
			break;
		default:
			$ret = $format;
	}

	return '"'. $ret . '"';
}

function processURL($oURL) {
	$ret = [];
	$ret['file'] = trim(strval($oURL->getURL()), '"');
	$ret['ext'] = pathinfo($ret['file'], PATHINFO_EXTENSION);

	return $ret;
}

function encodeSpacing($str) {
	return str_replace(" ", "+", $str);
}



function getMime($format) {
	$mime = 'font/woff2';
	if($format!== 'woff2') {
		switch($format) {
			case 'woff':
				$mime = 'application/font-woff';
				break;
			case 'eot':
				$mime = 'application/vnd.ms-fontobject';
				break;
			case 'ttf':
				$mime = 'application/font-sfnt';
				break;
			default:
				$mime = 'font/'.$format;
		}
	}

	return $mime;
}

function dataURI($file, $format) {
	$contents=file_get_contents($file);
	$base64=base64_encode($contents);
	return "data:".getMime($format).";base64,$base64";
}

