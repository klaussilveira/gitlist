<?php

namespace GitList;

use Silex\Application as SilexApplication;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use GitList\Provider\GitServiceProvider;
use GitList\Provider\RepositoryUtilServiceProvider;
use GitList\Provider\ViewUtilServiceProvider;
use GitList\Provider\RoutingUtilServiceProvider;

/**
 * GitList application.
 */
class Application extends SilexApplication
{
    /**
     * Constructor initialize services.
     *
     * @param Config $config
     * @param string $root   Base path of the application files (views, cache)
     */
    public function __construct(Config $config, $root = null)
    {
        parent::__construct();

        $app = $this;
        $root = realpath($root);

        $this['debug'] = $config->get('app', 'debug');
        $this['filetypes'] = $config->getSection('filetypes');
        $this['cache.archives'] = $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'archives';

        // Register services
        $this->register(new TwigServiceProvider(), array(
            'twig.path'       => $root . DIRECTORY_SEPARATOR . 'views',
            'twig.options'    => array('cache' => $root . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'views'),
        ));
        $this->register(new GitServiceProvider(), array(
            'git.client'      => $config->get('git', 'client'),
            'git.repos'       => $config->get('git', 'repositories'),
            'git.hidden'      => $config->get('git', 'hidden') ? $config->get('git', 'hidden') : array(),
        ));
        $this->register(new ViewUtilServiceProvider());
        $this->register(new RepositoryUtilServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new RoutingUtilServiceProvider());

        $this['twig'] = $this->share($this->extend('twig', function($twig, $app) {
            $twig->addFilter('htmlentities', new \Twig_Filter_Function('htmlentities'));
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
