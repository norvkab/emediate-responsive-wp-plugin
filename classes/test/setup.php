<?php

// Setup server vars expected to exist by wordpress
$_SERVER['DOCUMENT_ROOT'] = getcwd();
$_SERVER['SERVER_PROTOCOL'] = '';
$_SERVER['HTTP_HOST'] = '';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Load wp
require_once __DIR__ . '/../../../../../wp-load.php';

// Load PHPUnit from root
require_once __DIR__ . '/../../../../../vendor/autoload.php';

// Load classes
require_once __DIR__.'/../class-loader.php';