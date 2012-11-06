<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;

class BlobController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/blob/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

            $blob = $repository->getBlob("$branch:\"$file\"");
            $breadcrumbs = $app['util.view']->getBreadcrumbs($file);
            $fileType = $app['util.repository']->getFileType($file);

            if ($fileType !== 'image' && $app['util.repository']->isBinary($file)) {
                return $app->redirect($app['url_generator']->generate('blob_raw', array(
                    'repo'   => $repo,
                    'branch' => $branch,
                    'file'   => $file,
                )));
            }

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
          ->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', '[\w-._\/]+')
          ->bind('blob');

        $route->get('{repo}/raw/{branch}/{file}', function($repo, $branch, $file) use ($app) {
            $repository = $app['git']->getRepository($app['git.repos'] . $repo);
            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);
            $blob = $repository->getBlob("$branch:\"$file\"")->output();

            $headers = array();
            if ($app['util.repository']->isBinary($file)) {
                $headers['Content-Disposition'] = 'attachment; filename="' .  $file . '"';
                $headers['Content-Transfer-Encoding'] = 'application/octet-stream';
                $headers['Content-Transfer-Encoding'] = 'binary';
            } else {
                $headers['Content-Transfer-Encoding'] = 'text/plain';
            }

            return new Response($blob, 200, $headers);
        })->assert('file', '.+')
          ->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('branch', '[\w-._\/]+')
          ->bind('blob_raw');

        return $route;
    }
}
