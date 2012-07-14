<?php

use Symfony\Component\HttpFoundation\StreamedResponse;

$app->get('{repo}/{format}ball/{branch}', function($repo, $format, $branch) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $tree = $repository->getBranchTree($branch);

    if (false === $tree) {
        return $app->abort(404, 'Invalid commit or tree reference: '.$branch);
    }

    $file = $app['cache.archives'].DIRECTORY_SEPARATOR.$repo.DIRECTORY_SEPARATOR.substr($tree, 0, 2).DIRECTORY_SEPARATOR.substr($tree, 2).'.'.$format;

    if (!file_exists($file)) {
        $repository->createArchive($tree, $file, $format);
    }

    return new StreamedResponse(function () use($file) {
        readfile($file);
    }, 200, array(
        'Content-type' => ('zip' === $format) ? 'application/zip' : 'application/x-tar',
        'Content-Description' => 'File Transfer',
        'Content-Disposition' => 'attachment; filename="'.$repo.'-'.substr($tree, 0, 6).'.'.$format.'"',
        'Content-Transfer-Encoding' => 'binary',
        'Content-Length' => filesize($file),
    ));
})->assert('format', '(zip|tar)')
  ->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->value('format', 'zip')
  ->bind('archive');
