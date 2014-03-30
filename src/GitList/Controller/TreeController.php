<?php

namespace GitList\Controller;

use Gitter\Model\Tree;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

            $this->flattenFolders($files);

            $parent = null;
            if (($slash = strrpos($tree, '/')) !== false) {
                $parent = substr($tree, 0, $slash);
            } elseif (!empty($tree)) {
                $parent = '';
            }

            return $app['twig']->render('tree.twig', array(
                'files'          => $files->output(),
                'repo'           => $repo,
                'branch'         => $branch,
                'path'           => $tree ? $tree . '/' : $tree,
                'parent'         => $parent,
                'breadcrumbs'    => $breadcrumbs,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
                'readme'         => $app['util.repository']->getReadme($repository, $branch),
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
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
                'results'        => $results,
                'repo'           => $repo,
                'branch'         => $branch,
                'path'           => $tree,
                'breadcrumbs'    => $breadcrumbs,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->bind('search');


        # Intentionally before next statement, because order appears
        # to be important, and the other statement got precedence previously.
        $route->get('{repo}/{format}ball/{branch}', function($repo, $format, $branch) use ($app) {
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

            return new StreamedResponse(function () use ($file) {
                readfile($file);
            }, 200, array(
                'Content-type' => ('zip' === $format) ? 'application/zip' : 'application/x-tar',
                'Content-Description' => 'File Transfer',
                'Content-Disposition' => 'attachment; filename="'.$repo.'-'.substr($tree, 0, 6).'.'.$format.'"',
                'Content-Transfer-Encoding' => 'binary',
            ));
        })->assert('format', '(zip|tar)')
          ->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->bind('archive');


        $route->get('{repo}/{branch}/', function($repo, $branch) use ($app, $treeController) {
            return $treeController($repo, $branch);
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', $app['util.routing']->getBranchRegex())
          ->bind('branch');

        $route->get('{repo}/', function($repo) use ($app, $treeController) {
            return $treeController($repo);
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->bind('repository');

        return $route;
    }
    
    /**
     * Flattens subdirectories of given folder.
     * 
     * @param \Gitter\Model\Tree $folder
     */
    private function flattenFolders(Tree $folder) {
        foreach ($folder as $node) {
            if ($node->isTree()) {
                // node is a subdirectory of given folder -> try to flatten
                $flattenedNode = $this->flattenFolder($node);
                $node->setName($flattenedNode->getName());
            }
        }
    }
    
    /**
     * Helper method for recursive flattening of subfolders.
     * 
     * @param \Gitter\Model\Tree $folder to flatten
     * @return \Gitter\Model\Tree flattened folder
     */
    private function flattenFolder(Tree $folder) {
        $folder->parse();
        if ($folder->valid()) {
            
            // check whether first child is a folder
            $firstChild = $folder->current();
            if (!$firstChild->isTree()) {
                return $folder;
            }
            
            // if there are any other folders/files/symlinks we cannot flatten
            $folder->next();
            if ($folder->valid()) {
                return $folder;
            }
            
            // given folder has only one subfolder -> call recursively
            $firstChild->setName($folder->getName() . DIRECTORY_SEPARATOR . $firstChild->getName());
            return $this->flattenFolder($firstChild);
        }
        
        return $folder;
    }
    
}

