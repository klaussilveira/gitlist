<?php

declare(strict_types=1);

namespace GitList\E2E;

use Facebook\WebDriver\WebDriverExpectedCondition;

class RepositoryTest extends WebTestCase
{
    public function testIsShowingRepository(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo');

        self::assertResponseIsSuccessful();

        $stats = $crawler->filter('.repository-stats .nav-link');
        self::assertSame('2 Branches', $stats->eq(0)->text(null, true));
        self::assertSame('1 Tags', $stats->eq(1)->text(null, true));

        self::assertSelectorTextSame('.card-header span', 'Klaus Silveira Fixed mm.');
        self::assertSelectorTextSame('.float-end', 'a003d30 @ 2016-11-24 12:30:04');
    }

    public function testIsShowingBranchesInRefList(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/git-bare-repo');

        $client->waitForVisibility('.dropdown > .btn')->filter('.dropdown > .btn')->click();
        $client->waitForVisibility('.ref-list');

        self::assertSelectorIsVisible('#branches a[href="/git-bare-repo/tree/feature/1.2-dev/"]');
        self::assertSelectorTextSame('#branches a[href="/git-bare-repo/tree/feature/1.2-dev/"]', 'feature/1.2-dev');
    }

    public function testIsShowingTagsInRefList(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/git-bare-repo');

        $client->waitForVisibility('.dropdown > .btn')->filter('.dropdown > .btn')->click();
        $client->waitForVisibility('#tags-tab')->filter('#tags-tab')->click();
        $client->waitForVisibility('#tags .list-group-item');

        self::assertSelectorTextSame('#tags .list-group-item', '1.2');
        self::assertSelectorIsNotVisible('#branches a[href="/git-bare-repo/tree/feature/1.2-dev/"]');
    }

    public function testIsNavigatingToRefFromAutocomplete(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/git-bare-repo');

        $client->waitForVisibility('.dropdown > .btn')->filter('.dropdown > .btn')->click();
        $client->waitForVisibility('.ref-list input')->filter('.ref-list input')->sendKeys('feature/1.2-dev');

        $client->wait()->until(WebDriverExpectedCondition::urlIs(self::$baseUri.'/git-bare-repo/tree/feature/1.2-dev/'));
        $client->refreshCrawler();

        self::assertSame(self::$baseUri.'/git-bare-repo/tree/feature/1.2-dev/', $client->getCurrentURL());
        self::assertSelectorTextSame('h1', 'git-bare-repo');
    }

    public function testIsSwappingCloneUrl(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/git-bare-repo');

        self::assertSelectorAttributeContains('.clone-input input', 'value', 'git@gitlist.org:git-bare-repo.git');

        $client->waitForVisibility('[data-toggle="clone-input"]')->filter('[data-toggle="clone-input"]')->click();
        $client->waitForVisibility('[data-clone-url="https://gitlist.org/git-bare-repo.git"]')->filter('[data-clone-url="https://gitlist.org/git-bare-repo.git"]')->click();
        $client->waitForAttributeToContain('.clone-input input', 'value', 'https://gitlist.org/git-bare-repo.git');

        self::assertSelectorAttributeContains('.clone-input input', 'value', 'https://gitlist.org/git-bare-repo.git');

        $client->waitForVisibility('[data-toggle="clone-input"]')->filter('[data-toggle="clone-input"]')->click();
        $client->waitForVisibility('[data-clone-url="git@gitlist.org:git-bare-repo.git"]')->filter('[data-clone-url="git@gitlist.org:git-bare-repo.git"]')->click();
        $client->waitForAttributeToContain('.clone-input input', 'value', 'git@gitlist.org:git-bare-repo.git');

        self::assertSelectorAttributeContains('.clone-input input', 'value', 'git@gitlist.org:git-bare-repo.git');
    }
}
