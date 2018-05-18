<?php

namespace GitList\Controller;

use Silex\ControllerProviderInterface;
use Silex\Application;

class TreeGraphController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $route = $app['controllers_factory'];

        $route->get(
            '{repo}/treegraph/{commitishPath}',
            function ($repo, $commitishPath) use ($app) {
                /** @var \GitList\Git\Repository $repository */
                $repository = $app['git']->getRepositoryFromName($app['git.repos'], $repo);

                $command = 'log --graph --date-order --all -C -M -n 100 --date=iso ' .
                    '--pretty=format:"B[%d] C[%H] D[%ad] A[%an] E[%ae] H[%h] S[%s]"';
                $rawRows = $repository->getClient()->run($repository, $command);
                $rawRows = explode("\n", $rawRows);
                $graphItems = array();

                foreach ($rawRows as $row) {
                    if (preg_match("/^(.+?)(\s(B\[(.*?)\])? C\[(.+?)\] D\[(.+?)\] A\[(.+?)\] E\[(.+?)\] H\[(.+?)\] S\[(.+?)\])?$/", $row, $output)) {
                        if (!isset($output[4])) {
                            $graphItems[] = array(
                                'relation' => $output[1],
                            );
                            continue;
                        }
                        $graphItems[] = array(
                            'relation' => $output[1],
                            'branch' => $output[4],
                            'rev' => $output[5],
                            'date' => $output[6],
                            'author' => $output[7],
                            'author_email' => $output[8],
                            'short_rev' => $output[9],
                            'subject' => preg_replace('/(^|\s)(#[[:xdigit:]]+)(\s|$)/', '$1<a href="$2">$2</a>$3', $output[10]),
                        );
                    }
                }

                if ($commitishPath === null) {
                    $commitishPath = $repository->getHead();
                }

                list($branch, $file) = $app['util.routing']->parseCommitishPathParam($commitishPath, $repo);
                list($branch, $file) = $app['util.repository']->extractRef($repository, $branch, $file);

                return $app['twig']->render(
                    'treegraph.twig',
                    array(
                        'repo' => $repo,
                        'branch' => $branch,
                        'commitishPath' => $commitishPath,
                        'graphItems' => $graphItems,
                    )
                );
            }
        )->assert('repo', $app['util.routing']->getRepositoryRegex())
            ->assert('commitishPath', $app['util.routing']->getCommitishPathRegex())
            ->value('commitishPath', null)
            ->convert('commitishPath', 'escaper.argument:escape')
            ->bind('treegraph');

        return $route;
    }
}
