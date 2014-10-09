<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class MainController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('/', function() use ($app) {
            $repositories = $app['git']->getRepositories($app['git.repos']);

            return $app['twig']->render('index.twig', array(
                'repositories'   => $repositories,
            ));
        })->bind('homepage');


        $route->get('/refresh', function(Request $request) use ($app ) {
            # Go back to calling page
            return $app->redirect($request->headers->get('Referer'));
        })->bind('refresh');

        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $stats = $repository->getStatistics($branch);
            $authors = $repository->getAuthorStatistics($branch);

            return $app['twig']->render('stats.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'stats'          => $stats,
                'authors'        => $authors,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->convert('branch', 'escaper.argument:escape')
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            $commits = $repository->getPaginatedCommits($branch);

            $html = $app['twig']->render('rss.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'commits'        => $commits,
            ));

            return new Response($html, 200, array('Content-Type' => 'application/rss+xml'));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->convert('branch', 'escaper.argument:escape')
          ->bind('rss');

        $route->post('/create', function (Request $request) use ($app) {
            $name = $request->get('name');
            $errors = $app['validator']->validateValue(
                $name,
                new Assert\Regex(array(
                    'pattern' => '([^\w.@\:/-~]+)',
                    'match' => false,
                    'message' => 'Repository name invalid'
                ))
            );
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }
            $app['git']->createRepository($app['git.default_repo'] . $name, true);
            return $app->redirect('/');
        })->assert('name', '([\w.@\:/-~]+)(.git)?');

        $route->post('/fork', function (Request $request) use ($app) {
            $name = $request->get('name');
            $errors = $app['validator']->validateValue(
                $name,
                new Assert\Regex(array(
                    'pattern' => '([^\w.@\:/-~]+)',
                    'match' => false,
                    'message' => 'Repository name invalid'
                ))
            );
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }
            $url = $request->get('url');
            $errors = $app['validator']->validateValue($url, new Assert\Url());
            if (count($errors) > 0) {
                throw new \InvalidArgumentException((string) $errors);
            }
            $app['git']->forkRepository($app['git.default_repo'] . $name, $url, true);
            return $app->redirect('/' . $name);
        });

        return $route;
    }
}
