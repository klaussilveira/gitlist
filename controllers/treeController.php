<?php

$app->get('{repo}/', function($repo) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $tree = $repository->getTree('master');
    if ($filename=$repository->getReadme($tree)) {
      $blob   = $repository->getBlob("master:\'$filename\'");
      $output = $blob->output();
    } else {
      $output=null;
    }
    var_dump($blob->output());
    $breadcrumbs = $app['utils']->getBreadcrumbs("$repo/");

    return $app['twig']->render('tree.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'files',
        'files'          => $tree->output(),
        'repo'           => $repo,
        'blob'		 => $output,
        'branch'         => 'master',
        'path'           => '',
        'parent'         => '',
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
    ));
})->assert('repo', '[\w-._]+');


$app->get('{repo}/tree/{branch}/', function($repo, $branch) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $tree = $repository->getTree($branch);
    $filename = $repository->getReadme($tree);

    if ($filename != null) {
      $blob = $repository->getBlob("$branch:'$filename'");
      $output = $blob->output();
    } else {
      $output=null;
    }

    $breadcrumbs = $app['utils']->getBreadcrumbs("$repo/");
 
    return $app['twig']->render('tree.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'files',
        'files'          => $tree->output(),
        'repo'           => $repo,
        'blob'           => $output,
        'branch'         => $branch,
        'path'           => '',
        'parent'         => '',
        'breadcrumbs'    => $breadcrumbs,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
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
