<?php

// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com');
});

$app->get('/v1/gfonts/{fontstring}', function($req, $res){
	echo json_encode([ 'fontstring' => $req->getAttribute()['fontstring'] ]);

    return $res;
});