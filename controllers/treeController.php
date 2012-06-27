<?php

$app->get('{repo}/', function($repo) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $defaultBranch = $repository->getHead();
    $tree = $repository->getTree($defaultBranch);
    $breadcrumbs = $app['utils']->getBreadcrumbs("$repo/");

    return $app['twig']->render('tree.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'files',
        'files'          => $tree->output(),
        'repo'           => $repo,
        'branch'         => $defaultBranch,
        'path'           => '',
        'parent'         => '',
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'readme'         => $app['utils']->getReadme($repo, $defaultBranch),
    ));
})->assert('repo', '[\w-._]+');

$app->get('{repo}/tree/{branch}/', function($repo, $branch) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $tree = $repository->getTree($branch);
    $breadcrumbs = $app['utils']->getBreadcrumbs("$repo/");

    return $app['twig']->render('tree.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'files',
        'files'          => $tree->output(),
        'repo'           => $repo,
        'branch'         => $branch,
        'path'           => '',
        'parent'         => '',
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'readme'         => $app['utils']->getReadme($repo, $branch),
    ));
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+');

$app->get('{repo}/tree/{branch}/{tree}/', function($repo, $branch, $tree) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $files = $repository->getTree("$branch:'$tree'/");
    $breadcrumbs = $app['utils']->getBreadcrumbs("$repo/tree/$branch/$tree");

    if (($slash = strrpos($tree, '/')) !== false) {
        $parent = '/' . substr($tree, 0, $slash);
    } else {
        $parent = '/';
    }

    return $app['twig']->render('tree.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'files',
        'files'          => $files->output(),
        'repo'           => $repo,
        'branch'         => $branch,
        'path'           => "$tree/",
        'parent'         => $parent,
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
    ));
})->assert('tree', '.+')
  ->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+');
