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

		$route->get('{repo}/network/{branch}/{page}.json', function($repo, $branch, $page) use ($app) {
			/** @var $repository Repository */
			$repository = $app['git']->getRepository($app['git.repos'] . $repo);
			if ($branch === null) {
				$branch = $repository->getHead();
			}

			$pager = $app['util.view']->getPager($page, $repository->getTotalCommits($branch));
			$commits = $repository->getPaginatedCommits($branch, $pager['current']);

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
																					 'branch' => $branch,
																					 'page' => $pager['next']));
			}

			return $app->json(array(
				'repo'           => $repo,
				'branch'         => $branch,
				'nextPage'		 => $nextPageUrl,
				'start'			 => $commits[0]->getHash(),
				'commits'		 => $jsonFormattedCommits
			), 200);

		})->assert('repo', $app['util.routing']->getRepositoryRegex())
			->assert('branch', $app['util.routing']->getBranchRegex())
			->value('branch', null)
			->assert('page', '\d+')
			->value('page', '0')
			->bind('networkData');


		$route->get('{repo}/network/{branch}', function($repo, $branch) use ($app) {
			$repository = $app['git']->getRepository($app['git.repos'] . $repo);
			if ($branch === null) {
				$branch = $repository->getHead();
			}

			return $app['twig']->render('network.twig', array(
				'repo'           => $repo,
				'branch'         => $branch,
			));
		})->assert('repo', $app['util.routing']->getRepositoryRegex())
			->assert('branch', $app['util.routing']->getBranchRegex())
			->value('branch', null)
			->bind('network');

        return $route;
    }
}
