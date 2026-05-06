<?php

namespace Bili\Tests;

use Bili\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\Request.
 *
 * Covers HTTP method detection, protocol detection (HTTP/HTTPS including
 * X-Forwarded-Proto), root and sub URI construction, query string variable
 * parsing, the $_REQUEST parameter getter with fallback defaults,
 * and all class constants.
 *
 * @see Request
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/RequestTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testGetMethod tests/RequestTest.php
 */
class RequestTest extends TestCase
{
    private array $originalServer;
    private array $originalRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
        $this->originalRequest = $_REQUEST ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_REQUEST = $this->originalRequest;
        parent::tearDown();
    }

    /**
     * Tests that getMethod() returns the uppercased HTTP request method from $_SERVER.
     *
     * @return void
     */
    public function testGetMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('GET', Request::getMethod());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('POST', Request::getMethod());

        $_SERVER['REQUEST_METHOD'] = 'put';
        $this->assertEquals('PUT', Request::getMethod());
    }

    /**
     * Tests that getMethod() returns an empty string when REQUEST_METHOD is not set.
     *
     * @return void
     */
    public function testGetMethodEmpty(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertEquals('', Request::getMethod());
    }

    /**
     * Tests that getProtocol() returns "http" when neither HTTPS
     * nor HTTP_X_FORWARDED_PROTO indicate a secure connection.
     *
     * @return void
     */
    public function testGetProtocolHttp(): void
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $this->assertEquals('http', Request::getProtocol());
    }

    /**
     * Tests that getProtocol() returns "https" when $_SERVER['HTTPS'] is "on".
     *
     * @return void
     */
    public function testGetProtocolHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https', Request::getProtocol());
    }

    /**
     * Tests that getProtocol() returns "https" when behind a reverse proxy
     * that sets the HTTP_X_FORWARDED_PROTO header to "https".
     *
     * @return void
     */
    public function testGetProtocolHttpsForwarded(): void
    {
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertEquals('https', Request::getProtocol());
    }

    /**
     * Tests that getRootURI() returns protocol://host based on the current server globals.
     *
     * @return void
     */
    public function testGetRootURI(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $this->assertEquals('http://example.com', Request::getRootURI());
    }

    /**
     * Tests that getRootURI() returns an empty string when HTTP_HOST is not set.
     *
     * @return void
     */
    public function testGetRootURIEmpty(): void
    {
        unset($_SERVER['HTTP_HOST']);
        $this->assertEquals('', Request::getRootURI());
    }

    /**
     * Tests that getVar() parses a query string from a URL and returns
     * the value for a given variable name (case-insensitive match).
     *
     * Note: The source code uses array_pop(explode(...)) which triggers
     * a strict notice. The @ operator suppresses this.
     *
     * @return void
     */
    public function testGetVar(): void
    {
        $result = @Request::getVar("http://example.com?foo=hello&bar=world", "foo");
        $this->assertEquals("hello", $result);

        $result = @Request::getVar("http://example.com?foo=hello&bar=world", "bar");
        $this->assertEquals("world", $result);
    }

    /**
     * Tests that get() returns the value from $_REQUEST for an existing parameter.
     *
     * @return void
     */
    public function testGet(): void
    {
        $_REQUEST['testParam'] = 'testValue';
        $this->assertEquals('testValue', Request::get('testParam'));
    }

    /**
     * Tests that get() returns the fallback default when the parameter does not exist.
     *
     * @return void
     */
    public function testGetDefault(): void
    {
        unset($_REQUEST['nonExistent']);
        $this->assertEquals('default', Request::get('nonExistent', 'default'));
    }

    /**
     * Tests that get() returns the fallback default when the parameter
     * exists but is an empty string.
     *
     * @return void
     */
    public function testGetEmpty(): void
    {
        $_REQUEST['emptyParam'] = '';
        $this->assertEquals('fallback', Request::get('emptyParam', 'fallback'));
    }

    /**
     * Tests that get() returns numeric zero (int 0) instead of falling back
     * to the default, since 0 is a valid non-empty value.
     *
     * @return void
     */
    public function testGetNumericZero(): void
    {
        $_REQUEST['zeroParam'] = 0;
        $this->assertEquals(0, Request::get('zeroParam', 'fallback'));
    }

    /**
     * Tests that get() returns the string "0" instead of falling back
     * to the default, since "0" is a valid non-empty value.
     *
     * @return void
     */
    public function testGetNumericZeroString(): void
    {
        $_REQUEST['zeroParam'] = '0';
        $this->assertEquals('0', Request::get('zeroParam', 'fallback'));
    }

    /**
     * Tests that all HTTP method constants are defined with the expected values.
     *
     * @return void
     */
    public function testConstants(): void
    {
        $this->assertEquals('GET', Request::METHOD_GET);
        $this->assertEquals('POST', Request::METHOD_POST);
        $this->assertEquals('PUT', Request::METHOD_PUT);
        $this->assertEquals('OPTIONS', Request::METHOD_OPTIONS);
        $this->assertEquals('HEAD', Request::METHOD_HEAD);
        $this->assertEquals('DELETE', Request::METHOD_DELETE);
    }
}
