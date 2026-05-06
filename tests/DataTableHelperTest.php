<?php

namespace Bili\Tests;

use Bili\DataTableHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\DataTableHelper.
 *
 * Covers the server-side DataTables helper methods: initial response structure,
 * echo parameter, order column resolution (single and multi-column, with and
 * without whitelists), order direction (default, from request, per-column,
 * invalid values), search values (raw and SQL-wrapped), and pagination
 * (page number, start offset, page length with defaults).
 *
 * @see DataTableHelper
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/DataTableHelperTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testGetOrderColumn tests/DataTableHelperTest.php
 */
class DataTableHelperTest extends TestCase
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
     * Tests that getInitialServerResponse() returns the expected
     * DataTables server-side response structure with zero records.
     *
     * @return void
     */
    public function testGetInitialServerResponse(): void
    {
        $response = DataTableHelper::getInitialServerResponse();
        $this->assertIsArray($response);
        $this->assertEquals(0, $response['iTotalRecords']);
        $this->assertEquals(0, $response['iTotalDisplayRecords']);
        $this->assertArrayHasKey('sEcho', $response);
        $this->assertIsArray($response['aaData']);
        $this->assertEmpty($response['aaData']);
    }

    /**
     * Tests that getEcho() reads the "sEcho" parameter from the request.
     *
     * @return void
     */
    public function testGetEcho(): void
    {
        $_REQUEST['sEcho'] = '5';
        $this->assertEquals('5', DataTableHelper::getEcho());
    }

    /**
     * Tests that getOrderColumn() returns the default column
     * when no sorting columns are sent in the request.
     *
     * @return void
     */
    public function testGetOrderColumnDefault(): void
    {
        $this->assertEquals('name', DataTableHelper::getOrderColumn('name'));
    }

    /**
     * Tests that getOrderColumn() returns the column name from the request
     * when a sorting column is provided by the DataTables client.
     *
     * @return void
     */
    public function testGetOrderColumnFromRequest(): void
    {
        $_REQUEST['iSortingCols'] = '1';
        $_REQUEST['iSortCol_0'] = '0';
        $_REQUEST['mDataProp_0'] = 'email';

        $this->assertEquals('email', DataTableHelper::getOrderColumn('name'));
    }

    /**
     * Tests that getOrderColumn() respects the whitelist: a column in the
     * whitelist is accepted, while a column not in the whitelist falls back
     * to the default.
     *
     * @return void
     */
    public function testGetOrderColumnWithWhitelist(): void
    {
        $_REQUEST['iSortingCols'] = '1';
        $_REQUEST['iSortCol_0'] = '0';
        $_REQUEST['mDataProp_0'] = 'email';

        // Column is in whitelist
        $this->assertEquals('email', DataTableHelper::getOrderColumn('name', ['email', 'phone']));

        // Column is not in whitelist
        $this->assertEquals('name', DataTableHelper::getOrderColumn('name', ['phone', 'address']));
    }

    /**
     * Tests that getOrderColumns() returns all sorting columns sent by the client.
     *
     * @return void
     */
    public function testGetOrderColumns(): void
    {
        $_REQUEST['iSortingCols'] = '2';
        $_REQUEST['iSortCol_0'] = '0';
        $_REQUEST['mDataProp_0'] = 'name';
        $_REQUEST['iSortCol_1'] = '1';
        $_REQUEST['mDataProp_1'] = 'email';

        $columns = DataTableHelper::getOrderColumns('id');
        $this->assertEquals(['name', 'email'], $columns);
    }

    /**
     * Tests that hasOrderColumn() returns true when the given column string
     * is among the current order columns, and false otherwise.
     *
     * @return void
     */
    public function testHasOrderColumn(): void
    {
        $_REQUEST['iSortingCols'] = '1';
        $_REQUEST['iSortCol_0'] = '0';
        $_REQUEST['mDataProp_0'] = 'name';

        $this->assertTrue(DataTableHelper::hasOrderColumn('name', 'id'));
        $this->assertFalse(DataTableHelper::hasOrderColumn('email', 'id'));
    }

    /**
     * Tests that hasOrderColumn() accepts an array and returns true if any
     * of the given columns are among the current order columns.
     *
     * @return void
     */
    public function testHasOrderColumnArray(): void
    {
        $_REQUEST['iSortingCols'] = '1';
        $_REQUEST['iSortCol_0'] = '0';
        $_REQUEST['mDataProp_0'] = 'name';

        $this->assertTrue(DataTableHelper::hasOrderColumn(['name', 'email'], 'id'));
        $this->assertFalse(DataTableHelper::hasOrderColumn(['email', 'phone'], 'id'));
    }

    /**
     * Tests that getOrderDirection() returns the ucfirst'd sort direction
     * from the first sorting column in the request.
     *
     * @return void
     */
    public function testGetOrderDirection(): void
    {
        $_REQUEST['sSortDir_0'] = 'desc';
        $this->assertEquals('Desc', DataTableHelper::getOrderDirection('Asc'));
    }

    /**
     * Tests that getOrderDirection() returns the default direction
     * when no sort direction is present in the request.
     *
     * @return void
     */
    public function testGetOrderDirectionDefault(): void
    {
        $this->assertEquals('Asc', DataTableHelper::getOrderDirection('Asc'));
    }

    /**
     * Tests that getOrderDirection() falls back to the default when
     * the sort direction in the request is not a valid value ("Asc" or "Desc").
     *
     * @return void
     */
    public function testGetOrderDirectionInvalid(): void
    {
        $_REQUEST['sSortDir_0'] = 'invalid';
        $this->assertEquals('Asc', DataTableHelper::getOrderDirection('Asc'));
    }

    /**
     * Tests that getOrderDirection() can return the direction for a specific
     * column name when multiple sorting columns are present.
     *
     * @return void
     */
    public function testGetOrderDirectionForSpecificColumn(): void
    {
        $_REQUEST['iSortingCols'] = '2';
        $_REQUEST['iSortCol_0'] = '0';
        $_REQUEST['mDataProp_0'] = 'name';
        $_REQUEST['sSortDir_0'] = 'asc';
        $_REQUEST['iSortCol_1'] = '1';
        $_REQUEST['mDataProp_1'] = 'email';
        $_REQUEST['sSortDir_1'] = 'desc';

        $this->assertEquals('Desc', DataTableHelper::getOrderDirection('Asc', 'email'));
        $this->assertEquals('Asc', DataTableHelper::getOrderDirection('Desc', 'name'));
    }

    /**
     * Tests that getSearchValue() reads the "sSearch" parameter from the request.
     *
     * @return void
     */
    public function testGetSearchValue(): void
    {
        $_REQUEST['sSearch'] = 'test query';
        $this->assertEquals('test query', DataTableHelper::getSearchValue());
    }

    /**
     * Tests that getSqlSearchValue() wraps the search value with SQL LIKE wildcards.
     *
     * @return void
     */
    public function testGetSqlSearchValue(): void
    {
        $_REQUEST['sSearch'] = 'test';
        $this->assertEquals('%test%', DataTableHelper::getSqlSearchValue());
    }

    /**
     * Tests that getPage() calculates the correct page number from
     * iDisplayStart and iDisplayLength.
     *
     * @return void
     */
    public function testGetPage(): void
    {
        $_REQUEST['iDisplayStart'] = '20';
        $_REQUEST['iDisplayLength'] = '10';
        $this->assertEquals(3, DataTableHelper::getPage());
    }

    /**
     * Tests that getPage() returns 1 when no pagination parameters are present.
     *
     * @return void
     */
    public function testGetPageDefault(): void
    {
        $this->assertEquals(1, DataTableHelper::getPage());
    }

    /**
     * Tests that getPageStart() reads the "iDisplayStart" offset from the request.
     *
     * @return void
     */
    public function testGetPageStart(): void
    {
        $_REQUEST['iDisplayStart'] = '30';
        $this->assertEquals(30, DataTableHelper::getPageStart());
    }

    /**
     * Tests that getPageLength() reads the "iDisplayLength" parameter from the request.
     *
     * @return void
     */
    public function testGetPageLength(): void
    {
        $_REQUEST['iDisplayLength'] = '25';
        $this->assertEquals(25, DataTableHelper::getPageLength());
    }

    /**
     * Tests that getPageLength() defaults to 10 when the parameter is not in the request.
     *
     * @return void
     */
    public function testGetPageLengthDefault(): void
    {
        $this->assertEquals(10, DataTableHelper::getPageLength());
    }
}
