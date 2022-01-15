<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class FileTest extends TestCase
{
    use ProphecyTrait;

    public function testIsCreatingFromBlob(): void
    {
        $blob = $this->prophesize(Blob::class);
        $blob->getName()->willReturn('my_test.png');
        $blob->getContents()->willReturn('PNGFOO123');

        $file = File::createFromBlob($blob->reveal());
        $this->assertEquals('my_test.png', $file->getName());
        $this->assertEquals('png', $file->getExtension());
        $this->assertEquals('image/png', $file->getMimeType());
        $this->assertEquals('PNGFOO123', $file->getContents());
        $this->assertTrue($file->isImage());
    }

    public function testIsDetectingImages(): void
    {
        $file = new File('test.gif');
        $this->assertTrue($file->isImage());

        $file = new File('test.avi');
        $this->assertFalse($file->isImage());

        $file = new File('test.jpg');
        $this->assertTrue($file->isImage());

        $file = new File('test.zip');
        $this->assertFalse($file->isImage());
    }

    public function testIsDetectingVideos(): void
    {
        $file = new File('test.avi');
        $this->assertTrue($file->isVideo());

        $file = new File('test.gif');
        $this->assertFalse($file->isVideo());

        $file = new File('test.jpg');
        $this->assertFalse($file->isVideo());

        $file = new File('test.zip');
        $this->assertFalse($file->isVideo());
    }

    public function testIsDetectingAudio(): void
    {
        $file = new File('test.wav');
        $this->assertTrue($file->isAudio());

        $file = new File('test.gif');
        $this->assertFalse($file->isAudio());

        $file = new File('test.jpg');
        $this->assertFalse($file->isAudio());

        $file = new File('test.zip');
        $this->assertFalse($file->isAudio());
    }

    public function testIsDetectingModel(): void
    {
        $file = new File('test.dae');
        $this->assertTrue($file->isModel());

        $file = new File('test.gif');
        $this->assertFalse($file->isModel());

        $file = new File('test.jpg');
        $this->assertFalse($file->isModel());

        $file = new File('test.zip');
        $this->assertFalse($file->isModel());
    }

    public function testIsDetectingBinary(): void
    {
        $file = new File('test.zip');
        $this->assertTrue($file->isBinary());

        $file = new File('test.rar');
        $this->assertTrue($file->isBinary());

        $file = new File('test.wav');
        $this->assertFalse($file->isBinary());

        $file = new File('test.gif');
        $this->assertFalse($file->isBinary());

        $file = new File('test.jpg');
        $this->assertFalse($file->isBinary());
    }

    public function testIsDetectingText(): void
    {
        $file = new File('test.txt');
        $this->assertTrue($file->isText());

        $file = new File('test.php');
        $this->assertTrue($file->isText());

        $file = new File('test.wav');
        $this->assertFalse($file->isText());

        $file = new File('test.gif');
        $this->assertFalse($file->isText());

        $file = new File('test.jpg');
        $this->assertFalse($file->isText());
    }
}
