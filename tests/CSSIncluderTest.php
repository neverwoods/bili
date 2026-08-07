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
 * Also covers @import registration (the "import"/"layer" keys on add(),
 * rendered as an `@import url(...) layer(<name>);` style element) and the
 * getOutput() concatenation seam using temporary CSS files and globals.
 *
 * Note: The static render() method is not tested as it requires
 * HTTP headers to be sent.
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
        unset($GLOBALS["_PATHS"]["css"], $GLOBALS["_CONF"]["cache"]);
        array_map('unlink', glob($this->tempDir . '*'));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    /**
     * Point the globals used by getOutput() at the temp directory,
     * with result caching disabled.
     *
     * @return void
     */
    protected function setUpOutputGlobals(): void
    {
        $GLOBALS["_PATHS"]["css"] = $this->tempDir;
        $GLOBALS["_CONF"]["cache"] = [
            "caching" => false,
            "cacheDir" => $this->tempDir,
        ];
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

    /**
     * Tests that getOutput() returns the concatenated CSS of the requested
     * files, including the auto-appended "-custom" companion.
     *
     * @return void
     */
    public function testGetOutputConcatenatesFilesWithCustomCompanion(): void
    {
        $this->setUpOutputGlobals();
        file_put_contents($this->tempDir . 'style.css', '.base {}');
        file_put_contents($this->tempDir . 'style-custom.css', '.custom {}');
        file_put_contents($this->tempDir . 'other.css', '.other {}');

        $output = CSSIncluder::getOutput(["style", "other"]);
        $this->assertStringContainsString('.base', $output);
        $this->assertStringContainsString('.custom', $output);
        $this->assertStringContainsString('.other', $output);
    }

    /**
     * Tests that import entries with a layer are emitted as an @import with
     * the layer() function inside a style element, while link entries keep
     * the plain link — and that the link comes first.
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlRendersImportEntriesAsImportStyle(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');
        file_put_contents($this->tempDir . 'other.css', 'body{}');
        file_put_contents($this->tempDir . 'plain.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir, [
            ["href" => "plain", "media" => "all"],
            ["href" => "style", "media" => "all", "import" => true, "layer" => "legacy"],
            ["href" => "other", "media" => "all", "import" => true, "layer" => "legacy"],
        ]);

        $html = $includer->toHtml();
        $this->assertStringContainsString('href="/css?plain"', $html);
        $this->assertStringContainsString(
            '<style media="all">@import url("/css?style,other") layer(legacy);</style>',
            $html
        );
        $this->assertLessThan(
            strpos($html, '<style'),
            strpos($html, '<link'),
            'The link element must precede the import style element.'
        );
    }

    /**
     * Tests that every import URL is preceded by a preload link with the
     * identical URL, so the browser fetches it in parallel.
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlPreloadsImportUrls(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir, [
            ["href" => "style", "media" => "all", "import" => true, "layer" => "legacy"],
        ]);

        $html = $includer->toHtml("1.0.0");
        $strPreload = '<link rel="preload" href="/css?style,version-1.0.0" as="style" media="all" />';
        $this->assertStringContainsString($strPreload, $html);
        $this->assertLessThan(
            strpos($html, '@import'),
            strpos($html, $strPreload),
            'The preload link must precede the import.'
        );
    }

    /**
     * Tests that import entries in different layers get one @import per
     * layer, and an import without a layer omits the layer() function.
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlGroupsImportsPerLayer(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');
        file_put_contents($this->tempDir . 'other.css', 'body{}');
        file_put_contents($this->tempDir . 'free.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir, [
            ["href" => "style", "media" => "all", "import" => true, "layer" => "legacy"],
            ["href" => "other", "media" => "all", "import" => true, "layer" => "vendor"],
            ["href" => "free", "media" => "all", "import" => true],
        ]);

        $html = $includer->toHtml();
        $this->assertStringContainsString('@import url("/css?style") layer(legacy);', $html);
        $this->assertStringContainsString('@import url("/css?other") layer(vendor);', $html);
        $this->assertStringContainsString('@import url("/css?free");', $html);
    }

    /**
     * Tests that the version token is appended to the import URL like it
     * is to the link URL.
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlAppendsVersionToImportUrl(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir, [
            ["href" => "style", "media" => "all", "import" => true, "layer" => "legacy"],
        ]);

        $this->assertStringContainsString(
            '@import url("/css?style,version-1.0.0") layer(legacy);',
            $includer->toHtml("1.0.0")
        );
    }

    /**
     * Tests that import entries respect their media bucket via the style
     * element's media attribute.
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlKeepsMediaBucketOnImport(): void
    {
        file_put_contents($this->tempDir . 'print.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir, [
            ["href" => "print", "media" => "print", "import" => true, "layer" => "legacy"],
        ]);

        $this->assertStringContainsString(
            '<style media="print">@import url("/css?print") layer(legacy);</style>',
            $includer->toHtml()
        );
    }

    /**
     * Tests that a link without any import entry stays byte-identical to
     * the previous format (no style element emitted).
     *
     * @return void
     * @throws Exception
     */
    public function testToHtmlWithoutImportsKeepsPlainLinkOnly(): void
    {
        file_put_contents($this->tempDir . 'style.css', 'body{}');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "style", "media" => "all"]);

        $html = $includer->toHtml();
        $this->assertStringContainsString('href="/css?style"', $html);
        $this->assertStringNotContainsString('<style', $html);
    }

    /**
     * Tests that an import entry referencing a missing file throws like a
     * link entry does.
     *
     * @return void
     */
    public function testAddImportMissingFileThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not found');

        $includer = new CSSIncluder($this->tempDir);
        $includer->add(["href" => "nonexistent", "media" => "all", "import" => true]);
    }
}
