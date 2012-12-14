<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class CommitController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/commits/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);

            if ($branch === null) {
                $branch = $repository->getHead();
            }

            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

            $type = $file ? "$branch -- \"$file\"" : $branch;
            $pager = $app['util.view']->getPager($app['request']->get('page'), $repository->getTotalCommits($type));
            $commits = $repository->getPaginatedCommits($type, $pager['current']);

            foreach ($commits as $commit) {
                $date = $commit->getDate();
                $date = $date->format('m/d/Y');
                $categorized[$date][] = $commit;
            }

            $template = $app['request']->isXmlHttpRequest() ? 'commits_list.twig' : 'commits.twig';

            return $app['twig']->render($template, array(
                'pager'          => $pager,
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'commits'        => $categorized,
                'file'           => $file,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', '[\w-._\/]+')
          ->assert('file', '.+')
          ->value('branch', null)
          ->value('file', '')
          ->bind('commits');

        $route->post('{repo}/commits/search', function(Request $request, $repo) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $branch = $repository->getHead();
            $commits = $repository->searchCommitLog($request->get('query'));

            foreach ($commits as $commit) {
                $date = $commit->getDate();
                $date = $date->format('m/d/Y');
                $categorized[$date][] = $commit;
            }

            return $app['twig']->render('searchcommits.twig', array(
                'repo'           => $repo,
                'branch'         => $branch,
                'file'           => '',
                'commits'        => $categorized,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->bind('searchcommits');

        $route->get('{repo}/commit/{commit}/', function($repo, $commit) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $commit = $repository->getCommit($commit);
            $branch = $repository->getHead();

            return $app['twig']->render('commit.twig', array(
                'branch'         => $branch,
                'repo'           => $repo,
                'commit'         => $commit,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('commit', '[a-f0-9^]+')
          ->bind('commit');

        $route->get('{repo}/blame/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);

            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

            $blames = $repository->getBlame("$branch -- \"$file\"");

            return $app['twig']->render('blame.twig', array(
                'file'           => $file,
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'blames'         => $blames,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('file', '.+')
          ->assert('branch', '[\w-._\/]+')
          ->bind('blame');

        return $route;
    }
}
