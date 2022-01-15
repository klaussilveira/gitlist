<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\TestCase;

class TreeTest extends TestCase
{
    public function createTree()
    {
        return new Tree(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
    }

    public function testIsHandlingChildren(): void
    {
        $root = $this->createTree();
        $child1 = $this->createTree();
        $child2 = $this->createTree();
        $child3 = $this->createTree();

        $root->addChild($child1);
        $root->addChild($child2);
        $root->addChild($child3);

        $this->assertEquals([$child1, $child2, $child3], $root->getChildren());
    }

    public function testIsRemovingChild(): void
    {
        $root = $this->createTree();
        $child1 = $this->createTree();
        $child2 = $this->createTree();
        $child3 = $this->createTree();

        $root->addChild($child1);
        $root->addChild($child2);
        $root->addChild($child3);
        $root->removeChild($child2);

        $this->assertEquals([$child1, $child3], $root->getChildren());
    }

    public function testIsRemovingChildAndParentReference(): void
    {
        $root = $this->createTree();
        $child1 = $this->createTree();
        $root->addChild($child1);
        $root->removeChild($child1);

        $this->assertEmpty($child1->getParents());
    }

    public function testIsDetectingParent(): void
    {
        $root = $this->createTree();
        $child1 = $this->createTree();
        $child2 = $this->createTree();

        $root->addChild($child1);
        $root->addChild($child2);

        $this->assertEquals($root, $child1->getParents()[0]);
        $this->assertEquals($root, $child2->getParents()[0]);
    }

    public function testIsLeaf(): void
    {
        $root = $this->createTree();
        $this->assertTrue($root->isLeaf());
        $root->addChild($this->createTree());
        $this->assertFalse($root->isLeaf());
    }

    public function testIsRoot(): void
    {
        $root = $this->createTree();
        $child = $this->createTree();
        $root->addChild($child);

        $this->assertTrue($root->isRoot());
        $this->assertFalse($child->isRoot());
    }

    public function testIsChild(): void
    {
        $root = $this->createTree();
        $child = $this->createTree();
        $root->addChild($child);

        $this->assertTrue($child->isChild());
        $this->assertFalse($root->isChild());
    }

    public function testIsDetectingChildren(): void
    {
        $root = $this->createTree();
        $child = $this->createTree();
        $root->addChild($child);

        $this->assertFalse($child->hasChildren());
        $this->assertTrue($root->hasChildren());
    }

    public function testIsGettingReadme(): void
    {
        $root = $this->createTree();
        $child1 = $this->createTree();
        $child2 = $this->createTree();
        $child3 = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $child4 = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $child4->setName('/var/foo/README.md');

        $root->addChild($child1);
        $root->addChild($child2);
        $root->addChild($child3);
        $root->addChild($child4);

        $this->assertEquals($child4, $root->getReadme());
    }

    public function testIsNotGettingReadme(): void
    {
        $root = $this->createTree();
        $child1 = $this->createTree();
        $child2 = $this->createTree();
        $child3 = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));

        $root->addChild($child1);
        $root->addChild($child2);
        $root->addChild($child3);

        $this->assertNull($root->getReadme());
    }
}
