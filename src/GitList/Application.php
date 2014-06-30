<?php

namespace GitList;

use Silex\Application as SilexApplication;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use GitList\Provider\GitServiceProvider;
use GitList\Provider\RepositoryUtilServiceProvider;
use GitList\Provider\ViewUtilServiceProvider;
use GitList\Provider\RoutingUtilServiceProvider;
use Symfony\Component\Filesystem\Filesystem;

/**
 * GitList application.
 */
class Application extends SilexApplication
{
    protected $path;

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
        $this->path = realpath($root);

        $this['debug'] = $config->get('app', 'debug');
        $this['date.format'] = $config->get('date', 'format') ? $config->get('date', 'format') : 'd/m/Y H:i:s';
        $this['theme'] = $config->get('app', 'theme') ? $config->get('app', 'theme') : 'default';
        $this['filetypes'] = $config->getSection('filetypes');
        $this['binary_filetypes'] = $config->getSection('binary_filetypes');
        $this['cache.archives'] = $this->getCachePath() . 'archives';

        // Register services
        $this->register(new TwigServiceProvider(), array(
            'twig.path'       => array($this->getThemePath($this['theme']), $this->getThemePath('default')),
            'twig.options'    => $config->get('app', 'cache') ?
                                 array('cache' => $this->getCachePath() . 'views') : array(),
        ));

        $repositories = $config->get('git', 'repositories');

        $this->register(new GitServiceProvider(), array(
            'git.client'         => $config->get('git', 'client'),
            'git.repos'          => $repositories,
            'ini.file'           => "config.ini",
            'git.hidden'         => $config->get('git', 'hidden') ?
                                    $config->get('git', 'hidden') : array(),
            'git.default_branch' => $config->get('git', 'default_branch') ?
                                    $config->get('git', 'default_branch') : 'master',
        ));

        $this->register(new ViewUtilServiceProvider());
        $this->register(new RepositoryUtilServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new RoutingUtilServiceProvider());

        $this['twig'] = $this->share($this->extend('twig', function ($twig, $app) {
            $twig->addFilter(new \Twig_SimpleFilter('htmlentities', 'htmlentities'));
            $twig->addFilter(new \Twig_SimpleFilter('md5', 'md5'));
            $twig->addFilter(new \Twig_SimpleFilter('format_date', array($app, 'formatDate')));

            return $twig;
        }));

        $this['escaper.argument'] = $this->share(function() {
            return new Escaper\ArgumentEscaper();
        });

        // Handle errors
        $this->error(function (\Exception $e, $code) use ($app) {
            if ($app['debug']) {
                return;
            }

            return $app['twig']->render('error.twig', array(
                'message' => $e->getMessage(),
            ));
        });

        $this->finish(function () use ($app, $config) {
            if (!$config->get('app', 'cache')) {
                $fs = new Filesystem();
                $fs->remove($app['cache.archives']);
            }
        });
    }

    public function formatDate($date)
    {
        return $date->format($this['date.format']);
    }

    public function getPath()
    {
        return $this->path . DIRECTORY_SEPARATOR;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getCachePath()
    {
        return $this->path
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR;
    }

    public function getThemePath($theme)
    {
        return $this->path
            . DIRECTORY_SEPARATOR
            . 'themes'
            . DIRECTORY_SEPARATOR
            . $theme
            . DIRECTORY_SEPARATOR
            . 'twig'
            . DIRECTORY_SEPARATOR;
    }
}
