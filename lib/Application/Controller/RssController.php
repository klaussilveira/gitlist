<?php

namespace Application\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class RssController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $commits = $repository->getCommits($branch);

            $html = $app['twig']->render('rss.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'commits'        => $commits,
            ));

            return new Response($html, 200, array('Content-Type' => 'application/rss+xml'));
        })->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->bind('rss');

        return $controllers;
    }
}
