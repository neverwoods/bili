<?php

namespace Bili\Tests;

use Bili\Display;
use Bili\Language;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\Display.
 *
 * Covers HTML link rendering (internal, external, with CSS classes),
 * text wrapping, byte unit conversion (B/KB/MB/GB in both directions),
 * string shortening (with and without word preservation, custom append),
 * XML filtering, JavaScript filtering (escaping and tag stripping),
 * first-paragraph extraction, and singular/plural formatting.
 *
 * @see Display
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/DisplayTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testRenderLink tests/DisplayTest.php
 */
class DisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        setlocale(LC_ALL, 'en_US.UTF-8');
        $objLanguage = Language::singleton("english-utf-8", __DIR__ . '/languages/');
        $objLanguage->setLocale();
    }

    /**
     * Tests that renderLink() generates a basic <a> tag with href and label.
     *
     * @return void
     */
    public function testRenderLink(): void
    {
        $this->assertEquals(
            '<a href="/test">Click me</a>',
            Display::renderLink("Click me", "/test")
        );
    }

    /**
     * Tests that renderLink() adds rel="external" when the external flag is true.
     *
     * @return void
     */
    public function testRenderLinkExternal(): void
    {
        $this->assertEquals(
            '<a href="/test" rel="external">Click</a>',
            Display::renderLink("Click", "/test", true)
        );
    }

    /**
     * Tests that renderLink() adds a CSS class attribute when provided.
     *
     * @return void
     */
    public function testRenderLinkWithClass(): void
    {
        $this->assertEquals(
            '<a href="/test" class="btn">Click</a>',
            Display::renderLink("Click", "/test", false, "btn")
        );
    }

    /**
     * Tests that renderLink() combines both rel="external" and a CSS class.
     *
     * @return void
     */
    public function testRenderLinkExternalWithClass(): void
    {
        $this->assertEquals(
            '<a href="/test" rel="external" class="btn">Click</a>',
            Display::renderLink("Click", "/test", true, "btn")
        );
    }

    /**
     * Tests that wrapText() inserts <br /> tags at the specified character width.
     *
     * @return void
     */
    public function testWrapText(): void
    {
        $this->assertEquals("hello<br />world", Display::wrapText("hello world", 5));
    }

    /**
     * Tests that renderBytes() correctly converts bytes to KB, MB, and GB.
     *
     * @return void
     */
    public function testRenderBytes(): void
    {
        $this->assertEquals(1.0, Display::renderBytes(1024, "KB"));
        $this->assertEquals(1.0, Display::renderBytes(1048576, "MB"));
        $this->assertEquals(1.0, Display::renderBytes(1073741824, "GB"));
    }

    /**
     * Tests that renderBytes() converts from KB source unit to MB target.
     *
     * @return void
     */
    public function testRenderBytesFromKB(): void
    {
        $this->assertEquals(1.0, Display::renderBytes(1024, "MB", "KB"));
    }

    /**
     * Tests that renderBytes() converts from MB source unit to GB target.
     *
     * @return void
     */
    public function testRenderBytesFromMB(): void
    {
        $this->assertEquals(1.0, Display::renderBytes(1024, "GB", "MB"));
    }

    /**
     * Tests that renderBytes() converts from GB source unit back to bytes.
     *
     * @return void
     */
    public function testRenderBytesFromGB(): void
    {
        $this->assertEquals(1073741824.0, Display::renderBytes(1, "B", "GB"));
    }

    /**
     * Tests that getShortValue() truncates a long string to the given length
     * and appends the default " ..." suffix, preserving whole words.
     *
     * @return void
     */
    public function testGetShortValue(): void
    {
        $longText = "This is a long text that should be shortened to a specific length";
        $result = Display::getShortValue($longText, 20);
        $this->assertLessThanOrEqual(25, strlen($result)); // 20 + " ..."
        $this->assertStringEndsWith(" ...", $result);
    }

    /**
     * Tests that getShortValue() returns the full string unchanged
     * when it is shorter than the specified character limit.
     *
     * @return void
     */
    public function testGetShortValueNoTruncation(): void
    {
        $shortText = "Short";
        $this->assertEquals("Short", Display::getShortValue($shortText, 200));
    }

    /**
     * Tests that getShortValue() with blnPreserveWord=false cuts at the
     * exact character position without preserving word boundaries.
     *
     * @return void
     */
    public function testGetShortValueNoPreserveWord(): void
    {
        $text = "This is a test text for truncation";
        $result = Display::getShortValue($text, 10, false);
        $this->assertEquals("This is a  ...", $result);
    }

    /**
     * Tests that getShortValue() uses a custom append string instead of " ...".
     *
     * @return void
     */
    public function testGetShortValueCustomAppend(): void
    {
        $text = "This is a long text that should be shortened";
        $result = Display::getShortValue($text, 10, true, "…");
        $this->assertStringEndsWith("…", $result);
    }

    /**
     * Tests that filterForXML() encodes all five XML special characters
     * (&, <, >, ", ') to their entity equivalents.
     *
     * @return void
     */
    public function testFilterForXML(): void
    {
        $this->assertEquals("&amp;", Display::filterForXML("&"));
        $this->assertEquals("&lt;", Display::filterForXML("<"));
        $this->assertEquals("&gt;", Display::filterForXML(">"));
        $this->assertEquals("&quot;", Display::filterForXML("\""));
        $this->assertEquals("&apos;", Display::filterForXML("'"));
    }

    /**
     * Tests that filterForXML() handles already-encoded entities correctly.
     * html_entity_decode converts &amp; back to &, then it gets re-encoded to &amp;.
     *
     * @return void
     */
    public function testFilterForXMLPreservesEntities(): void
    {
        $this->assertEquals("&amp;", Display::filterForXML("&amp;"));
    }

    /**
     * Tests that filterForJavascript() JSON-encodes a string, escaping
     * double quotes for safe use in JavaScript.
     *
     * @return void
     */
    public function testFilterForJavascript(): void
    {
        $result = Display::filterForJavascript("Hello \"world\"");
        $this->assertEquals('"Hello \"world\""', $result);
    }

    /**
     * Tests that filterForJavascript() strips HTML tags (except allowed ones)
     * to prevent XSS in JavaScript contexts.
     *
     * @return void
     * @throws JsonException
     */
    public function testFilterForJavascriptStripsTags(): void
    {
        $result = Display::filterForJavascript("<script>alert('xss')</script>Hello");
        $decoded = json_decode($result, false, 512, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString("<script>", $decoded);
    }

    /**
     * Tests that getFirstParagraph() extracts the first <p>...</p> element
     * from an HTML string, ignoring any preceding non-paragraph content.
     *
     * @return void
     */
    public function testGetFirstParagraph(): void
    {
        $html = "<strong>hello</strong><p>Cool</p><p>Awesome longer text!</p>";
        $this->assertEquals("<p>Cool</p>", Display::getFirstParagraph($html));
    }

    /**
     * Tests that singularOrPlural() returns the singular form for 1
     * and the plural form for 0 or any amount other than 1.
     *
     * @return void
     */
    public function testSingularOrPlural(): void
    {
        $this->assertEquals("1 item", Display::singularOrPlural(1, "item", "items"));
        $this->assertEquals("0 items", Display::singularOrPlural(0, "item", "items"));
        $this->assertEquals("5 items", Display::singularOrPlural(5, "item", "items"));
    }
}
