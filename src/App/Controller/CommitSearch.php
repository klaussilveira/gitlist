<?php

declare(strict_types=1);

namespace GitList\App\Controller;

use GitList\App\Form\CriteriaType;
use GitList\Repository\Index;
use GitList\SCM\Commit\Criteria;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class CommitSearch
{
    public function __construct(protected Environment $templating, protected Index $index, protected FormFactoryInterface $formFactory, protected RouterInterface $router)
    {
    }

    public function createForm(Request $request, string $repository, string $commitish): Response
    {
        $repository = $this->index->getRepository($repository);
        $form = $this->formFactory->create(CriteriaType::class, new Criteria());

        return new Response($this->templating->render('Search/form.html.twig', [
            'repository' => $repository,
            'commitish' => $commitish,
            'form' => $form->createView(),
        ]));
    }

    public function showResults(Request $request, string $repository, string $commitish): Response
    {
        $criteria = new Criteria();
        $criteria->setMessage($request->request->get('query', ''));

        $form = $this->formFactory->create(CriteriaType::class, $criteria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $request->getSession()->getFlashBag()->add('danger', $error->getMessage());
            }

            return new RedirectResponse($this->router->generate('repository_tree', [
                'repository' => $repository,
                'commitish' => $commitish,
            ]));
        }

        $repository = $this->index->getRepository($repository);
        $commits = $repository->searchCommits($form->getData(), $commitish);
        $commitGroups = [];

        foreach ($commits as $commit) {
            $commitGroups[$commit->getCommitedAt()->format('Y-m-d')][] = $commit;
        }

        return new Response($this->templating->render('Search/list.html.twig', [
            'repository' => $repository,
            'commitGroups' => $commitGroups,
            'commitish' => $commitish,
        ]));
    }
}
