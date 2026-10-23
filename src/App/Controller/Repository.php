<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\Repository as SourceRepository;
use GitList\Repository\Index;
use GitList\SCM\File;
use GitList\SCM\Tree;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class Repository
{
    public function __construct(protected Environment $templating, protected Index $index)
    {
    }

    public function list(): Response
    {
        $repositories = $this->index->getRepositories();

        ksort($repositories);

        return new Response($this->templating->render('Repository/list.html.twig', [
            'repositories' => $repositories,
        ]));
    }

    public function show(string $repository): Response
    {
        $repository = $this->index->getRepository($repository);
        $tree = $repository->getTree();
        $lastCommit = $repository->getCommit($tree->getHash());

        return new Response($this->templating->render('Repository/show.html.twig', [
            'repository' => $repository,
            'tree' => $tree,
            'lastCommit' => $lastCommit,
            'documents' => $this->getDocuments($repository, $tree),
        ]));
    }

    public function showTree(string $repository, string $commitish): Response
    {
        $repository = $this->index->getRepository($repository);
        $tree = $repository->getTree($commitish);
        $lastCommit = $repository->getCommit($tree->getHash());

        return new Response($this->templating->render('Repository/show.html.twig', [
            'repository' => $repository,
            'tree' => $tree,
            'lastCommit' => $lastCommit,
            'documents' => $this->getDocuments($repository, $tree),
        ]));
    }

    public function listBranches(string $repository): Response
    {
        $repository = $this->index->getRepository($repository);
        $branches = $repository->getBranches();
        $defaultBranch = $repository->getDefaultBranch();
        $names = array_map(fn ($branch) => (string) $branch->getName(), $branches);
        $comparisons = [];

        foreach ($names as $name) {
            if ('' === $name || $name === $defaultBranch) {
                continue;
            }

            [$ahead, $behind] = $repository->getAheadBehind($defaultBranch, $name);

            $comparisons[$name] = [
                'ahead' => $ahead,
                'behind' => $behind,
                'forkPoint' => $repository->getMergeBase($defaultBranch, $name),
                'parent' => $repository->guessParentBranch($name, $names),
            ];
        }

        return new Response($this->templating->render('Repository/branches.html.twig', [
            'repository' => $repository,
            'branches' => $branches,
            'defaultBranch' => $defaultBranch,
            'comparisons' => $comparisons,
        ]));
    }

    public function listTags(string $repository): Response
    {
        $repository = $this->index->getRepository($repository);
        $tags = $repository->getTags();

        return new Response($this->templating->render('Repository/tags.html.twig', [
            'repository' => $repository,
            'tags' => $tags,
        ]));
    }

    public function archive(string $repository, string $commitish, string $format): Response
    {
        $repository = $this->index->getRepository($repository);
        $archive = $repository->archive($format, $commitish);

        if (!file_exists($archive)) {
            throw new NotFoundHttpException();
        }

        $contents = file_get_contents($archive);
        unlink($archive);
        rmdir(dirname($archive));

        if (false === $contents) {
            throw new NotFoundHttpException();
        }

        $response = new Response($contents);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($archive));
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }

    /**
     * @return array<string, array{file: File, markdown: bool}>
     */
    private function getDocuments(SourceRepository $repository, Tree $tree): array
    {
        $documents = [];

        foreach ($tree->getDocuments() as $type => $document) {
            $blob = $repository->getBlob(sprintf('%s/%s', $tree->getHash(), $document->getName()));
            $documents[$type] = [
                'file' => File::createFromBlob($blob),
                'markdown' => $document->isMarkdown(),
            ];
        }

        return $documents;
    }
}
