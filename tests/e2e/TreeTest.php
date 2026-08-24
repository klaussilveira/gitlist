<?php

declare(strict_types=1);

namespace GitList\E2E;

class TreeTest extends WebTestCase
{
    public function testIsNavigatingToSubTreeAndFile(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo');

        $crawler = $client->click($crawler->filter('.tree-filename a[href="/git-bare-repo/tree/HEAD/mm"]')->link());
        $client->click($crawler->filter('.tree-filename a[href="/git-bare-repo/blob/HEAD/mm/cma.c"]')->link());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.breadcrumb .active', 'cma.c');
        self::assertSelectorTextSame('.card-body h5', 'Fixed mm.');
    }
}
