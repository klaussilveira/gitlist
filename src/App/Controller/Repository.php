<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\Repository\Index;
use GitList\SCM\File;
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
        $readme = $tree->getReadme();

        if ($readme) {
            $blob = $repository->getBlob($tree->getHash().'/'.$readme->getName());
            $readme = File::createFromBlob($blob);
        }

        return new Response($this->templating->render('Repository/show.html.twig', [
            'repository' => $repository,
            'tree' => $tree,
            'lastCommit' => $lastCommit,
            'readme' => $readme,
        ]));
    }

    public function showTree(string $repository, string $commitish): Response
    {
        $repository = $this->index->getRepository($repository);
        $tree = $repository->getTree($commitish);
        $lastCommit = $repository->getCommit($tree->getHash());
        $readme = $tree->getReadme();

        if ($readme) {
            $blob = $repository->getBlob($tree->getHash().'/'.$readme->getName());
            $readme = File::createFromBlob($blob);
        }

        return new Response($this->templating->render('Repository/show.html.twig', [
            'repository' => $repository,
            'tree' => $tree,
            'lastCommit' => $lastCommit,
            'readme' => $readme,
        ]));
    }

    public function listBranches(string $repository): Response
    {
        $repository = $this->index->getRepository($repository);
        $branches = $repository->getBranches();

        return new Response($this->templating->render('Repository/branches.html.twig', [
            'repository' => $repository,
            'branches' => $branches,
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

        $response = new Response(file_get_contents($archive));
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($archive));
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }
}
