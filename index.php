<?php

/**
 * GitList 0.3
 * https://github.com/klaussilveira/gitlist
 */

if (!file_exists('config.ini')) {
    die("Please, create the config.ini file.");
}

$config = parse_ini_file('config.ini', true);

if (empty($config['git']['repositories']) || !is_dir($config['git']['repositories'])) {
    die("Please, edit the config.ini file and provide your repositories directory");
}

require 'vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = isset($config['app']['debug']) && $config['app']['debug'];
$app['filetypes'] = $config['filetypes'];
$app['hidden'] = isset($config['git']['hidden']) ? $config['git']['hidden'] : array();
$config['git']['repositories'] = rtrim($config['git']['repositories'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

$app['cache.archives'] = __DIR__.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'archives';

// Register Git and Twig service providersclass_path
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/views',
    'twig.options'    => array('cache' => __DIR__.'/cache'),
));
$app->register(new GitList\Provider\GitServiceProvider(), array(
    'git.client'      => $config['git']['client'],
    'git.repos'       => $config['git']['repositories'],
));
$app->register(new GitList\Provider\ViewUtilServiceProvider());
$app->register(new GitList\Provider\RepositoryUtilServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFilter('md5', new Twig_Filter_Function('md5'));
    return $twig;
}));

$app->mount('', new GitList\Controller\MainController());
$app->mount('', new GitList\Controller\BlobController());
$app->mount('', new GitList\Controller\CommitController());
$app->mount('', new GitList\Controller\TreeController());

// Error handling
$app->error(function (\Exception $e, $code) use ($app) {
    return $app['twig']->render('error.twig', array(
        'message' => $e->getMessage(),
    ));
});

$app->run();
