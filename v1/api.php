<?php

require "lib/functions.php";

// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com/docs/');
});

$app->get('/v1/gfonts/download', function($req, $res){

	// Prepare File
	$file = tempnam("tmp", "zip");
	$zip = new ZipArchive();
	$zip->open($file, ZipArchive::OVERWRITE);

	// Add CSS file
	$zip->addFromString('file_name_within_archive.ext', $your_string_data);
	// Add fonts
	$zip->addFile('file_on_server.ext', 'second_file_name_within_archive.ext');

	// Close and send to users
	$zip->close();
	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($file));
	header('Content-Disposition: attachment; filename="file.zip"');
	readfile($file);
	unlink($file); 
});


$app->get('/v1/gfonts/download/file', function($req, $res){
	$error = [];

	$fetchedCSS = fetchCombinedCss(urlencode($req->getQueryParams()['family']));

	if(!is_null($fetchedCSS['error'])) {
		echo $fetchedCSS['error']['message'];
		return $res->withStatus($fetchedCSS['error']['statusCode']);
	} 

	$font_list = processCSS($fetchedCSS['data']);

	// Only one file
	if(sizeof($font_list)>1 || sizeof($font_list[0]->types)>1) {
		$error[] = "This endpoint only downloads one file, but more than one has been passed";
	}

	// Get the formats we got
	// Format asked is in there
	$format = $req->getQueryParams()['format'];
	if(!isset($req->getQueryParams()['format']) || !isset($font_list[0]->types[0]->files->$format)) {
		$error[] = "The requested format is not available or it hasn't been specified";
	}

	if(!empty($error)) {
		echo json_encode([
			"stat" => "error",
			"error" => $error
		]);
		return $res->withStatus(500);
	}

	// Grab the file
	$file = $font_list[0]->types[0]->files->$format;
	$filename = trim($font_list[0]->types[0]->id, '+') . "." . $format;

	// Set download
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header("Content-Type: font/".$format);
	header("Content-Length: " . filesize($file));

	echo file_get_contents($file);
});


$app->get('/v1/gfonts/test', function($req, $res){

	$fetchedCSS = fetchCombinedCss(urlencode($req->getQueryParams()['family']));

	if(!is_null($fetchedCSS['error'])) {
		echo $fetchedCSS['error']['message'];
		return $res->withStatus($fetchedCSS['error']['statusCode']);
	} 

	echo $fetchedCSS['data'];
	return $res->withHeader('Content-type', 'text/css');
});

$app->get('/v1/gfonts', function($req, $res){

	$fetchedCSS = fetchCombinedCss(urlencode($req->getQueryParams()['family']));

	if(!is_null($fetchedCSS['error'])) {
		echo $fetchedCSS['error']['message'];
		return $res->withStatus($fetchedCSS['error']['statusCode']);
	} 

	$font_list = processCSS($fetchedCSS['data']);
	
	echo json_encode($font_list, JSON_PRETTY_PRINT);
    //return $res->withHeader('Content-type', 'text/css');

    return $res->withHeader('Content-type', 'application/json');
});