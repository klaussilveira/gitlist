<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    public function testIsGettingFirstParent(): void
    {
        $firstParent = new Item(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $secondParent = new Item(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));

        $object = new Item(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $object->addParent($firstParent);
        $object->addParent($secondParent);

        $this->assertEquals($firstParent, $object->getFirstParent());
    }

    public function testIsClearingParents(): void
    {
        $firstParent = new Item(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $secondParent = new Item(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));

        $object = new Item(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $object->addParent($firstParent);
        $object->addParent($secondParent);
        $object->clearParents();

        $this->assertEmpty($object->getParents());
        $this->assertNull($object->getFirstParent());
    }
}
