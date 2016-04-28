<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  // return only the headers and not the content
  // only allow CORS if we're doing a GET - i.e. no saving for now.
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'GET') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: X-Requested-With');
  }
  exit;
}

$app = new \Slim\App([
    'settings'  => [
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true,
    ]
]);

require 'v1/api.php';

$app->run();