<?php

declare(strict_types=1);

namespace GitList\E2E;

use GitList\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IntrospectionTest extends WebTestCase
{
    public function testIsShowingHookCountNextToRefCounts(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-introspection-repo');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.repository-stats .hooks-count', '1 Hooks');
        self::assertSelectorAttributeContains('.repository-stats .hooks-count a', 'href', '/git-introspection-repo/metadata/master#hooks');
    }

    public function testIsNotShowingHookCountForSamplesOnly(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.repository-stats .hooks-count'));
    }

    public function testIsListingHooksWithoutContents(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-introspection-repo/metadata/master');

        self::assertResponseIsSuccessful();
        self::assertSame(['post-receive'], $crawler->filter('#hooks-active .hook-name')->each(fn ($cell) => $cell->text()));
        self::assertSame(['pre-receive.sample', 'update'], $crawler->filter('#hooks-inactive .hook-name')->each(fn ($cell) => $cell->text()));
        self::assertSame(['Sample', 'Not executable'], $crawler->filter('#hooks-inactive .hook-status')->each(fn ($cell) => $cell->text()));
        self::assertCount(0, $crawler->filter('.hook-name a'));
        self::assertStringNotContainsString('DEPLOY_TOKEN', $client->getResponse()->getContent());
    }

    public function testIsShowingHookContentsWhenEnabled(): void
    {
        $response = $this->handleWithHookContents('/git-introspection-repo/metadata/master');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('echo &quot;Deploying with token $DEPLOY_TOKEN&quot;', (string) $response->getContent());
    }

    public function testIsListingAttributeRules(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-introspection-repo/metadata/master');

        self::assertResponseIsSuccessful();
        self::assertSame(['*', '*.md', '*.bin', '[attr]docs Macro', '*.md', 'docs/guide.md'], $crawler->filter('#attributes .attribute-pattern')->each(fn ($cell) => $cell->text()));
        self::assertSame(['filter=lfs', 'diff=lfs', 'merge=lfs', '-text'], $crawler->filter('#attributes tbody tr')->eq(2)->filter('.badge')->each(fn ($badge) => $badge->text()));
        self::assertSame(['.gitattributes:1', '.gitattributes:2', '.gitattributes:3', '.gitattributes:4', 'docs/.gitattributes:1', 'info/attributes:1'], $crawler->filter('#attributes .attribute-source')->each(fn ($cell) => $cell->text()));
        self::assertSame('/git-introspection-repo/blob/master/docs/.gitattributes', $crawler->filter('#attributes .attribute-source a')->eq(4)->attr('href'));
        self::assertCount(5, $crawler->filter('#attributes .attribute-source a'));
    }

    public function testIsShowingEffectiveAttributesOnBlob(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-introspection-repo/blob/master/docs/guide.md');

        self::assertResponseIsSuccessful();

        $badges = $crawler->filter('#blob-attributes .badge')->each(fn ($badge) => $badge->text());
        sort($badges);

        self::assertSame(['-diff', 'docs', 'eol=lf', 'export-ignore', 'linguist-documentation', 'text'], $badges);
    }

    public function testIsNotShowingAttributesOnBlobWithoutAny(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo/blob/master/test.json');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#blob-attributes'));
    }

    public function testIsShowingRepositoryInfoWithoutSecrets(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-introspection-repo/metadata/master');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('#info-properties .info-object_format', 'sha1');
        self::assertSelectorTextSame('#info-properties .info-bare', 'Yes');
        self::assertSame(['origin https://example.com/repo.git', 'mirror git@example.com:mirror.git'], $crawler->filter('#info-remotes tr')->each(fn ($row) => $row->text()));
        self::assertSame(['core.repositoryformatversion 0', 'receive.denynonfastforwards true'], $crawler->filter('#info-config tr')->each(fn ($row) => $row->text()));
        self::assertSame(['vendor/lib https://example.com/lib.git 1111111111111111111111111111111111111111'], $crawler->filter('#submodules tr')->each(fn ($row) => $row->text()));

        $content = $client->getResponse()->getContent();

        foreach (['hunter2', 'topsecret', 's3cr3t', 'extraheader'] as $secret) {
            self::assertStringNotContainsString($secret, $content);
        }
    }

    public function testIsShowingSubmoduleInTree(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-introspection-repo/tree/master/vendor');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.tree-submodule .tree-filename', 'lib @ 1111111');
        self::assertSame('https://example.com/lib.git', $crawler->filter('.tree-submodule .tree-filename a')->attr('href'));
        self::assertCount(0, $crawler->filter('a[href*="/blob/master/vendor/lib"]'));
    }

    public function testIsShowingCommitNotes(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-introspection-repo/commit/bc87025f97a7c2fefba6a0f3734b48f508538e92');

        self::assertResponseIsSuccessful();
        self::assertSame(['commits', 'review'], $crawler->filter('#commit-notes .badge')->each(fn ($badge) => $badge->text()));
        self::assertSame(['Reviewed by QA.', 'Ship it.'], $crawler->filter('#commit-notes pre')->each(fn ($note) => $note->text()));
    }

    protected function handleWithHookContents(string $uri): Response
    {
        $_ENV['SHOW_HOOK_CONTENTS'] = '1';
        $kernel = new Kernel('test', false);

        try {
            return $kernel->handle(Request::create($uri));
        } finally {
            $kernel->shutdown();
            unset($_ENV['SHOW_HOOK_CONTENTS']);
        }
    }
}
