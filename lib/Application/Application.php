<?php

namespace Application;

use Silex\Application as BaseApplication;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Git\GitServiceProvider;

/**
 * GitList application class.
 */
class Application extends BaseApplication
{
    public function __construct($basepath, array $config)
    {
        parent::__construct();

        $app = $this;

        $app['debug'] = isset($config['app']['debug']) && $config['app']['debug'];
        $app['filetypes'] = $config['filetypes'];
        $app['hidden'] = isset($config['git']['hidden']) ? $config['git']['hidden'] : array();
        $config['git']['repositories'] = rtrim($config['git']['repositories'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $app['cache.archives'] = $basepath.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'archives';

        // Register Git and Twig service providersclass_path
        $app->register(new TwigServiceProvider(), array(
            'twig.path'       => $basepath.'/views',
            'twig.options'    => array('cache' => $basepath.'/cache'),
        ));
        $app->register(new GitServiceProvider(), array(
            'git.client'      => $config['git']['client'],
            'git.repos'       => $config['git']['repositories'],
        ));
        $app->register(new UrlGeneratorServiceProvider());

        $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
            // Add the md5() function to Twig scope
            $twig->addFilter('md5', new \Twig_Filter_Function('md5'));

            return $twig;
        }));

        $app['utils'] = $app->share(function () use ($app) {
            return new Utils($app);
        });

        // Error handling
        $app->error(function (\Exception $e, $code) use ($app) {
            return $app['twig']->render('error.twig', array(
                'message' => $e->getMessage(),
            ));
        });
    }
}
