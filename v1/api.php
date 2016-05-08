<?php

require "lib/functions.php";
require "lib/GFont.php";


// Generic
$app->get('/', function($req, $res){
  return $res->withStatus(301)->withHeader('Location', 'https://fontperf.com/docs/');
});



// Downloads a ZIP containing the fonts and the CSS file for self-hosting
$app->get('/v1/gfonts/download', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

  $myFonts->buildList();

  $file = $myFonts->createZip();

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($file));
	header('Content-Disposition: attachment; filename="gfonts-fontperf-'.time().'.zip"');
	readfile($file);
	unlink($file);
});



// Downloads a single font file on the specified format
$app->get('/v1/gfonts/download/font.{format}', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

  $myFonts->buildList();

  $font = $myFonts->getFont($req->getAttribute('format'));

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

	// Set download
	header('Content-Disposition: attachment; filename="'.$font['filename'].'"');
	header("Content-Type: ".$font['mime']);
	header("Content-Length: " . filesize($font['file']));

	$contents = file_get_contents($font['file']);

  if(!$contents) {
    var_dump("File could not be reached");
    die();
  }

  echo $contents;

	return $res;
});



// CSS file for self-hosted setup
$app->get('/v1/gfonts/download/style[.{format}]', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();

  echo cssComment("The fonts linked here are available as a ZIP package on /download and as separate files on /download/font.format.");
  echo strval($myFonts);

	return $res->withHeader('Content-type', 'text/css');
});



// CSS file with data-URIs for fonts
$app->get('/v1/gfonts/datauri[.css]', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();


  $format = 'woff2';
  if(isset($req->getQueryParams()['format'])) {
    $format = $req->getQueryParams()['format'];
  }

  $css = $myFonts->buildCss('datauri', $format);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

  echo cssComment(true);
  echo $css;

	return $res->withHeader('Content-type', 'text/css');
});


// CSS file with data-URIs for fonts
$app->get('/v1/gfonts/critical[.css]', function($req, $res){

  $critical_subset = isset($req->getQueryParams()['critical_subset']) ? $req->getQueryParams()['critical_subset'] : 'Aa';

  $myFonts = new GFont($req->getQueryParams()['family'], $critical_subset);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();

  $format = 'woff2';
  if(isset($req->getQueryParams()['format'])) {
    $format = $req->getQueryParams()['format'];
  }

  $css = $myFonts->buildCss('datauri', $format);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }

  echo cssComment(true, ["Encoded fonts are in '".$format."' format."]);
  echo $css;

  return $res->withHeader('Content-type', 'text/css');
});



// Collects CSS for multiple requests to GF servers
$app->get('/v1/gfonts/test[.css]', function($req, $res){

	$myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();

  echo cssComment(true);
  echo $myFonts->getCss();

	return $res->withHeader('Content-type', 'text/css');
});



// Breaks down what the API will have available
$app->get('/v1/gfonts[.json]', function($req, $res){

  $myFonts = new GFont($req->getQueryParams()['family']);

  if($myFonts->error) {
    var_dump($myFonts->errorMessage);
    die();
  }
  $myFonts->buildList();

	echo json_encode($myFonts->getList());
    return $res->withHeader('Content-type', 'application/json');
});

