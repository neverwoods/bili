<?php

namespace Bili\Tests;

use Bili\Rewrite;
use Bili\Crypt;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\Rewrite.
 *
 * Covers URL encoding/decoding (single values and arrays via Crypt),
 * singleton instantiation and getInstance(), and the URL generation
 * method getUrl() with various combinations of section, subsection,
 * command, element (encoded), department (encoded), parameters
 * (with reserved parameter filtering), parse type, and fragment.
 *
 * Note: The URL parsing from $_REQUEST (getRewrite) is not tested here
 * because it calls Request::redirect() with exit() when no rewrite is set.
 *
 * @see Rewrite
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/RewriteTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testGetUrl tests/RewriteTest.php
 */
class RewriteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_REQUEST = [];
        // Reset the singleton
        Rewrite::$instance = null;
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        Rewrite::$instance = null;
        parent::tearDown();
    }

    /**
     * Tests that Rewrite::encode() encodes a single integer via Crypt::doEncode().
     *
     * @return void
     */
    public function testEncodeSingle(): void
    {
        $encoded = Rewrite::encode(1);
        $this->assertEquals(Crypt::doEncode(1), $encoded);
    }

    /**
     * Tests that Rewrite::decode() decodes a single encoded value back to its integer.
     *
     * @return void
     */
    public function testDecodeSingle(): void
    {
        $encoded = Crypt::doEncode(42);
        $decoded = Rewrite::decode($encoded);
        $this->assertEquals(42, $decoded);
    }

    /**
     * Tests that Rewrite::encode() encodes each element when given an array.
     *
     * @return void
     */
    public function testEncodeArray(): void
    {
        $result = Rewrite::encode([1, 2, 3]);
        $this->assertIsArray($result);
        $this->assertEquals(Crypt::doEncode(1), $result[0]);
        $this->assertEquals(Crypt::doEncode(2), $result[1]);
        $this->assertEquals(Crypt::doEncode(3), $result[2]);
    }

    /**
     * Tests that Rewrite::decode() decodes each element when given an array.
     *
     * @return void
     */
    public function testDecodeArray(): void
    {
        $encoded = [Crypt::doEncode(10), Crypt::doEncode(20)];
        $result = Rewrite::decode($encoded);
        $this->assertIsArray($result);
        $this->assertEquals(10, $result[0]);
        $this->assertEquals(20, $result[1]);
    }

    /**
     * Tests that singleton() creates an instance with all configuration
     * and that getInstance() returns the same instance.
     *
     * @return void
     */
    public function testSingleton(): void
    {
        $sections = ["home" => 1, "about" => 2];
        $subsections = ["sub1" => 10];
        $commands = ["edit" => 100, "delete" => 101];
        $parseTypes = ["json" => "json", "" => "html"];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes, 1, 10, 100, "html");

        $this->assertInstanceOf(Rewrite::class, $rewrite);
        $this->assertSame($rewrite, Rewrite::getInstance());
    }

    /**
     * Tests that getInstance() returns a Rewrite instance even when
     * singleton() has not been called (lazy initialization).
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $instance = Rewrite::getInstance();
        $this->assertInstanceOf(Rewrite::class, $instance);
    }

    /**
     * Tests that getUrl() generates a section-only URL (e.g. "/home").
     *
     * @return void
     */
    public function testGetUrl(): void
    {
        $sections = ["home" => 1, "about" => 2];
        $subsections = [];
        $commands = ["edit" => 100];
        $parseTypes = ["json" => "json", "" => "html"];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1);
        $this->assertEquals("/home", $url);
    }

    /**
     * Tests that getUrl() appends a command segment (e.g. "/home/edit").
     *
     * @return void
     */
    public function testGetUrlWithCommand(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = ["edit" => 100];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, 100);
        $this->assertEquals("/home/edit", $url);
    }

    /**
     * Tests that getUrl() encodes numeric elements via Crypt::doEncode()
     * and appends them to the URL (e.g. "/home/edit/{encoded_42}").
     *
     * @return void
     */
    public function testGetUrlWithElement(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = ["edit" => 100];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, 100, 42);
        $encoded42 = Crypt::doEncode(42);
        $this->assertEquals("/home/edit/{$encoded42}", $url);
    }

    /**
     * Tests that getUrl() inserts a subsection segment between the
     * section and command (e.g. "/home/settings").
     *
     * @return void
     */
    public function testGetUrlWithSubSection(): void
    {
        $sections = ["home" => 1];
        $subsections = ["settings" => 10];
        $commands = [];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, null, null, null, 10);
        $this->assertEquals("/home/settings", $url);
    }

    /**
     * Tests that getUrl() prepends an encoded department ID segment
     * before the section (e.g. "/{encoded_5}/home").
     *
     * @return void
     */
    public function testGetUrlWithDepartment(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = [];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, null, null, null, null, null, 5);
        $encoded5 = Crypt::doEncode(5);
        $this->assertEquals("/{$encoded5}/home", $url);
    }

    /**
     * Tests that getUrl() appends a "/view/{type}" segment for the parse type.
     *
     * @return void
     */
    public function testGetUrlWithParseType(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = [];
        $parseTypes = ["json" => "json", "" => "html"];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, null, null, "json");
        $this->assertEquals("/home/view/json", $url);
    }

    /**
     * Tests that getUrl() appends key/value parameter pairs to the URL.
     *
     * @return void
     */
    public function testGetUrlWithParameters(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = [];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, null, null, null, null, ["filter" => "active"]);
        $this->assertEquals("/home/filter/active", $url);
    }

    /**
     * Tests that getUrl() appends a URL fragment (e.g. "#section1") at the end.
     *
     * @return void
     */
    public function testGetUrlWithFragment(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = [];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, null, null, null, null, null, null, "#section1");
        $this->assertEquals("/home#section1", $url);
    }

    /**
     * Tests that getUrl() filters out reserved parameters (e.g. "view")
     * while keeping other parameters intact.
     *
     * @return void
     */
    public function testGetUrlReservedParametersFiltered(): void
    {
        $sections = ["home" => 1];
        $subsections = [];
        $commands = [];
        $parseTypes = [];

        $rewrite = Rewrite::singleton($sections, $subsections, $commands, $parseTypes);

        $url = $rewrite->getUrl(1, null, null, null, null, ["view" => "json", "filter" => "active"]);
        $this->assertStringNotContainsString("/view/", $url);
        $this->assertStringContainsString("/filter/active", $url);
    }

    /**
     * Tests that setDefaultSection/SubSection/Command/Parser store values
     * that can be retrieved via getDefaultSection().
     *
     * @return void
     */
    public function testSettersAndDefaults(): void
    {
        $rewrite = Rewrite::singleton([], [], [], []);
        $rewrite->setDefaultSection(1);
        $rewrite->setDefaultSubSection(10);
        $rewrite->setDefaultCommand(100);
        $rewrite->setDefaultParser("html");

        $this->assertEquals(1, $rewrite->getDefaultSection());
    }
}
