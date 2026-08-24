<?php

declare(strict_types=1);

namespace GitList\E2E;

class CommitSearchTest extends WebTestCase
{
    public function testIsSearchingCommitsByMessage(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/search/commits/master');

        self::assertResponseIsSuccessful();

        $client->submit($crawler->filter('#criteria_submit')->form(), ['criteria[message]' => 'mm']);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.me-auto a[href="/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed"]', 'Fixed mm.');
        self::assertSelectorTextSame('.me-auto a[href="/git-bare-repo/commit/5570c142146e430b7356a84175f281ab2a364d48"]', 'Added mm.');
    }
}
