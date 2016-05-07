<?php

require "lib/functions.php";
require "lib/GFont.php";

// Generic
$app->get('/', function($req, $res){
    return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com/docs/');
});



// Downloads a ZIP containing the fonts and the CSS file for self-hosting
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



// Downloads a single font file on the specified format
$app->get('/v1/gfonts/download/font', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

  $myFonts->buildList();

	// Only one file
	if($myFonts->count > 1) {
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

	return $res;
});



// CSS file for self-hosted setup
$app->get('/v1/gfonts/download/css', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();
  echo strval($myFonts);

	return $res->withHeader('Content-type', 'text/css');
});



// CSS file with data-URIs for fonts
$app->get('/v1/gfonts/download/datauri', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();
  echo $myFonts->buildCss('datauri');

	return $res->withHeader('Content-type', 'text/css');
});




// Collects CSS for multiple requests to GF servers
$app->get('/v1/gfonts/test', function($req, $res){

	$myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();
  echo $myFonts->getCss();

	return $res->withHeader('Content-type', 'text/css');
});



// Breaks down what the API will have available
$app->get('/v1/gfonts', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();

	echo json_encode($myFonts->getList(), JSON_PRETTY_PRINT);
    return $res->withHeader('Content-type', 'application/json');
});


