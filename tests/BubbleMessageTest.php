<?php

namespace Bili\Tests;

use Bili\BubbleMessage;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\BubbleMessage.
 *
 * Covers construction with default and custom options, the generated message ID,
 * CSS helper methods, JSON serialization, dynamic setters (via ClassDynamic),
 * and all class constants.
 *
 * @see BubbleMessage
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/BubbleMessageTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testDefaultConstructor tests/BubbleMessageTest.php
 */
class BubbleMessageTest extends TestCase
{
    /**
     * Tests that a BubbleMessage created with only a message string
     * receives the correct default values for all optional properties.
     *
     * @return void
     */
    public function testDefaultConstructor(): void
    {
        $msg = new BubbleMessage("Hello world");

        $this->assertEquals("Hello world", $msg->getMessage());
        $this->assertEquals("", $msg->getTitle());
        $this->assertEquals(BubbleMessage::MSG_TYPE_INFO, $msg->getType());
        $this->assertEquals(BubbleMessage::MSG_ICON_INFO, $msg->getIcon());
        $this->assertEquals(BubbleMessage::MSG_LOC_CONTAINER, $msg->getLocation());
        $this->assertEquals(0, $msg->getTimeout());
        $this->assertFalse($msg->getPermanent());
        $this->assertFalse($msg->getDismiss());
        $this->assertEquals("", $msg->getKey());
    }

    /**
     * Tests that all options passed to the constructor are correctly applied:
     * title, type, icon, location, timeout, permanent, dismiss, and key.
     *
     * @return void
     */
    public function testConstructorWithOptions(): void
    {
        $msg = new BubbleMessage("Error occurred", [
            "title" => "Error",
            "type" => BubbleMessage::MSG_TYPE_ERROR,
            "icon" => BubbleMessage::MSG_ICON_ERROR,
            "location" => BubbleMessage::MSG_LOC_PAGE,
            "timeout" => BubbleMessage::MSG_HIDE_TIME_ERROR,
            "permanent" => true,
            "dismiss" => true,
            "key" => "error-1",
        ]);

        $this->assertEquals("Error occurred", $msg->getMessage());
        $this->assertEquals("Error", $msg->getTitle());
        $this->assertEquals(BubbleMessage::MSG_TYPE_ERROR, $msg->getType());
        $this->assertEquals(BubbleMessage::MSG_ICON_ERROR, $msg->getIcon());
        $this->assertEquals(BubbleMessage::MSG_LOC_PAGE, $msg->getLocation());
        $this->assertEquals(BubbleMessage::MSG_HIDE_TIME_ERROR, $msg->getTimeout());
        $this->assertTrue($msg->getPermanent());
        $this->assertTrue($msg->getDismiss());
        $this->assertEquals("error-1", $msg->getKey());
    }

    /**
     * Tests that the generated message ID uses the key when one is provided
     * (format: "message-{key}").
     *
     * @return void
     */
    public function testIdWithKey(): void
    {
        $msg = new BubbleMessage("test", ["key" => "my-key"]);
        $this->assertEquals("message-my-key", $msg->getId());
    }

    /**
     * Tests that the generated message ID uses a random number suffix
     * when no key is provided (format: "message-{random}").
     *
     * @return void
     */
    public function testIdWithoutKey(): void
    {
        $msg = new BubbleMessage("test");
        $this->assertStringStartsWith("message-", $msg->getId());
        $this->assertNotEquals("message-", $msg->getId());
    }

    /**
     * Tests that getCssType() returns the message type string used for CSS classes.
     *
     * @return void
     */
    public function testGetCssType(): void
    {
        $msg = new BubbleMessage("test", ["type" => BubbleMessage::MSG_TYPE_WARNING]);
        $this->assertEquals(BubbleMessage::MSG_TYPE_WARNING, $msg->getCssType());
    }

