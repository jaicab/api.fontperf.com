<?php
use \Curl\Curl;

function processURL($oURL) {
	$ret = [];
	$ret['file'] = trim(strval($oURL->getURL()), '"');
  $ret['ext'] = pathinfo($ret['file'], PATHINFO_EXTENSION);

  // If no extension, get from MIME type
  if(empty($ret['ext'])) {
    $curl = new Curl();
    $curl->get($ret['file']);

    $mime = $curl->responseHeaders['Content-Type'];
    $curl->close();

    if( ($slash_pos = strpos($mime, '/')) !== FALSE )
     $ret['ext'] = substr($mime, $slash_pos + 1);
  }

  if($ret['ext'] == 'svg+xml') $ret['ext'] = 'svg';

  $valid_extensions = ['woff2', 'woff', 'ttf', 'eot', 'svg'];

  if(!in_array($ret['ext'], $valid_extensions)) {
    $ret['ext'] = 'unknown';
  }


	return $ret;
}

function encodeSpacing($str) {
	return str_replace(" ", "+", $str);
}

function cssComment($disclaimer = false, $lines = [], $version = '1.0') {
  $ret = "/*!\n * fontperf API v".$version." (https://fontperf.com/docs/)\n * Fonts provided by Google Fonts - https://www.google.com/fonts\n";

  if(!empty($lines)) {
    array_unshift($lines, " ");
  }

  if($disclaimer) {
    $lines[] = " ";

    if(is_string($disclaimer)) $lines[] = $disclaimer;
    else {
      $lines[] = "DISCLAIMER: DO NOT LINK THIS CSS DIRECTLY.";
      $lines[] = "THIS API IS NOT MEANT FOR HOSTING AND ANY UPDATE COULD AFFECT YOUR CODEBASE.";
    }
  }

  foreach($lines as $line) {
    $ret .= " * ". $line ."\n";
  }

  $ret .= "*/\n";

  return $ret;
}
