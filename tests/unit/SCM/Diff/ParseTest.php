<?php

declare(strict_types=1);

namespace GitList\SCM\Diff;

use PHPUnit\Framework\TestCase;

class ParseTest extends TestCase
{
    public const RAW_DIFF_BLOCK = <<<DIFF
diff --git a/mm/cma.c b/mm/cma.c
index c960459..c17751c 100644
--- a/mm/cma.c
+++ b/mm/cma.c
@@ -23,7 +23,6 @@
 #  define DEBUG
 #endif
 #endif
-#define CREATE_TRACE_POINTS

 #include <linux/memblock.h>
 #include <linux/err.h>
@@ -33,56 +32,46 @@
 #include <linux/slab.h>
 #include <linux/log2.h>
 #include <linux/cma.h>
-#include <linux/highmem.h>
-#include <linux/io.h>
-#include <trace/events/cma.h>

-#include "cma.h"
+struct cma {
+   unsigned long   base_pfn;
+   unsigned long   count;
+   unsigned long   *bitmap;
+   unsigned int order_per_bit; /* Order of pages represented by one bit */
+   struct mutex    lock;
+};

-struct cma cma_areas[MAX_CMA_AREAS];
-unsigned cma_area_count;
+static struct cma cma_areas[MAX_CMA_AREAS];
+static unsigned cma_area_count;
 static DEFINE_MUTEX(cma_mutex);

-phys_addr_t cma_get_base(const struct cma *cma)
+phys_addr_t cma_get_base(struct cma *cma)
 {
    return PFN_PHYS(cma->base_pfn);
 }

-unsigned long cma_get_size(const struct cma *cma)
+unsigned long cma_get_size(struct cma *cma)
 {
    return cma->count << PAGE_SHIFT;
 }

-static unsigned long cma_bitmap_aligned_mask(const struct cma *cma,
-                        int align_order)
+static unsigned long cma_bitmap_aligned_mask(struct cma *cma, int align_order)
 {
-   if (align_order <= cma->order_per_bit)
-       return 0;
-   return (1UL << (align_order - cma->order_per_bit)) - 1;
+   return (1UL << (align_order >> cma->order_per_bit)) - 1;
 }
DIFF;

    public function testIsParsingRawDiffBlock(): void
    {
        $parse = new Parse();
        $files = $parse->fromRawBlock(self::RAW_DIFF_BLOCK);

        $this->assertCount(1, $files);
        $this->assertEquals('mm/cma.c', $files[0]->getName());
        $this->assertEquals(File::TYPE_NO_CHANGE, $files[0]->getType());
        $this->assertEquals('c960459..c17751c', $files[0]->getIndex());
        $this->assertEquals('a/mm/cma.c', $files[0]->getFrom());
        $this->assertEquals('b/mm/cma.c', $files[0]->getTo());
        $this->assertEquals(13, $files[0]->getAdditions());
        $this->assertEquals(14, $files[0]->getDeletions());
        $this->assertCount(2, $files[0]->getHunks());

        // First hunk
        $firstHunk = $files[0]->getHunks()[0];
        $this->assertEquals(23, $firstHunk->getOldStart());
        $this->assertEquals(7, $firstHunk->getOldCount());
        $this->assertEquals(23, $firstHunk->getNewStart());
        $this->assertEquals(6, $firstHunk->getNewCount());
        $this->assertEquals('@@ -23,7 +23,6 @@', $firstHunk->getContents());
        $this->assertCount(7, $firstHunk->getLines());

        // Line 1
        $this->assertEquals(' #  define DEBUG', $firstHunk->getLines()[0]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[0]->getType());
        $this->assertEquals(23, $firstHunk->getLines()[0]->getOldNumber());

        // Line 2
        $this->assertEquals(' #endif', $firstHunk->getLines()[1]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[1]->getType());
        $this->assertEquals(24, $firstHunk->getLines()[1]->getOldNumber());

        // Line 3
        $this->assertEquals(' #endif', $firstHunk->getLines()[2]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[2]->getType());
        $this->assertEquals(25, $firstHunk->getLines()[2]->getOldNumber());

        // Line 4
        $this->assertEquals('-#define CREATE_TRACE_POINTS', $firstHunk->getLines()[3]->getContents());
        $this->assertEquals(Line::TYPE_DELETE, $firstHunk->getLines()[3]->getType());
        $this->assertEquals(26, $firstHunk->getLines()[3]->getOldNumber());

        // Line 5
        $this->assertEquals('', $firstHunk->getLines()[4]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[4]->getType());

        // Line 6
        $this->assertEquals(' #include <linux/memblock.h>', $firstHunk->getLines()[5]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[5]->getType());

        // Line 7
        $this->assertEquals(' #include <linux/err.h>', $firstHunk->getLines()[6]->getContents());
        $this->assertEquals(Line::TYPE_NO_CHANGE, $firstHunk->getLines()[6]->getType());

        // Second hunk
        $secondHunk = $files[0]->getHunks()[1];
        $this->assertEquals(33, $secondHunk->getOldStart());
        $this->assertEquals(56, $secondHunk->getOldCount());
        $this->assertEquals(32, $secondHunk->getNewStart());
        $this->assertEquals(46, $secondHunk->getNewCount());
        $this->assertEquals('@@ -33,56 +32,46 @@', $secondHunk->getContents());
        $this->assertCount(43, $secondHunk->getLines());
    }

    public function testIsClearingParserAccumulator(): void
    {
        $parse = new Parse();
        $files = $parse->fromRawBlock(self::RAW_DIFF_BLOCK);
        $files = $parse->fromRawBlock(self::RAW_DIFF_BLOCK);

        $this->assertCount(1, $files);
    }

    public function testIsIgnoringEmptyRawBlock(): void
    {
        $parse = new Parse();
        $files = $parse->fromRawBlock('');
        $this->assertEmpty($files);
    }
}
