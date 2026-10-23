<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\Repository\Graph as Layout;
use GitList\Repository\Index;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class Graph
{
    public const PER_PAGE = 50;
    public const COMPARE_LIMIT = 100;

    public function __construct(protected Environment $templating, protected Index $index, protected RouterInterface $router)
    {
    }

    public function show(Request $request, string $repository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $repository = $this->index->getRepository($repository);

        $commits = array_values($repository->getGraph($page * self::PER_PAGE + 1));
        $hasMore = count($commits) > $page * self::PER_PAGE;
        $layout = new Layout(array_slice($commits, 0, $page * self::PER_PAGE));
        $rows = array_slice($layout->getRows(), ($page - 1) * self::PER_PAGE);

        $refs = [];

        foreach ($repository->getRefs() as $ref) {
            $refs[$ref['hash']][] = $ref;
        }

        return new Response($this->templating->render('Repository/graph.html.twig', [
            'repository' => $repository,
            'rows' => $rows,
            'width' => $rows ? max(array_column($rows, 'width')) : 0,
            'refs' => $refs,
            'page' => $page,
            'previousPage' => $page - 1,
            'nextPage' => $page + 1,
            'hasMore' => $hasMore,
        ]));
    }

    public function compare(string $repository, string $base, string $head): Response
    {
        $repository = $this->index->getRepository($repository);

        [$ahead, $behind] = $repository->getAheadBehind($base, $head);
        $mergeBase = $repository->getMergeBase($base, $head);

        return new Response($this->templating->render('Repository/compare.html.twig', [
            'repository' => $repository,
            'base' => $base,
            'head' => $head,
            'ahead' => $ahead,
            'behind' => $behind,
            'mergeBase' => $mergeBase,
            'aheadCommits' => $repository->getCommitsBetween($base, $head, self::COMPARE_LIMIT),
            'behindCommits' => $repository->getCommitsBetween($head, $base, self::COMPARE_LIMIT),
            'diffs' => $mergeBase ? $repository->getDiffBetween($base, $head) : [],
            'limit' => self::COMPARE_LIMIT,
        ]));
    }

    public function compareForm(Request $request, string $repository): RedirectResponse
    {
        $default = $this->index->getRepository($repository)->getDefaultBranch();

        return new RedirectResponse($this->router->generate('repository_compare', [
            'repository' => $repository,
            'base' => $request->query->getString('base') ?: $default,
            'head' => $request->query->getString('head') ?: $default,
        ]));
    }
}
