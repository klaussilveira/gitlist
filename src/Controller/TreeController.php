<?php

namespace GitList\Controller;

use Silex\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class TreeController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/tree/{commitishPath}/', $treeController = function ($repo, $commitishPath = '') use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);
            if (!$commitishPath) {
                $commitishPath = $repository->getHead();
            }

            list($branch, $tree) = $app['util.routing']->parseCommitishPathParam($commitishPath, $repo);

            list($branch, $tree) = $app['util.repository']->extractRef($repository, $branch, $tree);
            $files = $repository->getTree($tree ? "$branch:\"$tree\"/" : $branch);
            $breadcrumbs = $app['util.view']->getBreadcrumbs($tree);

            $parent = null;
            if (($slash = strrpos($tree, '/')) !== false) {
                $parent = substr($tree, 0, $slash);
            } elseif (!empty($tree)) {
                $parent = '';
            }

            return $app['twig']->render('tree.twig', array(
                'files' => $files->output(),
                'repo' => $repo,
                'branch' => $branch,
                'path' => $tree ? $tree . '/' : $tree,
                'parent' => $parent,
                'breadcrumbs' => $breadcrumbs,
                'branches' => $repository->getBranches(),
                'tags' => $repository->getTags(),
                'readme' => $app['util.repository']->getReadme($repository, $branch, $tree ? "$tree" : ''),
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
          ->convert('commitishPath', 'escaper.argument:escape')
          ->bind('tree');

        $route->post('{repo}/tree/{branch}/search', function (Request $request, $repo, $branch = '', $tree = '') use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);
            if (!$branch) {
                $branch = $repository->getHead();
            }

            $query = $request->get('query');
            $breadcrumbs = array(array('dir' => 'Search results for: ' . $query, 'path' => ''));
            $results = $repository->searchTree($query, $branch);

            return $app['twig']->render('search.twig', array(
                'results' => $results,
                'repo' => $repo,
                'branch' => $branch,
                'path' => $tree,
                'breadcrumbs' => $breadcrumbs,
                'branches' => $repository->getBranches(),
                'tags' => $repository->getTags(),
                'query' => $query,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->convert('branch', 'escaper.argument:escape')
          ->bind('search');

        $route->get('{repo}/{format}ball/{branch}', function ($repo, $format, $branch) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            $tree = $repository->getBranchTree($branch);

            if (false === $tree) {
                return $app->abort(404, 'Invalid commit or tree reference: ' . $branch);
            }

            $file = $app['cache.archives'] . DIRECTORY_SEPARATOR
                    . $repo . DIRECTORY_SEPARATOR
                    . substr($tree, 0, 2) . DIRECTORY_SEPARATOR
                    . substr($tree, 2)
                    . '.'
                    . $format;

            if (!file_exists($file)) {
                $repository->createArchive($tree, $file, $format);
            }

            /**
             * Generating name for downloading, lowercasing and removing all non
             * ascii and special characters.
             */
            $filename = strtolower($repo . '_' . $branch);
            $filename = preg_replace('#[^a-z0-9]+#', '_', $filename);
            $filename = $filename . '.' . $format;

            $response = new BinaryFileResponse($file);
            $response->setContentDisposition('attachment', $filename);

            return $response;
        })->assert('format', '(zip|tar)')
          ->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->convert('branch', 'escaper.argument:escape')
          ->bind('archive');

        $route->get('{repo}/{branch}/', function ($repo, $branch) use ($app, $treeController) {
            return $treeController($repo, $branch);
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->convert('branch', 'escaper.argument:escape')
          ->bind('branch');

        $route->get('{repo}/', function ($repo) use ($app, $treeController) {
            return $treeController($repo);
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->bind('repository');

        return $route;
    }
}
