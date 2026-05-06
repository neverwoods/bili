<?php

namespace Bili\Tests;

use Bili\Geocoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Bili\Geocoder.
 *
 * Only the input validation can be tested without making real HTTP requests
 * to the Google Maps API. Network-dependent behavior is not covered here.
 *
 * @see Geocoder
 *
 * Run all tests in this file:
 *   vendor/bin/phpunit tests/GeocoderTest.php
 *
 * Run a single test:
 *   vendor/bin/phpunit --filter testEmptyAddressThrows tests/GeocoderTest.php
 */
class GeocoderTest extends TestCase
{
    /**
     * Tests that addressToLatLng() throws an InvalidArgumentException
     * when called with an empty address string.
     *
     * @return void
     */
    public function testEmptyAddressThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Geocoder::addressToLatLng("");
    }
}
