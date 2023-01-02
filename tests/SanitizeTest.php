<?php

namespace Bili\Tests;

use Bili\Sanitize;
use PHPUnit\Framework\TestCase;

class SanitizeTest extends TestCase
{
    public function testToXhtml(): void
    {
        $this->assertEquals("M&amp;M's", Sanitize::toXhtml("M&M's"));
        $this->assertEquals("Ben &amp; Jerry's", Sanitize::toXhtml("Ben & Jerry's"));
        $this->assertEquals("10&#36; bill", Sanitize::toXhtml("10$ bill"));
    }

    public function testToEntities(): void
    {
        $this->assertEquals("M&amp;M&#039;s", Sanitize::toEntities("M&M's"));
        $this->assertEquals("Ben &amp; Jerry&#039;s", Sanitize::toEntities("Ben & Jerry's"));
        $this->assertEquals("10$ bill", Sanitize::toEntities("10$ bill"));
    }

    public function testFromEntities(): void
    {
        $this->assertEquals("M&M's", Sanitize::fromEntities("M&amp;M&#039;s"));
        $this->assertEquals("Ben & Jerry's", Sanitize::fromEntities("Ben &amp; Jerry&#039;s"));
        $this->assertEquals("10$ bill", Sanitize::fromEntities("10$ bill"));
    }

    public function testToXml(): void
    {
        $this->assertEquals("M&amp;M's", Sanitize::toXml("M&M's"));
        $this->assertEquals("Ben &amp; Jerry's", Sanitize::toXml("Ben & Jerry's"));
        $this->assertEquals("10&#36; bill", Sanitize::toXml("10$ bill"));
    }

    public function testFloatToMaxLength(): void
    {
        $this->assertEquals(99999999, Sanitize::floatToMaxLength(234234234.23234234, 8));
        $this->assertEquals(99348871.343434, Sanitize::floatToMaxLength(99348871.3434344, 9));
    }

    public function testToDecimal(): void
    {
        $this->assertEquals(23.0, Sanitize::toDecimal(23));
        $this->assertEquals(23.0, Sanitize::toDecimal("23"));
        $this->assertEquals(99348871.343434, Sanitize::toDecimal(99348871.3434344));
        $this->assertEquals(1541045.45, Sanitize::toDecimal("1.541.045,45"));
        $this->assertEquals(1541045.45, Sanitize::toDecimal("1,541,045.45"));
        $this->assertEquals(1541045.45, Sanitize::toDecimal("1541045,45"));
        $this->assertEquals(1541045.45, Sanitize::toDecimal("1541045.45"));
    }

    public function testBr2nl(): void
    {
        $this->assertEquals("line\n", Sanitize::br2nl("line<br>"));
        $this->assertEquals("line\nline\nline\n", Sanitize::br2nl("line<br>line<br>line<br>"));
    }

    public function testToInteger(): void
    {
        $this->assertEquals(1010, Sanitize::toInteger("1010"));
        $this->assertEquals(1010, Sanitize::toInteger("1010.0"));
        $this->assertEquals(10, Sanitize::toInteger("10,95"));
    }

    public function testToUrl(): void
    {
        $this->assertEquals("xmonl-testfile-with-long-name", Sanitize::toUrl("xmo.nl /test/file with long name"));
        $this->assertEquals("mm-testfile-with-long-name", Sanitize::toUrl("m&m /test/file with long name"));
    }

    public function testToNumeric(): void
    {
        $this->assertEquals(0, Sanitize::toNumeric("xml"));
        $this->assertEquals(10.1, Sanitize::toNumeric(10.10));
    }

    public function testToString(): void
    {
        $this->assertSame('xml', Sanitize::toString("xml"));
        $this->assertSame('10.1', Sanitize::toString(10.10));
    }

    public function testToAscii(): void
    {
        $this->assertSame("This is the Euro symbol 'EUR'.", Sanitize::toAscii("This is the Euro symbol '€'."));
    }

    /**
     * In php8.1 FILTER_SANITIZE_STRING for filter_var is deprecated. This test ensures the results are the same.
     *
     * @return void
     */
    public function testFilterSanitizeString(): void
    {
        $varInput = "Dit is een test string";
        $this->assertSame("Dit is een test string", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een test string", Sanitize::filterStringPolyfill($varInput));

        $varInput = "Dit is een test string, $10";
        $this->assertSame("Dit is een test string, $10", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een test string, $10", Sanitize::filterStringPolyfill($varInput));

        $varInput = "Dit is een test string, $10\"\"";
        $this->assertSame("Dit is een test string, $10&#34;&#34;", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een test string, $10&#34;&#34;", Sanitize::filterStringPolyfill($varInput));

        $varInput = "Dit is een test string, $10\"\"";
        $this->assertSame("Dit is een test string, $10&#34;&#34;", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een test string, $10&#34;&#34;", Sanitize::filterStringPolyfill($varInput));

        $varInput = "Dit is een 'test' string, $10\"\"";
        $this->assertSame("Dit is een &#39;test&#39; string, $10&#34;&#34;", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een &#39;test&#39; string, $10&#34;&#34;", Sanitize::filterStringPolyfill($varInput));

        $varInput = "<a href=''>Dit is een 'test' string, $10\"\"</a>";
        $this->assertSame("Dit is een &#39;test&#39; string, $10&#34;&#34;", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een &#39;test&#39; string, $10&#34;&#34;", Sanitize::filterStringPolyfill($varInput));

        $varInput = "<a href=\"http://localhost:8080\">Dit is een 'test' string, $10\"\"</a>";
        $this->assertSame("Dit is een &#39;test&#39; string, $10&#34;&#34;", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Dit is een &#39;test&#39; string, $10&#34;&#34;", Sanitize::filterStringPolyfill($varInput));

        $varInput = "Bon aña 2023, !@#5^&*<>$";
        $this->assertSame("Bon aña 2023, !@#5^&*$", filter_var($varInput, FILTER_SANITIZE_STRING));
        $this->assertSame("Bon aña 2023, !@#5^&*$", Sanitize::filterStringPolyfill($varInput));
    }
}