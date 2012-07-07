<?php

$app->get('{repo}/commits/{branch}/{file}', function($repo, $branch, $file) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $type = $file ? "$branch -- $file" : $branch;
    $pager = $app['utils']->getPager($app['request']->get('page'), $repository->getTotalCommits($type));
    $commits = $repository->getCommits($type, $pager['current']);

    foreach ($commits as $commit) {
        $date = $commit->getDate();
        $date = $date->format('m/d/Y');
        $categorized[$date][] = $commit;
    }

    return $app['twig']->render('commits.twig', array(
        'pager'          => $pager,
        'repo'           => $repo,
        'branch'         => $branch,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'commits'        => $categorized,
        'file'           => $file,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->assert('file', '.+')
  ->value('branch', 'master')
  ->value('file', '')
  ->bind('commits');

$app->get('{repo}/commit/{commit}/', function($repo, $commit) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $commit = $repository->getCommit($commit);

    return $app['twig']->render('commit.twig', array(
        'branch'         => 'master',
        'repo'           => $repo,
        'commit'         => $commit,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('commit', '[a-f0-9]+')
  ->bind('commit');

$app->get('{repo}/blame/{branch}/{file}', function($repo, $branch, $file) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $blames = $repository->getBlame("$branch -- $file");

    return $app['twig']->render('blame.twig', array(
        'file'           => $file,
        'repo'           => $repo,
        'branch'         => $branch,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'blames'         => $blames,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('file', '.+')
  ->assert('branch', '[\w-._]+')
  ->bind('blame');
