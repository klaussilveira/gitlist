<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TreeController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/tree/{branch}/{tree}/', $treeController = function($repo, $branch = '', $tree = '') use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            if (!$branch) {
                $branch = $repository->getHead();
            }
            $files = $repository->getTree($tree ? "$branch:'$tree'/" : $branch);
            $breadcrumbs = $app['util.view']->getBreadcrumbs($tree);

            $parent = null;
            if (($slash = strrpos($tree, '/')) !== false) {
                $parent = substr($tree, 0, $slash);
            } elseif (!empty($tree)) {
                $parent = '';
            }

            $filesOutput = $files->output();
            foreach ($filesOutput as &$file)
            {
                if ($tree)
                {
                    $path = $tree . '/' . $file['name'];
                }
                else
                {
                    $path = $file['name'];
                }

                $info = $repository->getLatestCommitInfo($path);

                $parts = explode('|', $info);

                $file['commit'] = array(
                    'hash' => $parts[0],
                    'author' => $parts[1],
                    'age' => $parts[2],
                    'message' => count($parts) >= 3 ? $parts[3] : ''
                );
            }

            return $app['twig']->render('tree.twig', array(
                'files'          => $filesOutput,
                'repo'           => $repo,
                'branch'         => $branch,
                'path'           => $tree ? $tree . '/' : $tree,
                'parent'         => $parent,
                'breadcrumbs'    => $breadcrumbs,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'readme'         => $app['util.repository']->getReadme($repo, $branch),
            ));
        })->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->assert('tree', '.+')
          ->bind('tree');

        $route->get('{repo}/{branch}/', function($repo, $branch) use ($app, $treeController) {
            return $treeController($repo, $branch);
        })->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->bind('branch');

        $route->get('{repo}/', function($repo) use ($app, $treeController) {
            return $treeController($repo);
        })->assert('repo', '[\w-._]+')
          ->bind('repository');

        $route->get('{repo}/{format}ball/{branch}', function($repo, $format, $branch) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
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

            return new StreamedResponse(function () use ($file) {
                readfile($file);
            }, 200, array(
                'Content-type' => ('zip' === $format) ? 'application/zip' : 'application/x-tar',
                'Content-Description' => 'File Transfer',
                'Content-Disposition' => 'attachment; filename="'.$repo.'-'.substr($tree, 0, 6).'.'.$format.'"',
                'Content-Transfer-Encoding' => 'binary',
            ));
        })->assert('format', '(zip|tar)')
          ->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->bind('archive');

        return $route;
    }
}
