<?php

declare(strict_types=1);

namespace GitList\Repository;

use GitList\SCM\AttributeRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    public const ROOT = <<<'EOT'
[attr]generated linguist-generated -diff
*.bin binary
*.min.js generated
/root.txt anchored
docs/*.md docs-only
**/build/* in-build
vendor/** vendored
assets/ dir-only
*.lock -text diff
*.lock !diff
file[0-9].txt numbered
file[!0-9].txt lettered
*.min.js diff=minified

EOT;

    public const NESTED = <<<'EOT'
*.md nested
/top.md nested-anchored
[attr]ignored-macro foo
*.txt ignored-macro

EOT;

    public const INFO = "docs/top.md export-ignore\n";

    public static function pathProvider(): array
    {
        return [
            'built-in binary macro' => ['data.bin', ['binary' => true, 'diff' => false, 'merge' => false, 'text' => false]],
            'custom macro overridden later' => ['app.min.js', ['generated' => true, 'linguist-generated' => true, 'diff' => 'minified']],
            'anchored at root' => ['root.txt', ['anchored' => true]],
            'anchored not matching deeper' => ['sub/root.txt', []],
            'nested source and path pattern' => ['docs/a.md', ['docs-only' => true, 'nested' => true]],
            'path pattern does not cross directories' => ['docs/sub/a.md', ['nested' => true]],
            'leading double star' => ['x/build/out.o', ['in-build' => true]],
            'leading double star at root' => ['build/out.o', ['in-build' => true]],
            'trailing double star' => ['vendor/a/b.js', ['vendored' => true]],
            'directory pattern never matches files' => ['assets/x.png', []],
            'unspecified resets attribute' => ['y.lock', ['text' => false]],
            'character class' => ['file1.txt', ['numbered' => true]],
            'negated character class' => ['filea.txt', ['lettered' => true]],
            'info attributes and nested anchor' => ['docs/top.md', ['docs-only' => true, 'nested' => true, 'nested-anchored' => true, 'export-ignore' => true]],
            'nested anchor relative to its directory' => ['docs/sub/top.md', ['nested' => true]],
            'macro outside root is ignored' => ['docs/n.txt', ['ignored-macro' => true]],
            'no match' => ['README.md', []],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testIsResolvingAttributesLikeGit(string $path, array $expected): void
    {
        $attributes = new Attributes([
            ...AttributeRule::parse('.gitattributes', self::ROOT),
            ...AttributeRule::parse('docs/.gitattributes', self::NESTED),
            ...AttributeRule::parse('info/attributes', self::INFO),
        ]);

        $resolved = $attributes->resolve($path);
        ksort($resolved);
        ksort($expected);

        $this->assertSame($expected, $resolved);
    }
}
