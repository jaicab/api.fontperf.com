<?php
use \Curl\Curl;

error_reporting(E_ALL);
ini_set('display_errors', 'on');
define('GFURL', 'https://fonts.googleapis.com/css?family=');

// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com');
});

$app->get('/v1/gfonts/{fontstring}', function($req, $res){


	$url = GFURL . $req->getAttribute('fontstring');
	/*$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	$data = curl_exec($curl);
	curl_close($curl);*/

	$curl = new Curl();
	$curl->get($url);

	if ($curl->error) {
	    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
	}
	else {
	    echo $curl->response;
	    return $res->withHeader('Content-type', 'text/css');
	}

    return $res;//->withHeader('Content-type', 'application/json');
});