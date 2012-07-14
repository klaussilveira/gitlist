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
    
    $filesOutput = $files->output();
    foreach ($filesOutput as &$file)
    {
        if ($tree)
        {
            $path = $tree . '/' . $file['name'];
        }
        else
        {
            $path = $file['name'];
        }
        
        $info = $repository->getLatestCommitInfo($path);
        
        $parts = explode('|', $info);
        
        $file['commit'] = array(
            'hash' => $parts[0],
            'author' => $parts[1],
            'age' => $parts[2],
            'message' => count($parts) >= 3 ? $parts[3] : ''
        );
    }

    return $app['twig']->render('tree.twig', array(
        'files'          => $filesOutput,
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
