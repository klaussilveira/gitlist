<?php

declare(strict_types=1);

namespace GitList\E2E;

class HistoryTest extends WebTestCase
{
    public function testIsShowingFileHistory(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/history/a003d30bc7a355f55bf28479e62134186bae1aed/mm/cma.c');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.breadcrumb .active', 'cma.c');
        self::assertSelectorTextSame('.card-header', 'November 24, 2016');
        self::assertSelectorTextSame('.me-auto a[href="/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed"]', 'Fixed mm.');
    }
}
