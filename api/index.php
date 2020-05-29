<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require '../vendor/autoload.php';
require '../src/config/config.php';
require '../src/config/db.php';
require '../src/libs/authorization.php';

$app = new \Slim\App;

/* 
** Get Users Routes
*/

require "../src/routes/users.php";

$app->run();