<?php

$app->get('{repo}/', function($repo) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $defaultBranch = $repository->getHead();
    $tree = $repository->getTree($defaultBranch);

    return $app['twig']->render('tree.twig', array(
        'page'           => 'files',
        'files'          => $tree->output(),
        'repo'           => $repo,
        'branch'         => $defaultBranch,
        'path'           => '',
        'parent'         => '',
        'breadcrumbs'    => array(),
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'readme'         => $app['utils']->getReadme($repo, $defaultBranch),
    ));
})->assert('repo', '[\w-._]+')
  ->bind('repository');

$app->get('{repo}/tree/{branch}/', function($repo, $branch) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $tree = $repository->getTree($branch);

    return $app['twig']->render('tree.twig', array(
        'page'           => 'files',
        'files'          => $tree->output(),
        'repo'           => $repo,
        'branch'         => $branch,
        'path'           => '',
        'parent'         => '',
        'breadcrumbs'    => array(),
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'readme'         => $app['utils']->getReadme($repo, $branch),
    ));
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->bind('tree');

$app->get('{repo}/tree/{branch}/{tree}/', function($repo, $branch, $tree) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $files = $repository->getTree("$branch:'$tree'/");
    $breadcrumbs = $app['utils']->getBreadcrumbs($tree);

    if (($slash = strrpos($tree, '/')) !== false) {
        $parent = substr($tree, 0, $slash);
    } else {
        $parent = '';
    }

    return $app['twig']->render('tree.twig', array(
        'page'           => 'files',
        'files'          => $files->output(),
        'repo'           => $repo,
        'branch'         => $branch,
        'path'           => "$tree/",
        'parent'         => $parent,
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'readme'         => $app['utils']->getReadme($repo, $branch),
    ));
})->assert('tree', '.+')
  ->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->bind('tree_dir');
