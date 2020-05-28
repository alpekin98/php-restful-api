<?php

$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->load('../.env');

// Configuration for Database
define("DB_HOST", getenv('DB_HOST'));
define("DB_USER", getenv('DB_USER'));
define("DB_PASS", getenv('DB_PASS'));
define("DB_NAME", getenv('DB_NAME'));
define("SECRET", getenv('SECRET_KEY'));



