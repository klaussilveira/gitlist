<?php

namespace GitList\Controller;

use GitList\Git\Repository;
use Gitter\Model\Commit\Commit;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class NetworkController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get('{repo}/network/{commitishPath}/{page}.json',
            function ($repo, $commitishPath, $page) use ($app) {
                /** @var $repository Repository */
                $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

                if ($commitishPath === null) {
                    $commitishPath = $repository->getHead();
                }

                $pager = $app['util.view']->getPager($page, $repository->getTotalCommits($commitishPath));
                $commits = $repository->getPaginatedCommits($commitishPath, $pager['current']);

                $jsonFormattedCommits = array();

                foreach ($commits as $commit) {
                    $detailsUrl = $app['url_generator']->generate(
                        'commit',
                        array(
                            'repo' => $repo,
                            'commit' => $commit->getHash()
                        )
                    );

                    $jsonFormattedCommits[$commit->getHash()] = array(
                        'hash' => $commit->getHash(),
                        'parentsHash' => $commit->getParentsHash(),
                        'date' => $commit->getDate()->format('U'),
                        'message' => htmlentities($commit->getMessage()),
                        'details' => $detailsUrl,
                        'author' => array(
                            'name' => $commit->getAuthor()->getName(),
                            'email' => $commit->getAuthor()->getEmail(),
                            // due to the lack of a inbuilt javascript md5 mechanism, build the full avatar url on the php side
                            'image' => 'http://gravatar.com/avatar/' . md5(
                                strtolower($commit->getAuthor()->getEmail())
                            ) . '?s=40'
                        )
                    );
                }

                $nextPageUrl = null;

                if ($pager['last'] !== $pager['current']) {
                    $nextPageUrl = $app['url_generator']->generate(
                        'networkData',
                        array(
                            'repo' => $repo,
                            'commitishPath' => $commitishPath,
                            'page' => $pager['next']
                        )
                    );
                }

				// when no commits are given, return an empty response - issue #369
				if( count($commits) === 0 ) {
					return $app->json( array(
						'repo' => $repo,
						'commitishPath' => $commitishPath,
						'nextPage' => null,
						'start' => null,
						'commits' => $jsonFormattedCommits
						), 200
					);
				}

                return $app->json( array(
                    'repo' => $repo,
                    'commitishPath' => $commitishPath,
                    'nextPage' => $nextPageUrl,
                    'start' => $commits[0]->getHash(),
                    'commits' => $jsonFormattedCommits
                    ), 200
                );
            }
        )->assert('repo', $app['util.routing']->getRepositoryRegex())
        ->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
        ->value('commitishPath', null)
        ->convert('commitishPath', 'escaper.argument:escape')
        ->assert('page', '\d+')
        ->value('page', '0')
        ->bind('networkData');

        $route->get(
            '{repo}/network/{commitishPath}',
            function ($repo, $commitishPath) use ($app) {
                $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

                if ($commitishPath === null) {
                    $commitishPath = $repository->getHead();
                }

                list($branch, $file) = $app['util.routing']->parseCommitishPathParam($commitishPath, $repo);
                list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

                return $app['twig']->render(
                    'network.twig',
                    array(
                        'repo' => $repo,
                        'branch' => $branch,
                        'commitishPath' => $commitishPath,
                    )
                );
            }
        )->assert('repo', $app['util.routing']->getRepositoryRegex())
        ->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
        ->value('commitishPath', null)
        ->convert('commitishPath', 'escaper.argument:escape')
        ->bind('network');

        return $route;
    }
}
