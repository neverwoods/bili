<?php

namespace Bili\Tests;

use Bili\Select2Helper;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\Select2Helper.
 *
 * Covers the Select2 AJAX helper: factory method, search value retrieval
 * (raw and SQL-wrapped), pagination (page number and page length with defaults),
 * row building, total row count, and JSON output.
 *
 * @see Select2Helper
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/Select2HelperTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testAddRowAndToJson tests/Select2HelperTest.php
 */
class Select2HelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
        parent::tearDown();
    }

    /**
     * Tests that getInitialServerResponse() returns a fresh Select2Helper instance.
     *
     * @return void
     */
    public function testGetInitialServerResponse(): void
    {
        $helper = Select2Helper::getInitialServerResponse();
        $this->assertInstanceOf(Select2Helper::class, $helper);
    }

    /**
     * Tests that getSearchValue() reads the "q" parameter from the request,
     * which is the default query parameter name used by Select2.
     *
     * @return void
     */
    public function testGetSearchValue(): void
    {
        $_REQUEST['q'] = 'search term';
        $this->assertEquals('search term', Select2Helper::getSearchValue());
    }

    /**
     * Tests that getSqlSearchValue() wraps the search value with SQL LIKE wildcards.
     *
     * @return void
     */
    public function testGetSqlSearchValue(): void
    {
        $_REQUEST['q'] = 'test';
        $this->assertEquals('%test%', Select2Helper::getSqlSearchValue());
    }

    /**
     * Tests that getPage() reads the "page" parameter from the request.
     *
     * @return void
     */
    public function testGetPage(): void
    {
        $_REQUEST['page'] = '3';
        $this->assertEquals('3', Select2Helper::getPage());
    }

    /**
     * Tests that getPage() defaults to 1 when the "page" parameter is absent.
     *
     * @return void
     */
    public function testGetPageDefault(): void
    {
        $this->assertEquals(1, Select2Helper::getPage());
    }

    /**
     * Tests that getPageLength() reads the "per" parameter from the request.
     *
     * @return void
     */
    public function testGetPageLength(): void
    {
        $_REQUEST['per'] = '25';
        $this->assertEquals('25', Select2Helper::getPageLength());
    }

    /**
     * Tests that getPageLength() defaults to 10 when the "per" parameter is absent.
     *
     * @return void
     */
    public function testGetPageLengthDefault(): void
    {
        $this->assertEquals(10, Select2Helper::getPageLength());
    }

    /**
     * Tests that addRow(), setTotalRows(), and toJson() produce the correct
     * JSON structure with rows and total count for Select2 consumption.
     *
     * @return void
     * @throws JsonException
     */
    public function testAddRowAndToJson(): void
    {
        $helper = Select2Helper::getInitialServerResponse();
        $helper->addRow(["id" => 1, "text" => "Option 1"]);
        $helper->addRow(["id" => 2, "text" => "Option 2"]);
        $helper->setTotalRows(2);

        $json = $helper->toJson();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(2, $data['total']);
        $this->assertCount(2, $data['rows']);
        $this->assertEquals(1, $data['rows'][0]['id']);
        $this->assertEquals("Option 2", $data['rows'][1]['text']);
    }

    /**
     * Tests that a freshly created helper produces empty rows and zero total in JSON.
     *
     * @return void
     * @throws JsonException
     */
    public function testEmptyToJson(): void
    {
        $helper = Select2Helper::getInitialServerResponse();
        $json = $helper->toJson();
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(0, $data['total']);
        $this->assertEmpty($data['rows']);
    }
}
