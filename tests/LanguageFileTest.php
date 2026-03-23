<?php

namespace Bili\Tests;

use Bili\LanguageFile;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\LanguageFile.
 *
 * Covers the public properties (name, language) used to represent
 * a single language file entry in a LanguageCollection.
 *
 * @see LanguageFile
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/LanguageFileTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testProperties tests/LanguageFileTest.php
 */
class LanguageFileTest extends TestCase
{
    /**
     * Tests that the public name and language properties can be set and read.
     *
     * @return void
     */
    public function testProperties(): void
    {
        $file = new LanguageFile();
        $file->name = "english-utf-8";
        $file->language = "English";

        $this->assertEquals("english-utf-8", $file->name);
        $this->assertEquals("English", $file->language);
    }

    /**
     * Tests that name and language are null by default on a new instance.
     *
     * @return void
     */
    public function testDefaultProperties(): void
    {
        $file = new LanguageFile();
        $this->assertNull($file->name);
        $this->assertNull($file->language);
    }
}
