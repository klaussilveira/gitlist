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

    public function testIsGettingDocumentsInFixedOrder(): void
    {
        $root = $this->createTree();
        $security = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $security->setName('SECURITY.md');
        $license = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $license->setName('LICENSE');
        $copying = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $copying->setName('COPYING');
        $readme = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $readme->setName('README.md');
        $other = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $other->setName('main.c');

        $root->addChild($security);
        $root->addChild($this->createTree());
        $root->addChild($license);
        $root->addChild($copying);
        $root->addChild($other);
        $root->addChild($readme);

        $this->assertSame(['readme' => $readme, 'license' => $license, 'security' => $security], $root->getDocuments());
    }

    public function testIsNotGettingDocuments(): void
    {
        $root = $this->createTree();
        $other = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $other->setName('main.c');

        $root->addChild($this->createTree());
        $root->addChild($other);

        $this->assertSame([], $root->getDocuments());
    }
}
