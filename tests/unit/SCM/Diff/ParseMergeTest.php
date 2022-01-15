<?php

declare(strict_types=1);

namespace GitList\SCM\Diff;

use PHPUnit\Framework\TestCase;

class ParseMergeTest extends TestCase
{
    public const MERGE_RAW_DIFF_BLOCK = <<<DIFF
diff --cc Makefile
index ccb7d5b2fbf5,06a5798335fc..efb942ad0b55
--- a/Makefile
+++ b/Makefile
@@@ -621,6 -602,12 +604,11 @@@ endi
  # Defaults to vmlinux, but the arch makefile usually adds further targets
  all: vmlinux

+ KBUILD_CFLAGS += $(call cc-option,-fno-PIE)
+ KBUILD_AFLAGS += $(call cc-option,-fno-PIE)
+ CFLAGS_GCOV   := -fprofile-arcs -ftest-coverage -fno-tree-loop-im $(call cc-disable-warning,maybe-uninitialized,)
 -CFLAGS_KCOV   := $(call cc-option,-fsanitize-coverage=trace-pc,)
+ export CFLAGS_GCOV CFLAGS_KCOV
+
  # The arch Makefile can set ARCH_{CPP,A,C}FLAGS to override the default
  # values of the respective KBUILD_* variables
  ARCH_CPPFLAGS :=
DIFF;

    public function testIsParsingRawDiffBlock(): void
    {
        $parse = new Parse();
        $files = $parse->fromRawBlock(self::MERGE_RAW_DIFF_BLOCK);

        $this->assertCount(1, $files);
        $this->assertEquals('Makefile', $files[0]->getName());
        $this->assertEquals(File::TYPE_NO_CHANGE, $files[0]->getType());
        $this->assertEquals('06a5798335fc..efb942ad0b55', $files[0]->getIndex());
        $this->assertEquals('a/Makefile', $files[0]->getFrom());
        $this->assertEquals('b/Makefile', $files[0]->getTo());
        $this->assertEquals(5, $files[0]->getAdditions());
        $this->assertEquals(0, $files[0]->getDeletions());
        $this->assertCount(1, $files[0]->getHunks());

        // First hunk
        $firstHunk = $files[0]->getHunks()[0];
        $this->assertEquals(602, $firstHunk->getOldStart());
        $this->assertEquals(12, $firstHunk->getOldCount());
        $this->assertEquals(604, $firstHunk->getNewStart());
        $this->assertEquals(11, $firstHunk->getNewCount());
        $this->assertEquals('@@@ -621,6 -602,12 +604,11 @@@ endi', $firstHunk->getContents());
        $this->assertCount(12, $firstHunk->getLines());

        // Line 1
        $this->assertEquals('  # Defaults to vmlinux, but the arch makefile usually adds further targets', $firstHunk->getLines()[0]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[0]->getType());
        $this->assertEquals(602, $firstHunk->getLines()[0]->getOldNumber());

        // Line 2
        $this->assertEquals('  all: vmlinux', $firstHunk->getLines()[1]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[1]->getType());
        $this->assertEquals(603, $firstHunk->getLines()[1]->getOldNumber());

        // Line 3
        $this->assertEmpty($firstHunk->getLines()[2]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[2]->getType());
        $this->assertEquals(604, $firstHunk->getLines()[2]->getOldNumber());

        // Line 4
        $this->assertEquals('+ KBUILD_CFLAGS += $(call cc-option,-fno-PIE)', $firstHunk->getLines()[3]->getContents());
        $this->assertEquals(Line::TYPE_ADD, $firstHunk->getLines()[3]->getType());
        $this->assertEquals(605, $firstHunk->getLines()[3]->getOldNumber());

        // Line 5
        $this->assertEquals('+ KBUILD_AFLAGS += $(call cc-option,-fno-PIE)', $firstHunk->getLines()[4]->getContents());
        $this->assertEquals(Line::TYPE_ADD, $firstHunk->getLines()[4]->getType());
    }

    public function testIsClearingParserAccumulator(): void
    {
        $parse = new Parse();
        $files = $parse->fromRawBlock(self::MERGE_RAW_DIFF_BLOCK);
        $files = $parse->fromRawBlock(self::MERGE_RAW_DIFF_BLOCK);

        $this->assertCount(1, $files);
    }

    public function testIsIgnoringEmptyRawBlock(): void
    {
        $parse = new Parse();
        $files = $parse->fromRawBlock('');
        $this->assertEmpty($files);
    }
}