    /**
     * Tests that getCssIcon() returns the icon class name string.
     *
     * @return void
     */
    public function testGetCssIcon(): void
    {
        $msg = new BubbleMessage("test", ["icon" => BubbleMessage::MSG_ICON_CONFIRM]);
        $this->assertEquals(BubbleMessage::MSG_ICON_CONFIRM, $msg->getCssIcon());
    }

    /**
     * Tests that jsonSerialize() returns the correct associative array
     * with type, icon, title, body, location, timeout, and key.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $msg = new BubbleMessage("Body text", [
            "title" => "Title",
            "type" => BubbleMessage::MSG_TYPE_CONFIRM,
            "icon" => BubbleMessage::MSG_ICON_CONFIRM,
            "location" => BubbleMessage::MSG_LOC_SIDEBAR,
            "timeout" => 3000,
            "key" => "test-key",
        ]);

        $json = $msg->jsonSerialize();

        $this->assertEquals(BubbleMessage::MSG_TYPE_CONFIRM, $json["type"]);
        $this->assertEquals(BubbleMessage::MSG_ICON_CONFIRM, $json["icon"]);
        $this->assertEquals("Title", $json["title"]);
        $this->assertEquals("Body text", $json["body"]);
        $this->assertEquals(BubbleMessage::MSG_LOC_SIDEBAR, $json["location"]);
        $this->assertEquals(3000, $json["timeout"]);
        $this->assertEquals("test-key", $json["key"]);
    }

    /**
     * Tests that all dynamic setters (via ClassDynamic) correctly update
     * each property after construction.
     *
     * @return void
     */
    public function testSetters(): void
    {
        $msg = new BubbleMessage("test");
        $msg->setMessage("updated");
        $msg->setTitle("New Title");
        $msg->setType(BubbleMessage::MSG_TYPE_WARNING);
        $msg->setIcon(BubbleMessage::MSG_ICON_WARNING);
        $msg->setLocation(BubbleMessage::MSG_LOC_SIDEBAR);
        $msg->setTimeout(1000);
        $msg->setPermanent(true);
        $msg->setDismiss(true);
        $msg->setKey("updated-key");

        $this->assertEquals("updated", $msg->getMessage());
        $this->assertEquals("New Title", $msg->getTitle());
        $this->assertEquals(BubbleMessage::MSG_TYPE_WARNING, $msg->getType());
        $this->assertEquals(BubbleMessage::MSG_ICON_WARNING, $msg->getIcon());
        $this->assertEquals(BubbleMessage::MSG_LOC_SIDEBAR, $msg->getLocation());
        $this->assertEquals(1000, $msg->getTimeout());
        $this->assertTrue($msg->getPermanent());
        $this->assertTrue($msg->getDismiss());
        $this->assertEquals("updated-key", $msg->getKey());
    }

    /**
     * Tests that all BubbleMessage class constants have the expected values
     * for message types, icons, locations, and hide times.
     *
     * @return void
     */
    public function testConstants(): void
    {
        $this->assertEquals("info", BubbleMessage::MSG_TYPE_INFO);
        $this->assertEquals("error", BubbleMessage::MSG_TYPE_ERROR);
        $this->assertEquals("warning", BubbleMessage::MSG_TYPE_WARNING);
        $this->assertEquals("success", BubbleMessage::MSG_TYPE_CONFIRM);

        $this->assertEquals("info-circle", BubbleMessage::MSG_ICON_INFO);
        $this->assertEquals("times-circle", BubbleMessage::MSG_ICON_ERROR);
        $this->assertEquals("warning", BubbleMessage::MSG_ICON_WARNING);
        $this->assertEquals("check-circle", BubbleMessage::MSG_ICON_CONFIRM);

        $this->assertEquals(1, BubbleMessage::MSG_LOC_PAGE);
        $this->assertEquals(2, BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertEquals(3, BubbleMessage::MSG_LOC_SIDEBAR);

        $this->assertEquals(5000, BubbleMessage::MSG_HIDE_TIME_INFO);
        $this->assertEquals(15000, BubbleMessage::MSG_HIDE_TIME_ERROR);
    }
}
