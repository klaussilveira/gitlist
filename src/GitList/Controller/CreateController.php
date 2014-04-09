<?php

namespace GitList\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CreateController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];
        
        $route->get('/create', function() use ($app) {
			
			if (false == $app['git.create_allow'])
			{
				return $app->redirect($app['url_generator']->generate('homepage'));
			}			

            return $app['twig']->render('create.twig', array(
				'repos' => $app['git.repos'],
            ));
        })->bind('create');
        
        $route->post('/create', function(Request $request) use ($app) {
			$reponame 		= $request->get('reponame', '');
			$repobase 		= $request->get('repobase', $app['git.repos'][0]);
			$pattern 		= '/^[a-z0-9]+$/';
			
			if (!preg_match($pattern, $reponame)) {
				$app['session']->getFlashBag()->add('error', 'Repository name must be lowercase alphanumeric.');
				return $app->redirect('create');
			}

			$base_path 		= rtrim($repobase, '/');
			$newrepo_path	= $base_path . '/' . $reponame . '.git';
			
			try
			{
				$app['git']->createRepository($newrepo_path);
				$app['session']->getFlashBag()->add('success', 'Repository Created!');
			}
			catch (\RuntimeException $e)
			{
				$app['session']->getFlashBag()->add('error', 'A repository with the same name already exists.');
			}
			
			return $app->redirect('create');
        });


        return $route;
    }
}

