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

        $route->get('{repo}/commits/{commitish_path}', function($repo, $commitish_path) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);

            if ($commitish_path === null) {
                $commitish_path = $repository->getHead();
            }

            list($branch, $file) = $app['util.routing']
                ->parseCommitishPathParam($commitish_path, $repo);

            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

            $type = $file ? "$branch -- \"$file\"" : $branch;
            $pager = $app['util.view']->getPager($app['request']->get('page'), $repository->getTotalCommits($type));
            $commits = $repository->getPaginatedCommits($type, $pager['current']);
            $categorized = array();

            foreach ($commits as $commit) {
                $date = $commit->getDate();
                $date = $date->format('m/d/Y');
                $categorized[$date][] = $commit;
            }

            $template = $app['request']->isXmlHttpRequest() ? 'commits_list.twig' : 'commits.twig';

            return $app['twig']->render($template, array(
                'page'           => 'commits',
                'pager'          => $pager,
                'repo'           => $repo,
                'branch'         => $branch,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'commits'        => $categorized,
                'file'           => $file,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('commitish_path', $app['util.routing']->getCommitishPathRegex())
          ->value('commitish_path', null)
          ->bind('commits');

        $route->post('{repo}/commits/{branch}/search', function(Request $request, $repo, $branch = '') use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $query = $request->get('query');
            $commits = $repository->searchCommitLog($query);
            $categorized = array();

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
                'query'          => $query
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->bind('searchcommits');

        $route->get('{repo}/commit/{commit}', function($repo, $commit) use ($app) {
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

        $route->get('{repo}/blame/{branch_file}', function($repo, $branch_file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);

            list($branch, $file) = $app['util.routing']
                ->parseCommitishPathParam($branch_file, $repo);

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
          ->assert('branch_file', $app['util.routing']->getCommitishPathRegex())
          ->bind('blame');

        return $route;
    }
}
