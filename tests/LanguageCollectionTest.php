<?php

namespace Bili\Tests;

use Bili\LanguageCollection;
use Bili\LanguageFile;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\LanguageCollection.
 *
 * Covers construction (empty and pre-filled), adding objects,
 * counting, Iterator interface (current, next, key, valid, rewind),
 * and iteration via foreach.
 *
 * @see LanguageCollection
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/LanguageCollectionTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testIteration tests/LanguageCollectionTest.php
 */
class LanguageCollectionTest extends TestCase
{
    /**
     * Tests that an empty LanguageCollection has a count of zero.
     *
     * @return void
     */
    public function testConstructEmpty(): void
    {
        $col = new LanguageCollection();
        $this->assertEquals(0, $col->count());
    }

    /**
     * Tests that constructing with an initial array sets the correct count.
     *
     * @return void
     */
    public function testConstructWithArray(): void
    {
        $col = new LanguageCollection(["a", "b"]);
        $this->assertEquals(2, $col->count());
    }

    /**
     * Tests that addObject() appends a LanguageFile to the collection
     * and increments the count.
     *
     * @return void
     */
    public function testAddObject(): void
    {
        $col = new LanguageCollection();
        $file = new LanguageFile();
        $file->name = "english-utf-8";
        $file->language = "English";
        $col->addObject($file);

        $this->assertEquals(1, $col->count());
    }

    /**
     * Tests that the collection can be iterated with foreach,
     * yielding all added LanguageFile objects in order.
     *
     * @return void
     */
    public function testIteration(): void
    {
        $file1 = new LanguageFile();
        $file1->name = "en";
        $file1->language = "English";

        $file2 = new LanguageFile();
        $file2->name = "nl";
        $file2->language = "Dutch";

        $col = new LanguageCollection();
        $col->addObject($file1);
        $col->addObject($file2);

        $names = [];
        foreach ($col as $item) {
            $names[] = $item->name;
        }

        $this->assertEquals(["en", "nl"], $names);
    }

    /**
     * Tests that rewind() resets the internal pointer back to the first element.
     *
     * @return void
     */
    public function testRewind(): void
    {
        $col = new LanguageCollection(["a", "b", "c"]);
        $col->next();
        $col->next();
        $col->rewind();
        $this->assertEquals("a", $col->current());
        $this->assertEquals(0, $col->key());
    }

    /**
     * Tests that valid() returns true when the pointer is on an element,
     * and false after advancing past the last element.
     *
     * @return void
     */
    public function testValid(): void
    {
        $col = new LanguageCollection(["a"]);
        $this->assertTrue($col->valid());
        $col->next();
        $this->assertFalse($col->valid());
    }
}
