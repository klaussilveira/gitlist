<?php

/**
 * GitList 0.3
 * https://github.com/klaussilveira/gitlist
 */

require 'vendor/autoload.php';

// Load configuration
$config = new GitList\Config('config.ini');
$config->set('git', 'repositories', rtrim($config->get('git', 'repositories'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

// Startup and configure Silex application
$app = new Silex\Application();
$app['debug'] = $config->get('app', 'debug');
$app['filetypes'] = $config->getSection('filetypes');
$app['cache.archives'] = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'archives';

// Register services
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__ . '/views',
    'twig.options'    => array('cache' => __DIR__ . '/cache'),
));
$app->register(new GitList\Provider\GitServiceProvider(), array(
    'git.client'      => $config->get('git', 'client'),
    'git.repos'       => $config->get('git', 'repositories'),
    'git.hidden'      => $config->get('git', 'hidden') ? $config->get('git', 'hidden') : array(),
));
$app->register(new GitList\Provider\ViewUtilServiceProvider());
$app->register(new GitList\Provider\RepositoryUtilServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter('md5', new Twig_Filter_Function('md5'));
    return $twig;
}));

// Mount the controllers
$app->mount('', new GitList\Controller\MainController());
$app->mount('', new GitList\Controller\BlobController());
$app->mount('', new GitList\Controller\CommitController());
$app->mount('', new GitList\Controller\TreeController());

// Handle errors
$app->error(function (\Exception $e, $code) use ($app) {
    return $app['twig']->render('error.twig', array(
        'message' => $e->getMessage(),
    ));
});

$app->run();
