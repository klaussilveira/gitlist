<?php

declare(strict_types=1);

namespace GitList\E2E;

class CommitTest extends WebTestCase
{
    public function testIsListingCommits(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/commits/master');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.me-auto a[href="/git-bare-repo/commit/b064e711b341b3d160288cd121caf56811ca8991"]', 'Initial commit.');
    }

    public function testIsShowingCommit(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.card-header', 'Fixed mm. Parent 5570c14');
        self::assertSelectorTextSame('.card-text', 'Klaus Silveira commited on 2016-11-24 12:30:04 Showing 1 changed files, with 44 additions and 173 deletions.');
        self::assertSame('-#define CREATE_TRACE_POINTS', $crawler->filter('.diff-lines .line .delete')->first()->text(null, true));
    }

    public function testIsNavigatingToParentCommit(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/commit/a003d30bc7a355f55bf28479e62134186bae1aed');

        $client->click($crawler->filter('.card-header a[href="/git-bare-repo/commit/5570c142146e430b7356a84175f281ab2a364d48"]')->link());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.card-header', 'Added mm. Parent 85e6568');
    }
}
