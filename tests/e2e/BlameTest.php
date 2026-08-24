<?php

declare(strict_types=1);

namespace GitList\E2E;

class BlameTest extends WebTestCase
{
    public function testIsShowingFileBlame(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/blame/a003d30bc7a355f55bf28479e62134186bae1aed/mm/cma.c');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.breadcrumb .active', 'cma.c');

        $authors = $crawler->filter('.blame-lines .author-info a');
        self::assertSame('Added mm.', $authors->eq(0)->text(null, true));
        self::assertSame('Fixed mm.', $authors->eq(1)->text(null, true));
    }
}
