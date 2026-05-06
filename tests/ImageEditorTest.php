<?php

namespace Bili\Tests;

use Bili\ImageEditor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\ImageEditor.
 *
 * Note: ImageEditor uses a PHP4-style constructor (function ImageEditor(...))
 * which is not recognized as __construct() in PHP 8+. This means the image
 * resource ($this->img) is never initialized by the constructor. Only methods
 * that do not depend on the image resource can be meaningfully tested here.
 *
 * The class needs to be updated to use __construct() for full testability.
 *
 * @see ImageEditor
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/ImageEditorTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testSetImageType tests/ImageEditorTest.php
 */
class ImageEditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }
    }

    /**
     * Tests that setImageType() and getImageType() correctly store
     * and return the output image format.
     *
     * @return void
     */
    public function testSetImageType(): void
    {
        $editor = new ImageEditor("dummy", "dummy");
        $editor->setImageType("png");
        $this->assertEquals("png", $editor->getImageType());
    }

    /**
     * Tests that setSize() can be called without throwing an exception.
     * The font size is stored internally for use with addText().
     *
     * @return void
     */
    public function testSetSize(): void
    {
        $editor = new ImageEditor("dummy", "dummy");
        $editor->setSize(18);
        // Size is stored internally, we verify no exception
        $this->assertTrue(true);
    }

    /**
     * Tests that setFont() can be called without throwing an exception.
     * The font path is stored internally for use with addText().
     *
     * @return void
     */
    public function testSetFont(): void
    {
        $editor = new ImageEditor("dummy", "dummy");
        $editor->setFont("/path/to/font.ttf");
        $this->assertTrue(true);
    }
}
