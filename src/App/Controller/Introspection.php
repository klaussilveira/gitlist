<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\Repository\Commitish;
use GitList\Repository\Index;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class Introspection
{
    public function __construct(protected Environment $templating, protected Index $index, protected bool $showHookContents)
    {
    }

    public function show(string $repository, string $commitish): Response
    {
        $repository = $this->index->getRepository($repository);
        $revision = new Commitish($repository, $commitish);

        return new Response($this->templating->render('Repository/metadata.html.twig', [
            'repository' => $repository,
            'commitish' => $commitish,
            'info' => $repository->getInfo(),
            'hooks' => $repository->getHooks(),
            'showHookContents' => $this->showHookContents,
            'rules' => $repository->getAttributes($revision),
            'submodules' => $repository->getSubmodules($revision),
        ]));
    }
}
