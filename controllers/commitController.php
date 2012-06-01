<?php

$app->get('{repo}/commits/{branch}', function($repo, $branch) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $pager = $app['utils']->getPager($app['request']->get('page'), $repository->getTotalCommits());
    $commits = $repository->getCommits($branch, $pager['current']);

    foreach ($commits as $commit) {
        $date = $commit->getDate();
        $date = $date->format('m/d/Y');
        $categorized[$date][] = $commit;
    }

    return $app['twig']->render('commits.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'commits',
        'pager'          => $pager,
        'repo'           => $repo,
        'branch'         => $branch,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'commits'        => $categorized,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+')
  ->value('branch', 'master');

$app->get('{repo}/commits/{branch}/{file}/', function($repo, $branch, $file) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $pager = $app['utils']->getPager($app['request']->get('page'), $repository->getTotalCommits("$branch -- $file"));
    $commits = $repository->getCommits("$branch -- $file", $pager['current']);

    foreach ($commits as $commit) {
        $date = $commit->getDate();
        $date = $date->format('m/d/Y');
        $categorized[$date][] = $commit;
    }

    return $app['twig']->render('commits.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'commits',
        'pager'          => $pager,
        'repo'           => $repo,
        'branch'         => $branch,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'commits'        => $categorized,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('file', '.+')
  ->assert('branch', '[\w-._]+');

$app->get('{repo}/commit/{commit}/', function($repo, $commit) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $commit = $repository->getCommit($commit);

    return $app['twig']->render('commit.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'commits',
        'branch'         => 'master',
        'repo'           => $repo,
        'commit'         => $commit,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('commit', '[a-f0-9]+');

$app->get('{repo}/blame/{branch}/{file}/', function($repo, $branch, $file) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $blames = $repository->getBlame("$branch -- $file");

    return $app['twig']->render('blame.twig', array(
        'baseurl'        => $app['baseurl'],
        'page'           => 'commits',
        'file'           => $file,
        'repo'           => $repo,
        'branch'         => $branch,
        'branches'       => $repository->getBranches(),
        'tags'           => $repository->getTags(),
        'blames'         => $blames,
    ));
})->assert('repo', '[\w-._]+')
  ->assert('file', '.+')
  ->assert('branch', '[\w-._]+');
