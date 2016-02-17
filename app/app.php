<?php

use Symfony\Component\Console\Application;
use Silex\Provider;
use Silex\Provider\SwiftmailerServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';
// This is the default config. See `deploy_config/README.md' for more info.

// Apply custom config if available
if (file_exists(__DIR__ . '/config.php')) {
    include __DIR__ . '/config.php';
}

// Initialize Application
$application = new Application('PHP Avenger','0.1.0');

//$application->register(new SwiftmailerServiceProvider());

//$application['swiftmailer.options'] = $configMail;

/**
 * Register controllers as services
 * @link http://silex.sensiolabs.org/doc/providers/service_controller.html
 **/
/*$app['app.default_controller'] = $app->share(
    function () use ($app) {
        return new \App\Controller\DefaultController($app['twig'], $app['logger']);
    }
);*/
// Map routes to controllers
include __DIR__ . '/routing.php';
return $application;