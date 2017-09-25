<?php

error_reporting(E_ALL | E_STRICT);

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

// Setup autoloading
require 'vendor/autoload.php';

require_once 'config/env_configurator.php';

$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

define('TEST_DATA_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR . 'data'));