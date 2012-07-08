<?php

$app->get('{repo}/tree/{branch}/{tree}/', $treeController = function($repo, $branch = '', $tree = '') use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    if (!$branch) {
        $branch = $repository->getHead();
    }
    $files = $repository->getTree($tree ? "$branch:'$tree'/" : $branch);
    $breadcrumbs = $app['utils']->getBreadcrumbs($tree);

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
        'path'           => $tree ? $tree.'/' : $tree,
        'parent'         => $parent,
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'readme'         => $app['utils']->getReadme($repo, $branch),
    ));
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->assert('tree', '.+')
  ->bind('tree');

$app->get('{repo}/{branch}/', function($repo, $branch) use($app, $treeController) {
    return $treeController($repo, $branch);
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->bind('branch');

$app->get('{repo}/', function($repo) use($app, $treeController) {
    return $treeController($repo);
})->assert('repo', '[\w-._]+')
  ->bind('repository');
