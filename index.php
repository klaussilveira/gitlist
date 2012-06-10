<?php

/**
 * GitList 0.1
 * https://github.com/klaussilveira/gitlist
 */

$config = parse_ini_file('config.ini', true);

if (empty($config['git']['repositories']) || !is_dir($config['git']['repositories'])) {
    die("Please, edit the config.ini file and provide your repositories directory");
}

require_once 'phar://'.__DIR__.'/vendor/silex.phar';

$app = new Silex\Application();
$app['baseurl'] = rtrim($config['app']['baseurl'], '/');
$app['filetypes'] = $config['filetypes'];
$app['hidden'] = isset($config['git']['hidden']) ? $config['git']['hidden'] : array();
$config['git']['repositories'] = rtrim($config['git']['repositories'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

// Register Git and Twig libraries
$app['autoloader']->registerNamespace('Git', __DIR__.'/lib');
$app['autoloader']->registerNamespace('Application', __DIR__.'/lib');
$app['autoloader']->registerNamespace('Authorization', __DIR__.'/lib');
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/views',
    'twig.class_path' => __DIR__.'/vendor',
    'twig.options'    => array('cache' => __DIR__.'/cache'),
));
$app->register(new Git\GitServiceProvider(), array(
    'git.client'      => $config['git']['client'],
    'git.repos'       => $config['git']['repositories'],
));
$app->register(new Application\UtilsServiceProvider());

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Authorization\AuthorizationServiceProvider(), array(
    'auth.file'       => isset($config['authorization']['file']) ? $config['authorization']['file'] : false,
));

// Auth filter
Authorization\AuthorizationFilter::before($app);

// Add the md5() function to Twig scope
$app['twig']->addFilter('md5', new Twig_Filter_Function('md5'));

// Load controllers
include 'controllers/indexController.php';
include 'controllers/treeController.php';
include 'controllers/blobController.php';
include 'controllers/commitController.php';
include 'controllers/statsController.php';
include 'controllers/rssController.php';
include 'controllers/authorizationController.php';

// Error handling
$app->error(function (\Exception $e, $code) use ($app) {
    return $app['twig']->render('error.twig', array(
        'baseurl'   => $app['baseurl'],
        'message'   => $e->getMessage(),
    ));
});

$app->run();
