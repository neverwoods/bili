<?php

namespace Bili\Tests;

use Bili\Crypt;
use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase
{
    /**
     * Tests Crypt::generateToken default length.
     *
     * @return void
     */
    public function testGenerateToken(): void
    {
        $this->assertEquals(40, strlen((string)Crypt::generateToken()));
    }

    /**
     * Tests Crypt::generateToken 32 length.
     *
     * @return void
     */
    public function testGenerateTokenLength32(): void
    {
        $this->assertEquals(32, strlen((string)Crypt::generateToken([], 32)));
    }

    /**
     * Tests Crypt::generateToken sha1 encrypt first x chars.
     *
     * @return void
     */
    public function testGenerateTokenSha1(): void
    {
        $this->assertEquals("86f7e437faa5a7fc", Crypt::generateToken(['a'], 16));
        $this->assertEquals("641e83ce499913cb", Crypt::generateToken(['celery'], 16));
        $this->assertEquals("4de4727ba00457f7", Crypt::generateToken(['payroll'], 16));
        $this->assertEquals("2dbc2fd2358e1ea1", Crypt::generateToken(['online'], 16));
        $this->assertEquals("7b0e4e1f57", Crypt::generateToken(['ea504d83c6'], 10));
        $this->assertEquals("eb95c067b0", Crypt::generateToken(['5068a9c245'], 10));
        $this->assertEquals("20929cba0c", Crypt::generateToken(['656228147d'], 10));
    }

    /**
     * Tests Crypt::doEncode encode 1.
     *
     * @return void
     */
    public function testDoEncode1(): void
    {
        $this->assertEquals("35346486", Crypt::doEncode(1));
    }

    /**
     * Tests Crypt::doEncode encode 2.
     *
     * @return void
     */
    public function testDoEncode2(): void
    {
        $this->assertEquals("91069296", Crypt::doEncode(2));
    }

    /**
     * Tests Crypt::doDecode encode 1.
     *
     * @return void
     */
    public function testDoDecode1(): void
    {
        $this->assertEquals(1, Crypt::doDecode("35346486"));
    }

    /**
     * Tests Crypt::doDecode encode 2.
     *
     * @return void
     */
    public function testDoDecode2(): void
    {
        $this->assertEquals(2, Crypt::doDecode("91069296"));
    }

    /**
     * Tests Crypt::doDecode and Crypt::doEncode.
     *
     * @return void
     */
    public function testEncodeDecode(): void
    {
        for ($i=99; $i<399; $i++) {
            $this->assertEquals($i, Crypt::doDecode(Crypt::doEncode($i)));
        }
    }
}