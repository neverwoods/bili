<?php

namespace Bili\Tests;

use Bili\Language;
use Bili\LanguageCollection;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    public const EN = "english-utf-8";
    public const NL = "nederlands-utf-8";

    /**
     * Tests getInstance.
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $objLanguage = Language::getInstance();
        $this->assertInstanceOf(Language::class, $objLanguage);
    }

    /**
     * Tests set lang.
     *
     * @return void
     */
    public function testSetLangNL(): void
    {
        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::NL);
        $this->assertEquals(self::NL, $objLanguage->getActiveLang());
    }

    /**
     * Tests set lang.
     *
     * @return void
     */
    public function testSetLangEN(): void
    {
        $objLanguage = Language::getInstance();
        $objLanguage->setLang(self::EN);
        $this->assertEquals(self::EN, $objLanguage->getActiveLang());
    }

    /**
     * Tests get active language.
     *
     * @return void
     */
    public function testGetActiveLang(): void
    {
        $objLanguage = Language::getInstance();
        $this->assertEquals(self::EN, $objLanguage->getActiveLang());
    }

    /**
     * Tests get translation in English.
     *
     * @return void
     */
    public function testGetEn(): void
    {
        Language::getInstance()->setLang(self::EN);
        $this->assertEquals("en", Language::get('abbr'));
        $this->assertEquals("Yes", Language::get('yes', 'message'));
    }

    /**
     * Tests get translation in Dutch.
     *
     * @return void
     */
    public function testGetNl(): void
    {
        Language::getInstance()->setLang(self::NL);
        $this->assertEquals("nl", Language::get('abbr'));
        $this->assertEquals("Ja", Language::get('yes', 'message'));
    }

    /**
     * Tests get translation in Dutch.
     *
     * @return void
     */
    public function testGetLangs(): void
    {
        $this->assertInstanceOf(LanguageCollection::class, Language::getInstance()->getLangs());

    }

    public function setUp(): void
    {
        parent::setUp();
        setlocale(LC_ALL, 'en_US.UTF-8');
        $objLanguage = Language::singleton("english-utf-8", __DIR__ . '/languages/');
        $objLanguage->setLocale();
    }
}