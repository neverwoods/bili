<?php

namespace Bili\Tests;

use Bili\CSSIncluder;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\CSSIncluder.
 *
 * Covers construction (empty, with single file, with multiple files),
 * adding stylesheets for different media types (all, screen, print),
 * error handling for invalid media types, missing files, and non-array input,
 * HTML output generation, and version string appending.
 *
 * Note: The static render() method is not tested as it requires globals,
 * HTTP headers, and the Cache_Lite / Minify_CSS dependencies.
 *
 * @see CSSIncluder
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/CSSIncluderTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testAddAndToHtml tests/CSSIncluderTest.php
 */
class CSSIncluderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/bili_css_test_' . uniqid('', true) . '/';
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '*'));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    /**
     * Tests that a newly created CSSIncluder with no files produces empty HTML.
     *
     * @return void
     */
    public function testConstructorEmpty(): void
    {
        $includer = new CSSIncluder($this->tempDir);
        $this->assertEquals("", $includer->toHtml());
    }

    /**
     * Tests that add() registers a stylesheet and toHtml() generates
     * a <link> tag with the correct href and media attribute.
     *
     * @return void
     * @throws Exception
     */
    public function testAddAndToHtml(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "style", "media" => "all"]);

        $html = $includer->toHtml();
        $this->assertStringContainsString('style', $html);
        $this->assertStringContainsString('media="all"', $html);
        $this->assertStringContainsString('<link', $html);
    }

    /**
     * Tests that a stylesheet added with media="screen" produces
     * the correct media attribute in the HTML output.
     *
     * @return void
     * @throws Exception
     */
    public function testAddScreenMedia(): void
    {
        file_put_contents($this->tempDir . 'screen.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "screen", "media" => "screen"]);

        $html = $includer->toHtml();
        $this->assertStringContainsString('media="screen"', $html);
    }

    /**
     * Tests that a stylesheet added with media="print" produces
     * the correct media attribute in the HTML output.
     *
     * @return void
     * @throws Exception
     */
    public function testAddPrintMedia(): void
    {
        file_put_contents($this->tempDir . 'print.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "print", "media" => "print"]);

        $html = $includer->toHtml();
        $this->assertStringContainsString('media="print"', $html);
    }

    /**
     * Tests that add() throws an exception when given a media type
     * that is not "all", "screen", or "print".
     *
     * @return void
     */
    public function testAddInvalidMediaThrows(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('media type');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "style", "media" => "invalid"]);
    }

    /**
     * Tests that add() throws an exception when the referenced
     * CSS file does not exist on disk.
     *
     * @return void
     */
    public function testAddMissingFileThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not found');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "nonexistent", "media" => "all"]);
    }

    /**
     * Tests that add() throws an exception when passed a non-array argument.
     *
     * @return void
     */
    public function testAddNonArrayThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('invalid stylesheet');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add("not-an-array");
    }

    /**
     * Tests that the constructor accepts a nested array of file definitions
     * and registers them all (single file wrapped in an outer array).
     *
     * @return void
     */
    public function testConstructorWithSingleFile(): void
    {
        file_put_contents($this->tempDir . 'main.css', 'body{}');

        // Constructor expects nested an array even for a single file
        $includer = new CSSIncluder($this->tempDir, [["href" => "main", "media" => "all"]]);
        $html = $includer->toHtml();
        $this->assertStringContainsString('main', $html);
    }

    /**
     * Tests that the constructor accepts multiple file definitions
     * and includes them all in the HTML output.
     *
     * @return void
     */
    public function testConstructorWithMultipleFiles(): void
    {
        file_put_contents($this->tempDir . 'one.css', 'body{}');
        file_put_contents($this->tempDir . 'two.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir, [
            ["href" => "one", "media" => "all"],
            ["href" => "two", "media" => "all"],
        ]);
        $html = $includer->toHtml();
        $this->assertStringContainsString('one', $html);
        $this->assertStringContainsString('two', $html);
    }

    /**
     * Tests that toHtml() appends a "version-{version}" cache-busting
     * parameter when a version string is provided.
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlWithVersion(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "style", "media" => "all"]);

        $html = $includer->toHtml("1.0.0");
        $this->assertStringContainsString('version-1.0.0', $html);
    }
}
