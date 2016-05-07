<?php
use \Curl\Curl;

function processURL($oURL) {
	$ret = [];
	$ret['file'] = trim(strval($oURL->getURL()), '"');
	$ret['ext'] = pathinfo($ret['file'], PATHINFO_EXTENSION);

	return $ret;
}

function encodeSpacing($str) {
	return str_replace(" ", "+", $str);
}

