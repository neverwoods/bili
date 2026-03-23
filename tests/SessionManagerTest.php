<?php

namespace Bili\Tests;

use Bili\SessionManager;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\SessionManager.
 *
 * Covers the static setData/getData session helpers (set, overwrite,
 * remove via null, get non-existent) and the static unserialize() method
 * for PHP-format session strings.
 *
 * Note: The singleton(), validate(), read(), write(), destroy(), gc(),
 * and fingerprint methods are not tested here as they require an active
 * PHP session and a dependency-injected session storage class.
 *
 * @see SessionManager
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/SessionManagerTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testSetAndGetData tests/SessionManagerTest.php
 */
class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Tests that setData() stores a value in $_SESSION and getData() retrieves it.
     *
     * @return void
     */
    public function testSetAndGetData(): void
    {
        SessionManager::setData("key", "value");
        $this->assertEquals("value", SessionManager::getData("key"));
    }

    /**
     * Tests that setData() with a null value removes the key from $_SESSION.
     *
     * @return void
     */
    public function testSetDataNull(): void
    {
        $_SESSION["toRemove"] = "exists";
        SessionManager::setData("toRemove", null);
        $this->assertArrayNotHasKey("toRemove", $_SESSION);
    }

    /**
     * Tests that getData() returns null for a key that does not exist in $_SESSION.
     *
     * @return void
     */
    public function testGetDataNonExistent(): void
    {
        $this->assertNull(SessionManager::getData("nonexistent"));
    }

    /**
     * Tests that calling setData() twice for the same key overwrites the previous value.
     *
     * @return void
     */
    public function testSetDataOverwrite(): void
    {
        SessionManager::setData("key", "first");
        SessionManager::setData("key", "second");
        $this->assertEquals("second", SessionManager::getData("key"));
    }

    /**
     * Tests that unserialize() can parse a PHP-format session serialized string
     * (key|serialized_value pairs separated by semicolons).
     *
     * @return void
     * @throws Exception
     */
    public function testUnserializePhp(): void
    {
        // Build a proper PHP session serialized string
        // Each key-value pair is: key|serialized_value
        $data = 'name|s:5:"hello";count|i:42;';
        // Use @ to suppress the unserialize warning in this test
        $result = @SessionManager::unserialize($data);

        $this->assertIsArray($result);
        $this->assertEquals("hello", $result['name']);
    }
}
