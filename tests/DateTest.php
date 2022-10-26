<?php

namespace Bili\Tests;

use Bili\Date;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class DateTest extends TestCase
{
    /**
     * Tests Date::fromMysql in English.
     *
     * @return void
     */
    public function testFromMysqlEn(): void
    {
        setlocale(LC_ALL, 'en_US.UTF-8');
        $this->assertEquals("2020-10-10", Date::fromMysql("YYYY-MM-DD", "2020-10-10 08:05:09"));
        $this->assertEquals("Saturday October, 10", Date::fromMysql("dddd MMMM, D", "2020-10-10 08:05:09"));
        $this->assertEquals("08:05:09", Date::fromMysql("hh:mm:ss", "2020-10-10 08:05:09"));
        $this->assertEquals("8:5:9 am", Date::fromMysql("h:m:s a", "2020-10-10 08:05:09"));
    }

    /**
     * Tests Date::fromMysql in Dutch.
     *
     * @return void
     */
    public function testFromMysqlNl(): void
    {
        setlocale(LC_ALL, 'nl_NL.UTF-8');
        $this->assertEquals("2020-09-02", Date::fromMysql("YYYY-MM-DD", "2020-09-02 08:05:09"));
        $this->assertEquals("woensdag 2 september", Date::fromMysql("dddd D MMMM", "2020-09-02 08:05:09"));
    }

    /**
     * Tests Date::toMysql.
     *
     * @return void
     */
    public function testToMysql(): void
    {
        $this->assertEquals("2020-09-02 08:05:09", Date::toMysql("2020-09-02 08:05:09"));
        $this->assertEquals("1960-09-12 08:05:09", Date::toMysql("1960-09-12 08:05:09"));
        $this->assertEquals("1860-10-02 08:05:09", Date::toMysql("1860-10-02 08:05:09"));
    }

    /**
     * Tests Date::getMonthName in Dutch.
     *
     * @return void
     */
    public function testGetMonthNameNl(): void
    {
        setlocale(LC_ALL, 'nl_NL.UTF-8');
        $this->assertEquals("januari", Date::getMonthName(1));
        $this->assertEquals("maart", Date::getMonthName(3));
        $this->assertEquals("mei", Date::getMonthName(5));
        $this->assertEquals("juli", Date::getMonthName(7));
        $this->assertEquals("september", Date::getMonthName(9));
        $this->assertEquals("november", Date::getMonthName(11));
    }

    /**
     * Tests Date::getMonthName in English.
     *
     * @return void
     */
    public function testGetMonthNameEn(): void
    {
        setlocale(LC_ALL, 'en_US.UTF-8');
        $this->assertEquals("January", Date::getMonthName(1));
        $this->assertEquals("February", Date::getMonthName(2));
        $this->assertEquals("March", Date::getMonthName(3));
        $this->assertEquals("April", Date::getMonthName(4));
        $this->assertEquals("May", Date::getMonthName(5));
        $this->assertEquals("June", Date::getMonthName(6));
    }

    /**
     * Tests Date::getShortMonthName in Dutch.
     *
     * @return void
     */
    public function testGetShortMonthNameNl(): void
    {
        setlocale(LC_ALL, 'nl_NL.UTF-8');
        $this->assertEquals("jan", Date::getShortMonthName(1));
        $this->assertEquals("feb", Date::getShortMonthName(2));
        $this->assertEquals("apr", Date::getShortMonthName(4));
        $this->assertEquals("jun", Date::getShortMonthName(6));
    }

    /**
     * Tests Date::getShortMonthName in English.
     *
     * @return void
     */
    public function testGetShortMonthNameEn(): void
    {
        setlocale(LC_ALL, 'en_US.UTF-8');
        $this->assertEquals("Jan", Date::getShortMonthName(1));
        $this->assertEquals("Feb", Date::getShortMonthName(2));
        $this->assertEquals("Nov", Date::getShortMonthName(11));
        $this->assertEquals("Dec", Date::getShortMonthName(12));
    }

    public function testGetQuarter(): void
    {
        $this->assertSame(1.0, Date::getQuarter(1));
        $this->assertSame(1.0, Date::getQuarter(2));
        $this->assertSame(1.0, Date::getQuarter(3));
        $this->assertSame(2.0, Date::getQuarter(4));
        $this->assertSame(2.0, Date::getQuarter(5));
        $this->assertSame(2.0, Date::getQuarter(6));
        $this->assertSame(3.0, Date::getQuarter(7));
        $this->assertSame(3.0, Date::getQuarter(8));
        $this->assertSame(3.0, Date::getQuarter(9));
        $this->assertSame(4.0, Date::getQuarter(10));
        $this->assertSame(4.0, Date::getQuarter(11));
        $this->assertSame(4.0, Date::getQuarter(12));
    }

    /**
     * Tests Date::parseDate.
     *
     * @return void
     */
    public function testParseDate(): void
    {
        $this->assertEquals(1602547200, Date::parseDate("10/13/2020", "MM/DD/YYYY"));
        $this->assertEquals(1599033909, Date::parseDate("2020-09-02 08:05:09", "YYYY-MM-DD HH:mm:ss"));
    }

    /**
     * Tests Date::testParsedDate.
     *
     * @return void
     */
    public function testTestParsedDate(): void
    {
        $this->assertSame(50889600, Date::testParsedDate("08/13/1971", "MM/DD/YYYY", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("13/08/1971", "DD/MM/YYYY", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("1971/08/13", "YYYY/MM/DD", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("1971/13/08", "YYYY/DD/MM", 1900, 2025)->getTimestamp());

        $this->assertSame(50889600, Date::testParsedDate("13-08-1971", "DD-MM-YYYY", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("08-13-1971", "MM-DD-YYYY", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("1971-08-13", "YYYY-MM-DD", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("1971-13-08", "YYYY-DD-MM", 1900, 2025)->getTimestamp());

        $this->assertSame(50889600, Date::testParsedDate("13081971", "DDMMYYYY", 1900, 2025)->getTimestamp());
        $this->assertSame(50889600, Date::testParsedDate("19710813", "YYYYMMDD", 1900, 2025)->getTimestamp());

        $this->assertNull(Date::testParsedDate("32/12/1971", "MM/DD/YYYY", 1900, 2025));
        $this->assertNull(Date::testParsedDate("08/13/1971", "DD/MM/YYYY", 1900, 2025));
        $this->assertNull(Date::testParsedDate("13/08/1971", "MM/DD/YYYY", 1900, 2025));
    }

    public function testGetDateDelimiter(): void
    {
        $this->assertEquals("/", Date::getDateDelimiter("10/13/2020"));
        $this->assertEquals("-", Date::getDateDelimiter("10-13-2020"));
        $this->assertEquals(".", Date::getDateDelimiter("10.13.2020"));
    }

    /**
     * Tests Date::fixShortYearInDate.
     *
     * @return void
     */
    public function testFixShortYearInDate(): void
    {
        $this->assertEquals("1971-08-13", Date::fixShortYearInDate("71-08-13"));
        $this->assertEquals("13/08/1971", Date::fixShortYearInDate("13/08/71"));
        $this->assertEquals("1971-13-08", Date::fixShortYearInDate("71-13-08"));
        $this->assertEquals("1971-08-13", Date::fixShortYearInDate("1971-08-13"));
    }

    /**
     * Tests Date::convertDate in Dutch.
     *
     * @return void
     */
    public function testConvertDateNl(): void
    {
        setlocale(LC_ALL, 'nl_NL.UTF-8');
        $this->assertSame("13 augustus 1971", Date::convertDate("1971-08-13", "YYYY-MM-DD", "DD MMMM YYYY"));
    }

    /**
     * Tests Date::convertDate in English.
     *
     * @return void
     */
    public function testConvertDateEn(): void
    {
        setlocale(LC_ALL, 'en_US.UTF-8');
        $this->assertSame("13 August 1971", Date::convertDate("1971-08-13", "YYYY-MM-DD", "DD MMMM YYYY"));
    }

    /**
     * Tests Date::convertDate in English.
     *
     * @return void
     */
    public function testGetOrdinalSuffix(): void
    {
        $arrItems = ['','st','nd','rd'];

        $this->assertSame("1st", Date::getOrdinalSuffix(1, $arrItems));
        $this->assertSame("2nd", Date::getOrdinalSuffix(2, $arrItems));
        $this->assertSame("3rd", Date::getOrdinalSuffix(3, $arrItems));
    }

    /**
     * Tests Date::dateDifference.
     *
     * @return void
     */
    public function testDateDifference(): void
    {
        $this->assertSame(
            "5 years",
            Date::dateDifference("13-08-1971 08:30:00", "16-11-1976 11:00:00", 1)
        );
        $this->assertSame(
            "5 years, 3 months",
            Date::dateDifference("13-08-1971 08:30:00", "16-11-1976 11:00:00", 2)
        );
        $this->assertSame(
            "5 years, 3 months, 3 days",
            Date::dateDifference("13-08-1971 08:30:00", "16-11-1976 11:00:00", 3)
        );
        $this->assertSame(
            "5 years, 3 months, 3 days, 2 hours",
            Date::dateDifference("13-08-1971 08:30:00", "16-11-1976 11:00:00", 4)
        );
        $this->assertSame(
            "5 years, 3 months, 3 days, 2 hours, 30 minutes",
            Date::dateDifference("13-08-1971 08:30:00", "16-11-1976 11:00:00", 5)
        );
        $this->assertSame(
            "5 years, 3 months, 3 days, 2 hours, 30 minutes, 1 second",
            Date::dateDifference("13-08-1971 08:30:00", "16-11-1976 11:00:01")
        );
    }

    /**
     * Tests Date::getFirstDayTimestamp.
     *
     * @return void
     */
    public function testGetFirstDayTimestamp(): void
    {
        $intTimestamp = Carbon::createFromIsoFormat("YYYY-MM-DD HH:mm:ss", "2020-09-02 08:05:09")->getTimestamp();
        $intFirstDay = Date::getFirstDayTimestamp($intTimestamp);

        $this->assertEquals("2020-09-01 00:00:00", Carbon::createFromTimestamp($intFirstDay)->isoFormat("YYYY-MM-DD HH:mm:ss"));
    }

    /**
     * Tests Date::getLastDayTimestamp.
     *
     * @return void
     */
    public function testGetLastDayTimestamp(): void
    {
        $intTimestamp = Carbon::createFromIsoFormat("YYYY-MM-DD HH:mm:ss", "2020-09-02 08:05:09")->getTimestamp();
        $intFirstDay = Date::getLastDayTimestamp($intTimestamp);

        $this->assertEquals("2020-09-30 00:00:00", Carbon::createFromTimestamp($intFirstDay)->isoFormat("YYYY-MM-DD HH:mm:ss"));
    }

    /**
     * Tests Concert date format.
     *
     * @return void
     */
    public function testConvertDateFormat(): void
    {
        $this->assertEquals("MM/DD/YYYY", Date::convertStrftimeFormat("%m/%d/%Y"));
        $this->assertEquals("DD/MM/YYYY", Date::convertStrftimeFormat("%d/%m/%Y"));
        $this->assertEquals("YYYY/MM/DD", Date::convertStrftimeFormat("%Y/%m/%d"));
        $this->assertEquals("YYYY/DD/MM", Date::convertStrftimeFormat("%Y/%d/%m"));

        $this->assertEquals("DD-MM-YYYY", Date::convertStrftimeFormat("%d-%m-%Y"));
        $this->assertEquals("MM-DD-YYYY", Date::convertStrftimeFormat("%m-%d-%Y"));
        $this->assertEquals("YYYY-MM-DD", Date::convertStrftimeFormat("%Y-%m-%d"));
        $this->assertEquals("YYYY-DD-MM", Date::convertStrftimeFormat("%Y-%d-%m"));

        $this->assertEquals("DDMMYYYY", Date::convertStrftimeFormat("%d%m%Y"));
        $this->assertEquals("YYYYMMDD", Date::convertStrftimeFormat("%Y%m%d"));

        $this->assertEquals("dddd, MMMM D, YYYY", Date::convertStrftimeFormat("%A, %B %-e, %Y"));
        $this->assertEquals("A, MMMM D, YYYY HH:mm", Date::convertStrftimeFormat("A, %B %-e, %Y %H:%M"));
        $this->assertEquals("MMMM D, YYYY", Date::convertStrftimeFormat("%B %-e, %Y"));
        $this->assertEquals("D MMM YYYY", Date::convertStrftimeFormat("%-e %h %Y"));
        $this->assertEquals("DD MMMM", Date::convertStrftimeFormat("%d %B"));
        $this->assertEquals("DD-MM-YYYY", Date::convertStrftimeFormat("%d-%m-%Y"));
        $this->assertEquals("YYYY-MM-DD HH:mm:ss", Date::convertStrftimeFormat("%Y-%m-%d %H:%M:%S"));
        $this->assertEquals("MM/DD/YYYY", Date::convertStrftimeFormat("%m/%d/%Y"));
        $this->assertEquals("DD MMMM YYYY HH:mm", Date::convertStrftimeFormat("%d %B %Y %H:%M"));
        $this->assertEquals("DD MMMM YYYY at HH:mm", Date::convertStrftimeFormat("%d %B %Y at %H:%M"));
        $this->assertEquals("YYYY-MM-DDTHH:mm:ss", Date::convertStrftimeFormat("%Y-%m-%dT%H:%M:%S"));
        $this->assertEquals("D MMM YYYY", Date::convertStrftimeFormat("%-e %b %Y"));
        $this->assertEquals("MMMM D, YYYY", Date::convertStrftimeFormat("%B %-e, %Y"));
        $this->assertEquals("MMMM D", Date::convertStrftimeFormat("%B %-e"));
        $this->assertEquals("dddd, MMMM D, YYYY", Date::convertStrftimeFormat("%A, %B %-e, %Y"));
        $this->assertEquals("dddd, MMMM D", Date::convertStrftimeFormat("%A, %B %-e"));
        $this->assertEquals("MMMM D, YYYY", Date::convertStrftimeFormat("%B %-e, %Y"));
        $this->assertEquals("ddd, MMMM D, YYYY", Date::convertStrftimeFormat("%a, %B %-e, %Y"));
        $this->assertEquals("ddd, MMM D, YYYY", Date::convertStrftimeFormat("%a, %b %-e, %Y"));
        $this->assertEquals("ddd, MMM D", Date::convertStrftimeFormat("%a, %b %-e"));
        $this->assertEquals("MMM D", Date::convertStrftimeFormat("%b %-e"));
        $this->assertEquals("MMM D, YYYY", Date::convertStrftimeFormat("%b %-e, %Y"));
        $this->assertEquals("MMMM D, YYYY HH:mm", Date::convertStrftimeFormat("%B %-e, %Y %H:%M"));
        $this->assertEquals("MMM D HH:mm", Date::convertStrftimeFormat("%b %-e %H:%M"));
    }

    /**
     * Test only works in php < 8.1
     *
     * @requires PHP 7.4
     * @return void
     */
    public function testConvertFormatTest(): void
    {
        $strFormat = '%-e';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %B %Y %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %B %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %B';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %b %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %b %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %b';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%-e %h %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%A %-e %B %Y %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%A %-e %B %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%A %-e %B';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%A, %B %-e';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%A, %B %-e, %Y %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%A, %B %-e, %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%B %-e';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%B %-e, %Y %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%B %-e, %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%Y-%m-%d %H:%M:%S';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%a %-e %B %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%a %-e %b %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%a %-e %b';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%a, %B %-e, %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%a, %b %-e';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%a, %b %-e, %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%b %-e %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%b %-e';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%b %-e, %Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%d %B %Y %H:%M';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%d %B';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%d-%m-%Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );

        $strFormat = '%m/%d/%Y';
        $this->assertEquals(Carbon::createFromTimestamp(
            50889600)->isoFormat(Date::convertStrftimeFormat($strFormat)),
            strftime($strFormat, 50889600)
        );
    }
}