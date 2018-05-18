<?php

namespace GitList\Controller;

use Silex\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class BlobController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/blob/{commitishPath}', function ($repo, $commitishPath) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            list($branch, $file) = $app['util.routing']
                ->parseCommitishPathParam($commitishPath, $repo);

            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

            $blob = $repository->getBlob("$branch:\"$file\"");
            $breadcrumbs = $app['util.view']->getBreadcrumbs($file);
            $fileType = $app['util.repository']->getFileType($file);

            if ($fileType !== 'image' && $app['util.repository']->isBinary($file)) {
                return $app->redirect($app['url_generator']->generate('blob_raw', array(
                    'repo' => $repo,
                    'commitishPath' => $commitishPath,
                )));
            }

            return $app['twig']->render('file.twig', array(
                'file' => $file,
                'fileType' => $fileType,
                'blob' => $blob->output(),
                'repo' => $repo,
                'branch' => $branch,
                'breadcrumbs' => $breadcrumbs,
                'branches' => $repository->getBranches(),
                'tags' => $repository->getTags(),
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('commitishPath', '.+')
          ->convert('commitishPath', 'escaper.argument:escape')
          ->bind('blob');

        $route->get('{repo}/raw/{commitishPath}', function ($repo, $commitishPath) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            list($branch, $file) = $app['util.routing']
                ->parseCommitishPathParam($commitishPath, $repo);

            list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

            $blob = $repository->getBlob("$branch:\"$file\"")->output();

            $headers = array();
            if ($app['util.repository']->isBinary($file)) {
                $headers['Content-Disposition'] = 'attachment; filename="' . $file . '"';
                $headers['Content-Type'] = 'application/octet-stream';
            } else {
                $headers['Content-Type'] = 'text/plain';
            }

            return new Response($blob, 200, $headers);
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
          ->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
          ->convert('commitishPath', 'escaper.argument:escape')
          ->bind('blob_raw');

        $route->get('{repo}/logpatch/{commitishPath}', function ($repo, $commitishPath) use ($app) {
            $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

            list($branch, $file) = $app['util.routing']
                ->parseCommitishPathParam($commitishPath, $repo);

            $filePatchesLog = $repository->getCommitsLogPatch($file);
            $breadcrumbs = $app['util.view']->getBreadcrumbs($file);

            return $app['twig']->render('logpatch.twig', array(
                'branch' => $branch,
                'repo' => $repo,
                'breadcrumbs' => $breadcrumbs,
                'commits' => $filePatchesLog,
            ));
        })->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('commitishPath', '.+')
            ->convert('commitishPath', 'escaper.argument:escape')
            ->bind('logpatch');

        return $route;
    }
}
