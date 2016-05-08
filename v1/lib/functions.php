<?php
use \Curl\Curl;

function processURL($oURL) {
	$ret = [];
	$ret['file'] = trim(strval($oURL->getURL()), '"');
	$ext = pathinfo($ret['file'], PATHINFO_EXTENSION);
  $ret['ext'] = empty($ext) ? "woff2" : $ext;

	return $ret;
}

function encodeSpacing($str) {
	return str_replace(" ", "+", $str);
}

