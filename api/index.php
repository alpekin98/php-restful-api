<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require '../vendor/autoload.php';
require '../src/config/config.php';
require '../src/config/db.php';

$app = new \Slim\App;

/* 
** Get Users Routes
*/

require "../src/routes/users.php";

/* 
** Get Pairs Routes
*/

require "../src/routes/pairs.php";

$app->run();