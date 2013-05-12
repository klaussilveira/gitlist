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

		$route->get('{repo}/network/{commitishPath}/{page}.json', function($repo, $commitishPath, $page) use ($app) {
			/** @var $repository Repository */
			$repository = $app['git']->getRepository($app['git.repos'], $repo);

			if ($commitishPath === null) {
				$commitishPath = $repository->getHead();
			}

			list($branch, $file) = $app['util.routing']
				->parseCommitishPathParam($commitishPath, $repo);

			list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

			$pager = $app['util.view']->getPager($page, $repository->getTotalCommits($commitishPath));
			$commits = $repository->getPaginatedCommits($commitishPath, $pager['current']);

			// format the commits for the json reponse
			$jsonFormattedCommits = array();
			foreach( $commits as $commit ) {
				/** @var $commit Commit */
				$jsonFormattedCommits[$commit->getHash()] = array(
					'hash' => $commit->getHash(),
					'parentsHash' => $commit->getParentsHash(),
					'date' => $commit->getDate()->format('U'),
					'message' => htmlentities( $commit->getMessage() ),
					'author' => array(
						'name' => $commit->getAuthor()->getName(),
						'email' => $commit->getAuthor()->getEmail()
					)
				);
			}

			$nextPageUrl = null;
			if ( $pager['last'] !== $pager['current'] ) {
				$nextPageUrl = $app['url_generator']->generate('networkData', array( 'repo' => $repo,
																					 'commitishPath' => $commitishPath,
																					 'page' => $pager['next']));
			}

			return $app->json(array(
				'repo'           => $repo,
				'commitishPath'         => $commitishPath,
				'nextPage'		 => $nextPageUrl,
				'start'			 => $commits[0]->getHash(),
				'commits'		 => $jsonFormattedCommits
			), 200);

		})->assert('repo', $app['util.routing']->getRepositoryRegex())
			->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
			->value('commitishPath', null)
			->assert('page', '\d+')
			->value('page', '0')
			->bind('networkData');


		$route->get('{repo}/network/{commitishPath}', function($repo, $commitishPath) use ($app) {
			$repository = $app['git']->getRepository($app['git.repos'], $repo);

			if ($commitishPath === null) {
				$commitishPath = $repository->getHead();
			}

			list($branch, $file) = $app['util.routing']
				->parseCommitishPathParam($commitishPath, $repo);

			list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

			return $app['twig']->render('network.twig', array(
				'repo'           => $repo,
				'branch'		=> $branch,
				'commitishPath'         => $commitishPath,
			));
		})->assert('repo', $app['util.routing']->getRepositoryRegex())
			->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
			->value('commitishPath', null)
			->bind('network');

        return $route;
    }
}
