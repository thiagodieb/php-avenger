<?php

use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

// Apply custom config if available
if (file_exists(__DIR__ . '/config.php')) {
    include __DIR__ . '/config.php';
}

// Initialize Application
$application = new Application('PHP Avenger','0.1.0');

// Map routes to controllers
include __DIR__ . '/routing.php';

return $application;