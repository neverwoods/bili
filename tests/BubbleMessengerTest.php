<?php

namespace Bili\Tests;

use Bili\BubbleMessage;
use Bili\BubbleMessenger;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\BubbleMessenger.
 *
 * Covers the session-backed message stack: adding messages via string or object,
 * retrieving by location, automatic removal of non-permanent messages,
 * retention of permanent messages, removal by key, key existence checks
 * (string and array), clearing, and location-to-string conversion.
 *
 * @see BubbleMessenger
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/BubbleMessengerTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testAddAndGetMessages tests/BubbleMessengerTest.php
 */
class BubbleMessengerTest extends TestCase
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
     * Tests that messages added with different locations are correctly
     * returned when retrieved by their respective location.
     *
     * @return void
     */
    public function testAddAndGetMessages(): void
    {
        BubbleMessenger::add("Hello", ["location" => BubbleMessage::MSG_LOC_CONTAINER]);
        BubbleMessenger::add("World", ["location" => BubbleMessage::MSG_LOC_PAGE]);

        $containerMessages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(1, $containerMessages);
        $this->assertEquals("Hello", $containerMessages[0]->getMessage());

        $pageMessages = BubbleMessenger::get(BubbleMessage::MSG_LOC_PAGE);
        $this->assertCount(1, $pageMessages);
        $this->assertEquals("World", $pageMessages[0]->getMessage());
    }

    /**
     * Tests that a pre-constructed BubbleMessage object can be added
     * directly via addMessage() and retrieved correctly.
     *
     * @return void
     */
    public function testAddMessage(): void
    {
        $msg = new BubbleMessage("Test message", [
            "location" => BubbleMessage::MSG_LOC_CONTAINER,
        ]);
        BubbleMessenger::addMessage($msg);

        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(1, $messages);
        $this->assertEquals("Test message", $messages[0]->getMessage());
    }

    /**
     * Tests that non-permanent messages are removed from the session
     * after they have been retrieved once via get().
     *
     * @return void
     */
    public function testGetRemovesNonPermanentMessages(): void
    {
        BubbleMessenger::add("Temp", ["location" => BubbleMessage::MSG_LOC_CONTAINER]);

        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(1, $messages);

        // Second call should return empty since the message was not permanent
        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(0, $messages);
    }

    /**
     * Tests that permanent messages remain in the session after retrieval,
     * so they are returned on subsequent get() calls as well.
     *
     * @return void
     */
    public function testGetKeepsPermanentMessages(): void
    {
        BubbleMessenger::add("Permanent", [
            "location" => BubbleMessage::MSG_LOC_CONTAINER,
            "permanent" => true,
        ]);

        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(1, $messages);

        // Second call should still return the permanent message
        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(1, $messages);
    }

    /**
     * Tests that remove() deletes only the message matching the given key,
     * leaving other messages intact.
     *
     * @return void
     */
    public function testRemove(): void
    {
        BubbleMessenger::add("Removable", [
            "key" => "remove-me",
            "location" => BubbleMessage::MSG_LOC_CONTAINER,
        ]);
        BubbleMessenger::add("Keep", [
            "key" => "keep-me",
            "location" => BubbleMessage::MSG_LOC_CONTAINER,
        ]);

        BubbleMessenger::remove("remove-me");

        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(1, $messages);
        $this->assertEquals("keep-me", $messages[0]->getKey());
    }

    /**
     * Tests that hasMessage() correctly identifies whether a message
     * with a given key string exists in the session.
     *
     * @return void
     */
    public function testHasMessageWithString(): void
    {
        BubbleMessenger::add("Test", [
            "key" => "exists",
            "location" => BubbleMessage::MSG_LOC_CONTAINER,
        ]);

        $this->assertTrue(BubbleMessenger::hasMessage(["exists"]));
        $this->assertFalse(BubbleMessenger::hasMessage(["not-exists"]));
    }

    /**
     * Tests that hasMessage() accepts an array of keys and returns true
     * if any of the keys match a stored message.
     *
     * @return void
     */
    public function testHasMessageWithArray(): void
    {
        BubbleMessenger::add("Test", [
            "key" => "key-1",
            "location" => BubbleMessage::MSG_LOC_CONTAINER,
        ]);

        $this->assertTrue(BubbleMessenger::hasMessage(["key-1", "key-2"]));
        $this->assertFalse(BubbleMessenger::hasMessage(["key-3", "key-4"]));
    }

    /**
     * Tests that clear() removes all messages from the session.
     *
     * @return void
     */
    public function testClear(): void
    {
        BubbleMessenger::add("Test", ["location" => BubbleMessage::MSG_LOC_CONTAINER]);
        BubbleMessenger::clear();

        $messages = BubbleMessenger::get(BubbleMessage::MSG_LOC_CONTAINER);
        $this->assertCount(0, $messages);
    }

    /**
     * Tests that locationToString() converts location constants to their
     * string equivalents, and returns an empty string for unknown locations.
     *
     * @return void
     */
    public function testLocationToString(): void
    {
        $this->assertEquals("container", BubbleMessenger::locationToString(BubbleMessage::MSG_LOC_CONTAINER));
        $this->assertEquals("page", BubbleMessenger::locationToString(BubbleMessage::MSG_LOC_PAGE));
        $this->assertEquals("side", BubbleMessenger::locationToString(BubbleMessage::MSG_LOC_SIDEBAR));
        $this->assertEquals("", BubbleMessenger::locationToString(999));
    }
}
