<?php

define('GFURL', 'https://fonts.googleapis.com/css?family=');

// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com');
});

$app->get('/v1/gfonts/{fontstring}', function($req, $res){

	$url = GFURL. $req->getAttribute('fontstring');

	$curl = new Curl();
	$curl->get($url);

	echo $curl->response->data;

    return $res->withHeader('Content-type', 'application/json');
});