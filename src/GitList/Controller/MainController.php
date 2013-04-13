<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
            $app['git']->deleteCached();

            # Go back to calling page
            return $app->redirect($request->headers->get('Referer'));
        })->bind('refresh');
        
        
        $route->post('/ajax/edit-description/{repo}', function(Request $request, $repo) use ($app ) {
            
            $repository = $app['git']->getRepository($app['git.repos'], $repo);
            $repository->saveDescription($request->get('value'));
            
            return '';
        })
        ->assert('repo', $app['util.routing']->getRepositoryRegex());



        $route->get('{repo}/stats/{branch}', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] , $repo);
            if ($branch === null) {
                $branch = $repository->getHead();
            }
            $files   = $repository->getFileStatistics($branch);
            $commits = $repository->getCommitStatistics();

            $authors = array();
            /* split commit stats in something we can easily access in the
               templates */
            foreach ( $commits['by_author'] as $author => $emails ) {
                foreach ( $emails as $email => $user_commits ) {
                    $authors[] = array(
                        'name'    => $author,
                        'email'   => $email,
                        'commits' => $user_commits['total']
                    );
                }
            }
            usort(
                $authors,
                function ($a, $b) {
                    return $a['commits'] < $b['commits'];
                }
            );
            ksort($commits['by_date']);

            return $app['twig']->render('stats.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'files'          => $files,
                'authors'        => $authors,
                'commits'        => $commits['by_date'],
                'now'            => array( date('Y'), date('m') ),
                )
            );
        })->assert('repo', $app['util.routing']->getRepositoryRegex())        
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->value('branch', null)
          ->bind('stats');

        $route->get('{repo}/{branch}/rss/', function($repo, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'], $repo);

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
          ->bind('rss');

        return $route;
    }
}

