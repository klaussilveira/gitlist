<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\Repository\Commitish;
use GitList\Repository\Index;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class Commit
{
    public function __construct(protected Environment $templating, protected Index $index, protected int $perPage)
    {
    }

    public function list(Request $request, string $repository, string $commitish): Response
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', $this->perPage);

        $repository = $this->index->getRepository($repository);
        $commits = $repository->getCommits($commitish, $page, $perPage);
        $commitGroups = [];

        foreach ($commits as $commit) {
            $commitGroups[$commit->getCommitedAt()->format('Y-m-d')][] = $commit;
        }

        return new Response($this->templating->render('Commit/list.html.twig', [
            'repository' => $repository,
            'commitish' => $commitish,
            'commitGroups' => $commitGroups,
            'page' => $page,
            'nextPage' => $page + 1,
            'previousPage' => $page - 1,
            'perPage' => $perPage,
        ]));
    }

    public function show(string $repository, string $commitish): Response
    {
        $repository = $this->index->getRepository($repository);
        $commit = $repository->getCommit($commitish);

        return new Response($this->templating->render('Commit/show.html.twig', [
            'repository' => $repository,
            'commit' => $commit,
        ]));
    }

    public function feed(string $repository, string $commitish, string $format): Response
    {
        $repository = $this->index->getRepository($repository);
        $commits = $repository->getCommits($commitish, 1, $this->perPage);
        $commitish = new Commitish($repository, $commitish);

        return new Response($this->templating->render(sprintf('Commit/feed.%s.twig', $format), [
            'repository' => $repository,
            'commitish' => $commitish,
            'commits' => $commits,
        ]));
    }
}
