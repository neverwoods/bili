<?php

namespace Bili\Tests;

use BadMethodCallException;
use Bili\ClassDynamic;
use PHPUnit\Framework\TestCase;

/**
 * Test helper class that extends ClassDynamic to expose its magic method behavior.
 */
class ClassDynamicTestSubject extends ClassDynamic
{
    protected string $name;
    protected string $value;
    protected bool $active;

    public function __construct(string $name = "", string $value = "", bool $active = false)
    {
        $this->name = $name;
        $this->value = $value;
        $this->active = $active;
    }
}

/**
 * Tests for Bili\ClassDynamic.
 *
 * Verifies the magic __get, __set, and __call methods that provide
 * dynamic getter/setter access to protected properties in subclasses.
 *
 * @see ClassDynamic
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/ClassDynamicTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testGetProperty tests/ClassDynamicTest.php
 */
class ClassDynamicTest extends TestCase
{
    /**
     * Tests that __call-based getters (e.g. getName()) return the correct property values.
     *
     * @return void
     */
    public function testGetProperty(): void
    {
        $obj = new ClassDynamicTestSubject("test", "val", true);
        $this->assertEquals("test", $obj->getName());
        $this->assertEquals("val", $obj->getValue());
        $this->assertTrue($obj->getActive());
    }

    /**
     * Tests that __call-based setters (e.g. setName()) correctly update protected properties.
     *
     * @return void
     */
    public function testSetProperty(): void
    {
        $obj = new ClassDynamicTestSubject();
        $obj->setName("hello");
        $obj->setValue("world");
        $obj->setActive(true);

        $this->assertEquals("hello", $obj->getName());
        $this->assertEquals("world", $obj->getValue());
        $this->assertTrue($obj->getActive());
    }

    /**
     * Tests that __get allows direct property access (e.g. $obj->name) on protected properties.
     *
     * @return void
     */
    public function testMagicGet(): void
    {
        $obj = new ClassDynamicTestSubject("foo", "bar", false);
        $this->assertEquals("foo", $obj->name);
        $this->assertEquals("bar", $obj->value);
        $this->assertFalse($obj->active);
    }

    /**
     * Tests that __set allows direct property assignment (e.g. $obj->name = "test")
     * on protected properties.
     *
     * @return void
     */
    public function testMagicSet(): void
    {
        $obj = new ClassDynamicTestSubject();
        $obj->name = "test";
        $obj->value = "abc";

        $this->assertEquals("test", $obj->getName());
        $this->assertEquals("abc", $obj->getValue());
    }

    /**
     * Tests that __get throws a BadMethodCallException when accessing a non-existent property.
     *
     * @return void
     */
    public function testGetNonExistentPropertyThrows(): void
    {
        $this->expectException(BadMethodCallException::class);
        $obj = new ClassDynamicTestSubject();
        $obj->nonExistent;
    }

    /**
     * Tests that __set throws a BadMethodCallException when assigning to a non-existent property.
     *
     * @return void
     */
    public function testSetNonExistentPropertyThrows(): void
    {
        $this->expectException(BadMethodCallException::class);
        $obj = new ClassDynamicTestSubject();
        $obj->nonExistent = "value";
    }

    /**
     * Tests that __call throws a BadMethodCallException when invoking a method
     * that is neither a getter nor a setter (e.g. doSomething()).
     *
     * @return void
     */
    public function testCallNonExistentMethodThrows(): void
    {
        $this->expectException(BadMethodCallException::class);
        $obj = new ClassDynamicTestSubject();
        $obj->doSomething();
    }
}
