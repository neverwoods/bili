<?php

namespace Bili\Tests;

use Bili\JSIncluder;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\JSIncluder.
 *
 * Covers construction (empty, with array, with string), adding JS class files,
 * error handling for missing files, HTML <script> tag output generation,
 * comma-separated file lists, and version string appending.
 *
 * Note: The static render() method is not tested as it requires globals,
 * HTTP headers, and the Cache_Lite / JSMin dependencies.
 *
 * @see JSIncluder
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/JSIncluderTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testAddAndToHtml tests/JSIncluderTest.php
 */
class JSIncluderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/bili_js_test_' . uniqid('', true) . '/';
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tempDir . '*'));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    /**
     * Tests that a newly created JSIncluder with no files produces empty HTML.
     *
     * @return void
     */
    public function testConstructorEmpty(): void
    {
        $includer = new JSIncluder($this->tempDir);
        $this->assertEquals("", $includer->toHtml());
    }

    /**
     * Tests that add() registers a JS file and toHtml() generates
     * a <script> tag referencing it.
     *
     * @return void
     * @throws Exception
     */
    public function testAddAndToHtml(): void
    {
        file_put_contents($this->tempDir . 'app.js', 'var x=1;');

        $includer = new JSIncluder($this->tempDir);
        $includer->add("app");

        $html = $includer->toHtml();
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('app', $html);
    }

    /**
     * Tests that add() throws an exception when the referenced
     * JS file does not exist on disk.
     *
     * @return void
     */
    public function testAddMissingFileThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not found');

        $includer = new JSIncluder($this->tempDir);
        $includer->add("nonexistent");
    }

    /**
     * Tests that the constructor accepts an array of class names
     * and registers them all.
     *
     * @return void
     */
    public function testConstructorWithArray(): void
    {
        file_put_contents($this->tempDir . 'one.js', 'var a;');
        file_put_contents($this->tempDir . 'two.js', 'var b;');

        $includer = new JSIncluder($this->tempDir, ["one", "two"]);
        $html = $includer->toHtml();
        $this->assertStringContainsString('one', $html);
        $this->assertStringContainsString('two', $html);
    }

    /**
     * Tests that the constructor accepts a single class name string
     * and registers it.
     *
     * @return void
     */
    public function testConstructorWithString(): void
    {
        file_put_contents($this->tempDir . 'main.js', 'var x;');

        $includer = new JSIncluder($this->tempDir, "main");
        $html = $includer->toHtml();
        $this->assertStringContainsString('main', $html);
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
        file_put_contents($this->tempDir . 'app.js', 'var x;');

        $includer = new JSIncluder($this->tempDir);
        $includer->add("app");

        $html = $includer->toHtml("2.0");
        $this->assertStringContainsString('version-2.0', $html);
    }

    /**
     * Tests that multiple added files are joined with commas in the
     * script src query string (e.g. "/js?a,b").
     *
     * @return void
     * @throws Exception
     */
    public function testMultipleFiles(): void
    {
        file_put_contents($this->tempDir . 'a.js', '');
        file_put_contents($this->tempDir . 'b.js', '');

        $includer = new JSIncluder($this->tempDir);
        $includer->add("a");
        $includer->add("b");

        $html = $includer->toHtml();
        $this->assertStringContainsString('a,b', $html);
    }
}
