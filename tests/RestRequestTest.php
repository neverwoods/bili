<?php

namespace Bili\Tests;

use Bili\RestRequest;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for Bili\RestRequest.
 *
 * Covers construction (defaults, with URL/verb, with request body),
 * all getters and setters, flush(), post body building (array, object,
 * and invalid input), header building (with custom headers and null),
 * and execute() with an unsupported HTTP verb.
 *
 * Note: Actual HTTP execution (GET, POST, PUT, DELETE via curl) is not
 * tested to avoid network dependencies.
 *
 * @see RestRequest
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/RestRequestTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testConstructorDefaults tests/RestRequestTest.php
 */
class RestRequestTest extends TestCase
{
    /**
     * Tests that a default-constructed RestRequest has null URL, "GET" verb,
     * "application/json" accept type, and null for credentials and response.
     *
     * @return void
     */
    public function testConstructorDefaults(): void
    {
        $req = new RestRequest();
        $this->assertNull($req->getUrl());
        $this->assertEquals("GET", $req->getVerb());
        $this->assertEquals("application/json", $req->getAcceptType());
        $this->assertNull($req->getUsername());
        $this->assertNull($req->getPassword());
        $this->assertNull($req->getResponseBody());
        $this->assertNull($req->getResponseInfo());
    }

    /**
     * Tests that the constructor correctly stores a URL and verb.
     *
     * @return void
     */
    public function testConstructorWithParams(): void
    {
        $req = new RestRequest("http://example.com", "POST");
        $this->assertEquals("http://example.com", $req->getUrl());
        $this->assertEquals("POST", $req->getVerb());
    }

    /**
     * Tests that the constructor accepts a request body array,
     * which triggers buildPostBody() internally.
     *
     * @return void
     */
    public function testConstructorWithBody(): void
    {
        $req = new RestRequest("http://example.com", "POST", ["key" => "value"]);
        $this->assertEquals("http://example.com", $req->getUrl());
        $this->assertEquals("POST", $req->getVerb());
    }

    /**
     * Tests that all setters correctly update their corresponding getters.
     *
     * @return void
     */
    public function testSettersAndGetters(): void
    {
        $req = new RestRequest();

        $req->setUrl("http://test.com");
        $this->assertEquals("http://test.com", $req->getUrl());

        $req->setVerb("PUT");
        $this->assertEquals("PUT", $req->getVerb());

        $req->setAcceptType("text/xml");
        $this->assertEquals("text/xml", $req->getAcceptType());

        $req->setUsername("user");
        $this->assertEquals("user", $req->getUsername());

        $req->setPassword("pass");
        $this->assertEquals("pass", $req->getPassword());
    }

    /**
     * Tests that flush() resets the request body, response body,
     * response info, and verb back to "GET".
     *
     * @return void
     */
    public function testFlush(): void
    {
        $req = new RestRequest("http://example.com", "POST");
        $req->flush();

        $this->assertNull($req->getResponseBody());
        $this->assertNull($req->getResponseInfo());
        $this->assertEquals("GET", $req->getVerb());
    }

    /**
     * Tests that buildPostBody() accepts an associative array
     * and URL-encodes it as the request body.
     *
     * @return void
     */
    public function testBuildPostBodyWithArray(): void
    {
        $req = new RestRequest();
        $req->buildPostBody(["foo" => "bar", "baz" => "qux"]);
        // Should not throw
        $this->assertTrue(true);
    }

    /**
     * Tests that buildPostBody() accepts a stdClass object.
     *
     * @return void
     */
    public function testBuildPostBodyWithObject(): void
    {
        $req = new RestRequest();
        $obj = new stdClass();
        $obj->foo = "bar";
        $req->buildPostBody($obj);
        $this->assertTrue(true);
    }

    /**
     * Tests that buildPostBody() throws an InvalidArgumentException
     * when given a non-array/non-object value (e.g. a plain string).
     *
     * @return void
     */
    public function testBuildPostBodyWithInvalidDataThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $req = new RestRequest();
        $req->buildPostBody("string data");
    }

    /**
     * Tests that buildHeaders() returns an array of "Key: Value" strings
     * from the custom request headers, plus the Accept header.
     *
     * @return void
     */
    public function testBuildHeaders(): void
    {
        $req = new RestRequest("http://example.com", "GET", null, [
            "Authorization" => "Bearer token123",
            "X-Custom" => "value",
        ]);

        $headers = $req->buildHeaders();
        $this->assertContains("Authorization: Bearer token123", $headers);
        $this->assertContains("X-Custom: value", $headers);
        $this->assertContains("Accept: application/json", $headers);
    }

    /**
     * Tests that buildHeaders() with no custom headers (null) still
     * returns the Accept header.
     *
     * @return void
     */
    public function testBuildHeadersWithNull(): void
    {
        $req = new RestRequest();
        $headers = $req->buildHeaders();
        $this->assertContains("Accept: application/json", $headers);
    }

    /**
     * Tests that execute() throws an InvalidArgumentException for an
     * unsupported HTTP verb (e.g. "PATCH" is not handled by the switch).
     *
     * @return void
     * @throws InvalidArgumentException|Exception
     */
    public function testExecuteInvalidVerb(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $req = new RestRequest("http://example.com", "PATCH");
        $req->execute();
    }
}
