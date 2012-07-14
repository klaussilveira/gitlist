<?php

namespace Application\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class BlobController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('{repo}/blob/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $blob = $repository->getBlob("$branch:'$file'");
            $breadcrumbs = $app['utils']->getBreadcrumbs($file);
            $fileType = $app['utils']->getFileType($file);

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

        $controllers->get('{repo}/raw/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            $blob = $repository->getBlob("$branch:'$file'")->output();

            return new Response($blob, 200, array('Content-Type' => 'text/plain'));
        })->assert('file', '.+')
          ->assert('repo', '[\w-._]+')
          ->assert('branch', '[\w-._]+')
          ->bind('blob_raw');

        return $controllers;
    }
}
