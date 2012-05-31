<?php

$app->get('{repo}/blob/{branch}/{file}/', function($repo, $branch, $file) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $blob = $repository->getBlob("$branch:'$file'");
    $breadcrumbs = $app['utils']->getBreadcrumbs("$repo/tree/$branch/$file");
    $fileType = $app['utils']->getFileType($file);

    return $app['twig']->render('file.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'files',
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
  ->assert('branch', '[\w-._]+');

$app->get('{repo}/raw/{branch}/{file}', function($repo, $branch, $file) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $blob = $repository->getBlob("$branch:'$file'")->output();

    return new Symfony\Component\HttpFoundation\Response($blob, 200, array('Content-Type' => 'text/plain'));
})->assert('file', '.+')
  ->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+');
