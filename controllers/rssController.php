<?php

$app->get('{repo}/{branch}/rss/', function($repo, $branch) use($app) {
    $repository = $app['git']->getRepository($app['git.repos'] . $repo);
    $commits = $repository->getCommits($branch);

    $html = $app['twig']->render('rss.twig', array(
        'baseurl'        => $app['baseurl'],
        'repo'           => $repo,
        'branch'         => $branch,
        'commits'        => $commits,
    ));

    return new Symfony\Component\HttpFoundation\Response($html, 200, array('Content-Type' => 'application/rss+xml'));
})->assert('repo', '[\w-._]+')
  ->assert('branch', '[\w-._]+');