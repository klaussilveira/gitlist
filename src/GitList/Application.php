<?php

namespace GitList;

use Silex\Application as SilexApplication;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use GitList\Provider\GitServiceProvider;
use GitList\Provider\RepositoryUtilServiceProvider;
use GitList\Provider\ViewUtilServiceProvider;

/**
 * GitList application.
 */
class Application extends SilexApplication
{
    /**
     * Constructor initialize services.
     *
     * @param string $configFile The config file to load.
     * @param string $root       Base path of the application files (views, cache)
     */
    public function __construct($configFile, $root = null)
    {
        parent::__construct();

        $app = $this;

        if (null == $root) {
            $root = __DIR__ . "/../..";
        }
        $root = realpath($root);

        if (!file_exists($configFile)) {
            throw new \RuntimeException(sprintf('Can not find config file: "%s"', $configFile));
        }
        require $configFile;

        $this['cache.archives'] = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'archives';

        // Register services
        $this->register(new TwigServiceProvider(), array(
            'twig.path'    => $root . DIRECTORY_SEPARATOR . 'views',
            'twig.options' => array('cache' => $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'views'),
        ));

        $this->register(new GitServiceProvider());
        $this->register(new ViewUtilServiceProvider());
        $this->register(new RepositoryUtilServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());

        $this['twig'] = $this->share($this->extend('twig', function($twig, $app) {
            $twig->addFilter('md5', new \Twig_Filter_Function('md5'));

            return $twig;
        }));

        // Handle errors
        $this->error(function (\Exception $e, $code) use ($app) {
            if ($app['debug']) {
                return;
            }
            return $app['twig']->render('error.twig', array(
                'message' => $e->getMessage(),
            ));
        });
    }
}
