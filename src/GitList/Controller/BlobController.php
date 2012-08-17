<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;

class BlobController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/blob/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $blob = $repository->getBlob("$branch:\"$file\"");
            $breadcrumbs = $app['util.view']->getBreadcrumbs($file);
            $fileType = $app['util.repository']->getFileType($file);

            return $app['twig']->render('file.twig', array(
                'file'           => $file,
                'fileType'       => $fileType,
                'blob'           => $blob->output(),
                'repo'           => $repo,
                'branch'         => $branch,
                'breadcrumbs'    => $breadcrumbs,
                'branches'       => $repository->getBranches(),
                'tags'           => $repository->getTags(),
            ));
        })->assert('file', '.+')
          ->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->bind('blob');

        $route->get('{repo}/raw/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $blob = $repository->getBlob("$branch:\"$file\"")->output();

            return new Response($blob, 200, array('Content-Type' => 'text/plain'));
        })->assert('file', '.+')
          ->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->bind('blob_raw');

        return $route;
    }
}