<?php

namespace Bili\Tests;

use Bili\Collection;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Test helper class with public properties and getId/getName/getValue methods,
 * used to test Collection's property-based lookup and ordering features.
 */
class CollectionTestItem
{
    public int $id;
    public string $name;
    public string $value;

    public function __construct(int $id, string $name, string $value = "")
    {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

/**
 * Tests for Bili\Collection.
 *
 * Covers the Iterator and JsonSerializable interfaces, adding/removing objects,
 * seeking, random selection, property-based lookups, ordering, navigation
 * (first, last, previous, next, reverse, end), merging, membership checks,
 * and the full pagination system (page items, page count, page start/end,
 * next/previous page, iteration within a page, getPageByChild, seekByChild).
 *
 * @see Collection
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/CollectionTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testPagination tests/CollectionTest.php
 */
class CollectionTest extends TestCase
{
    /**
     * Tests that an empty collection has a count of zero.
     *
     * @return void
     */
    public function testConstructEmpty(): void
    {
        $col = new Collection();
        $this->assertEquals(0, $col->count());
    }

    /**
     * Tests that constructing with an initial array sets the correct count.
     *
     * @return void
     */
    public function testConstructWithArray(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $this->assertEquals(3, $col->count());
    }

    /**
     * Tests that addObject() appends items to the end of the collection.
     *
     * @return void
     */
    public function testAddObject(): void
    {
        $col = new Collection();
        $col->addObject("first");
        $col->addObject("second");
        $this->assertEquals(2, $col->count());
    }

    /**
     * Tests that addObject() with blnAddToBeginning=true prepends the item.
     *
     * @return void
     */
    public function testAddObjectToBeginning(): void
    {
        $col = new Collection(["second", "third"]);
        $col->addObject("first", true);
        $this->assertEquals("first", $col->current());
    }

    /**
     * Tests that the collection can be iterated with foreach,
     * yielding all elements with their correct keys.
     *
     * @return void
     */
    public function testIteration(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $result = [];
        foreach ($col as $key => $value) {
            $result[$key] = $value;
        }
        $this->assertEquals([0 => "a", 1 => "b", 2 => "c"], $result);
    }

    /**
     * Tests that seek() advances the internal pointer to the given index.
     *
     * @return void
     */
    public function testSeek(): void
    {
        $col = new Collection(["a", "b", "c", "d"]);
        $col->seek(2);
        $this->assertEquals("c", $col->current());
    }

    /**
     * Tests that random() returns an element that exists in the collection.
     *
     * @return void
     */
    public function testRandom(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $item = $col->random();
        $this->assertContains($item, ["a", "b", "c"]);
    }

    /**
     * Tests that randomize() shuffles the collection but preserves the count.
     *
     * @return void
     */
    public function testRandomize(): void
    {
        $col = new Collection(range(1, 100));
        $col->randomize();
        // After randomizing, the collection should still have the same count
        $this->assertEquals(100, $col->count());
    }

    /**
     * Tests that getByPropertyValue() finds an element by calling
     * its getter method and matching the returned value.
     *
     * @return void
     */
    public function testGetByPropertyValue(): void
    {
        $col = new Collection([
            new CollectionTestItem(1, "Alpha"),
            new CollectionTestItem(2, "Beta"),
            new CollectionTestItem(3, "Gamma"),
        ]);

        $result = $col->getByPropertyValue("Name", "Beta");
        $this->assertNotNull($result);
        $this->assertEquals(2, $result->getId());
    }

    /**
     * Tests that getByPropertyValue() returns null when no element matches.
     *
     * @return void
     */
    public function testGetByPropertyValueNotFound(): void
    {
        $col = new Collection([
            new CollectionTestItem(1, "Alpha"),
        ]);

        $result = $col->getByPropertyValue("Name", "NonExistent");
        $this->assertNull($result);
    }

    /**
     * Tests that getValueByValue() finds an element by one property
     * and returns the value of another property (defaults to "value").
     *
     * @return void
     */
    public function testGetValueByValue(): void
    {
        $col = new Collection([
            new CollectionTestItem(1, "Alpha", "val-a"),
            new CollectionTestItem(2, "Beta", "val-b"),
        ]);

        $result = $col->getValueByValue("Name", "Beta");
        $this->assertEquals("val-b", $result);
    }

    /**
     * Tests that getValueByValue() returns an empty string when no element matches.
     *
     * @return void
     */
    public function testGetValueByValueNotFound(): void
    {
        $col = new Collection([
            new CollectionTestItem(1, "Alpha", "val-a"),
        ]);

        $result = $col->getValueByValue("Name", "NonExistent");
        $this->assertEquals("", $result);
    }

    /**
     * Tests that orderBy() with "asc" direction sorts elements
     * by the given property in ascending order.
     *
     * @return void
     */
    public function testOrderByAsc(): void
    {
        $col = new Collection([
            new CollectionTestItem(3, "Gamma"),
            new CollectionTestItem(1, "Alpha"),
            new CollectionTestItem(2, "Beta"),
        ]);

        $col->orderBy("name", "asc");
        $col->rewind();
        $this->assertEquals("Alpha", $col->current()->name);
    }

    /**
     * Tests that orderBy() with "desc" direction sorts elements
     * by the given property in descending order.
     *
     * @return void
     */
    public function testOrderByDesc(): void
    {
        $col = new Collection([
            new CollectionTestItem(1, "Alpha"),
            new CollectionTestItem(3, "Gamma"),
            new CollectionTestItem(2, "Beta"),
        ]);

        $col->orderBy("name", "desc");
        $col->rewind();
        $this->assertEquals("Gamma", $col->current()->name);
    }

    /**
     * Tests that first() returns the first element and last() returns
     * the last element of the collection.
     *
     * @return void
     */
    public function testFirstAndLast(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $this->assertEquals("a", $col->first());
        $this->assertEquals("c", $col->last());
    }

    /**
     * Tests that previous() moves the internal pointer one step back.
     *
     * @return void
     */
    public function testPrevious(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $col->next();
        $col->next();
        $this->assertEquals("c", $col->current());
        $col->previous();
        $this->assertEquals("b", $col->current());
    }

    /**
     * Tests that isFirst() and isLast() correctly report the pointer position.
     *
     * @return void
     */
    public function testIsFirstAndIsLast(): void
    {
        $col = new Collection(["a", "b"]);
        $this->assertTrue($col->isFirst());
        $this->assertFalse($col->isLast());

        $col->next();
        $this->assertFalse($col->isFirst());
        $this->assertTrue($col->isLast());
    }

    /**
     * Tests that merge() appends all elements from another collection.
     *
     * @return void
     */
    public function testMerge(): void
    {
        $col1 = new Collection(["a", "b"]);
        $col2 = new Collection(["c", "d"]);

        $col1->merge($col2);
        $this->assertEquals(4, $col1->count());
    }

    /**
     * Tests that merging an empty collection leaves the original unchanged.
     *
     * @return void
     */
    public function testMergeWithEmptyCollection(): void
    {
        $col1 = new Collection(["a", "b"]);
        $col2 = new Collection();

        $col1->merge($col2);
        $this->assertEquals(2, $col1->count());
    }

    /**
     * Tests that reverse() reverses the order and returns the collection,
     * with current() pointing at the new first element (previously last).
     *
     * @return void
     */
    public function testReverse(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $col->reverse();
        $this->assertEquals("c", $col->current());
    }

    /**
     * Tests that end() moves the internal pointer to the last element.
     *
     * @return void
     */
    public function testEnd(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $this->assertEquals("c", $col->end());
    }

    /**
     * Tests that inCollection() returns true for existing values
     * and false for non-existing values.
     *
     * @return void
     */
    public function testInCollection(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $this->assertTrue($col->inCollection("b"));
        $this->assertFalse($col->inCollection("z"));
    }

    /**
     * Tests that json_encode() on a Collection produces a JSON array
     * of its elements via the JsonSerializable interface.
     *
     * @return void
     * @throws JsonException
     */
    public function testJsonSerialize(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $json = json_encode($col, JSON_THROW_ON_ERROR);
        $this->assertEquals('["a","b","c"]', $json);
    }

    /**
     * Tests the pagination system on page 1: page items, current page,
     * page count, page start/end, and next/previous page numbers.
     *
     * @return void
     */
    public function testPagination(): void
    {
        $col = new Collection(range(1, 25));
        $col->setCurrentPage(1);
        $col->setPageItems(10);

        $this->assertEquals(10, $col->getPageItems());
        $this->assertEquals(1, $col->getCurrentPage());
        $this->assertEquals(3, $col->pageCount());
        $this->assertEquals(1, $col->pageStart());
        $this->assertEquals(10, $col->pageEnd());
        $this->assertEquals(2, $col->nextPage());
        $this->assertEquals(1, $col->previousPage());
    }

    /**
     * Tests pagination on the last page: page start/end clamp to the
     * actual collection size, and nextPage returns the last page number.
     *
     * @return void
     */
    public function testPaginationLastPage(): void
    {
        $col = new Collection(range(1, 25));
        // setPageItems must be called first, as it calls setCurrentPage() internally
        $col->setPageItems(10);
        $col->setCurrentPage(3);
        // Re-seek to the page start
        $col->seek($col->pageStart() - 1);

        $this->assertEquals(3, $col->getCurrentPage());
        $this->assertEquals(21, $col->pageStart());
        $this->assertEquals(25, $col->pageEnd());
        $this->assertEquals(3, $col->nextPage());
        $this->assertEquals(2, $col->previousPage());
    }

    /**
     * Tests that pageCount() returns 1 when no page items are set (no pagination).
     *
     * @return void
     */
    public function testPageCountWithNoPageItems(): void
    {
        $col = new Collection(range(1, 10));
        $this->assertEquals(1, $col->pageCount());
    }

    /**
     * Tests that iterating a paginated collection with foreach yields
     * only the items belonging to the current page.
     *
     * @return void
     */
    public function testPaginationIteration(): void
    {
        // When pageItems > 0, rewind() calls setCurrentPage() which reads from $_REQUEST
        // Simulate a page request
        $_REQUEST['page'] = 2;
        $col = new Collection(range(1, 25));
        $col->setPageItems(10);

        $items = [];
        foreach ($col as $item) {
            $items[] = $item;
        }
        $this->assertEquals(range(11, 20), $items);
        unset($_REQUEST['page']);
    }

    /**
     * Tests that getPageByChild() returns the correct page number
     * for a given child object based on the pagination settings.
     *
     * @return void
     */
    public function testGetPageByChild(): void
    {
        $items = [];
        for ($i = 1; $i <= 25; $i++) {
            $items[] = new CollectionTestItem($i, "Item $i");
        }
        $col = new Collection($items);
        $col->setCurrentPage(1);
        $col->setPageItems(10);

        $this->assertEquals(1, $col->getPageByChild($items[0]));
        $this->assertEquals(2, $col->getPageByChild($items[14]));
        $this->assertEquals(3, $col->getPageByChild($items[24]));
    }

    /**
     * Tests that getPageByChild() also works when passed a scalar ID
     * instead of an object with getId().
     *
     * @return void
     */
    public function testGetPageByChildWithId(): void
    {
        $items = [];
        for ($i = 1; $i <= 25; $i++) {
            $items[] = new CollectionTestItem($i, "Item $i");
        }
        $col = new Collection($items);
        $col->setCurrentPage(1);
        $col->setPageItems(10);

        // Pass an ID instead of an object
        $this->assertEquals(2, $col->getPageByChild(15));
    }

    /**
     * Tests that seekByChild() returns the zero-based index of the
     * matching child object and advances the internal pointer.
     *
     * @return void
     */
    public function testSeekByChild(): void
    {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = new CollectionTestItem($i, "Item $i");
        }
        $col = new Collection($items);

        $index = $col->seekByChild($items[2]);
        $this->assertEquals(2, $index);
    }

    /**
     * Tests that seekByChild() also works when passed a scalar ID
     * instead of an object with getId().
     *
     * @return void
     */
    public function testSeekByChildWithId(): void
    {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = new CollectionTestItem($i, "Item $i");
        }
        $col = new Collection($items);

        $index = $col->seekByChild(3);
        $this->assertEquals(2, $index);
    }

    /**
     * Tests that key() returns the current array key/index of the pointer.
     *
     * @return void
     */
    public function testKey(): void
    {
        $col = new Collection(["a", "b", "c"]);
        $this->assertEquals(0, $col->key());
        $col->next();
        $this->assertEquals(1, $col->key());
    }
}
