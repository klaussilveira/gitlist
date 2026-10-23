<?php

declare(strict_types=1);

namespace GitList\E2E;

class GraphTest extends WebTestCase
{
    public function testIsShowingGraphOfAllBranches(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/graph');

        self::assertResponseIsSuccessful();

        $subjects = $crawler->filter('.graph-subject a[href^="/git-bare-repo/commit/"]')->each(fn ($link) => $link->text());
        self::assertSame(['Fixed mm.', 'Added mm.', 'Added symlink.', 'Added symlink.', 'Added new config.', 'Added function call.', 'Initial commit.'], $subjects);
        self::assertCount(7, $crawler->filter('.graph-table .graph-node'));
        self::assertSame('feature/1.2-dev', $crawler->filter('.graph-subject a[href="/git-bare-repo/tree/feature/1.2-dev"]')->text(null, true));
        self::assertSame('1.2', $crawler->filter('.graph-subject a[href="/git-bare-repo/tree/1.2"]')->text(null, true));
    }

    public function testIsDrawingBranchOnItsOwnLane(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/graph');

        $lanes = $crawler->filter('.graph-table .graph-node')->each(fn ($node) => $node->attr('cx'));
        self::assertSame(['10', '10', '10', '26', '26', '10', '10'], $lanes);
    }

    public function testIsComparingBranches(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/compare/master...feature/1.2-dev');

        self::assertResponseIsSuccessful();
        self::assertSame(['Added symlink.', 'Added new config.'], $crawler->filter('.compare-ahead .me-auto a[href^="/git-bare-repo/commit/"]')->each(fn ($link) => $link->text()));
        self::assertSame(['Fixed mm.', 'Added mm.', 'Added symlink.'], $crawler->filter('.compare-behind .me-auto a[href^="/git-bare-repo/commit/"]')->each(fn ($link) => $link->text()));
        self::assertSelectorTextContains('.compare-summary', '2 commits ahead');
        self::assertSelectorTextContains('.compare-summary', '3 commits behind');
        self::assertSelectorTextContains('.compare-summary', '0e3ddf0');
        self::assertSame(['new.json', 'old.json'], $crawler->filter('a[id^="diff-"]')->each(fn ($link) => $link->text(null, true)));
    }

    public function testIsShowingIdenticalRefs(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/compare/master...master');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.compare-ahead, .compare-behind, a[id^="diff-"]'));
        self::assertSelectorTextContains('body', 'master and master are identical');
    }

    public function testIsRedirectingCompareForm(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/compare?base=feature/1.2-dev&head=master');

        self::assertResponseIsSuccessful();
        self::assertSame(sprintf('%s/git-bare-repo/compare/feature/1.2-dev...master', self::$baseUri), $client->getHistory()->current()->getUri());
    }

    public function testIsRejectingUnknownRefInCompare(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/compare/master...does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }

    public function testIsShowingBranchComparison(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/branches');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.branch-comparison'));
        self::assertSame('0e3ddf0', $crawler->filter('.branch-comparison a[href="/git-bare-repo/commit/0e3ddf0c6d39e7a300bef61b99a914a86ce5e267"]')->text(null, true));
        self::assertSame('master', $crawler->filter('.branch-comparison .branch-parent a')->text(null, true));
        self::assertSelectorTextContains('.branch-comparison', '2 commits ahead of master');
        self::assertSelectorTextContains('.branch-comparison', '3 commits behind master');
    }
}
