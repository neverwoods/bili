<?php

namespace Bili;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use DateInterval;
use DateTime;
use Exception;

/**
 * Date Class v0.3.0
 * Holds methods for misc. date calls.
 *
 * CHANGELOG
 * version 0.3.0, 14 Jun 2016
 *   ADD: Added the minDate method.
 *   ADD: Added the maxDate method.
 * version 0.2.7, 24 Sep 2014
 *   ADD: Added the getOrdinalSuffix method.
 * version 0.2.6, 04 Apr 2013
 *   ADD: Added the getMonthName method.
 * version 0.2.5, 29 Sep 2009
 *   ADD: Added a replcament function for strptime.
 *   FIX: Fixed the call to strptime on Windows.
 * version 0.2.4, 16 Jun 2008
 *   FIX: Fixed the timeSince method.
 * version 0.2.3, 11 May 2008
 *   ADD: Added the timeSince method.
 * version 0.2.2, 02 Apr 2008
 *   FIX: Fixed parseDate.
 * version 0.2.1, 14 Feb 2008
 *   CHG: Changed fromMysql. Removed language check.
 * version 0.2.0, 15 Nov 2007
 *   CHG: Extended toMysql method.
 * version 0.1.0, 12 Apr 2006
 *   NEW: Created class.
 */

class Date
{
    /**
     * Transform mysql format datetime string to given iso format.
     *
     * @param string $strIsoFormat
     * @param string $strDateTime
     * @return string
     */
    public static function fromMysql(string $strIsoFormat, string $strDateTime): string
    {
        $strReturn = $strDateTime;

        if ($strDateTime !== "0000-00-00 00:00:00" && !empty($strDateTime)) {
            $intTimestamp = strtotime($strDateTime);

            if ($intTimestamp !== -1 && $intTimestamp !== false) {
                Carbon::setLocale('auto');

                $strReturn = Carbon::createFromTimestamp($intTimestamp)->isoFormat($strIsoFormat);
            }
        } else {
            $strReturn = "";
        }

        return $strReturn;
    }

    /**
     * Gives mysql string.
     *
     * @param string $strDateTime
     * @return string
     */
    public static function toMysql(string $strDateTime = ""): string
    {
        $strReturn = $strDateTime;

        if (empty($strDateTime)) {
            $intTimestamp = time();
        } elseif (is_numeric($strDateTime)) {
            $intTimestamp = $strDateTime;
        } else {
            $intTimestamp = strtotime($strDateTime);
        }

        if ($intTimestamp !== -1 && $intTimestamp !== false) {
            $strReturn = Carbon::createFromTimestamp($intTimestamp)->isoFormat("YYYY-MM-DD HH:mm:ss");
        }

        return $strReturn;
    }

    /**
     * @return DateTime
     */
    public static function minDate(): DateTime
    {
        $dtReturn = new DateTime("1901-12-13");

        return $dtReturn;
    }

    /**
     * @return DateTime
     */
    public static function maxDate(): DateTime
    {
        $dtReturn = new DateTime("2038-01-18");

        return $dtReturn;
    }

    /**
     * Returns Month name.
     *
     * @param string $intMonth
     * @return string
     */
    public static function getMonthName(string $intMonth): string
    {
        $intTimestamp = mktime(0, 0, 0, $intMonth, 10);

        Carbon::setLocale('auto');
        $strReturn = Carbon::createFromTimestamp($intTimestamp)->monthName;

        return $strReturn;
    }

    /**
     * Returns Short month name.
     *
     * @param string $intMonth
     * @return string
     */
    public static function getShortMonthName(string $intMonth): string
    {
        $intTimestamp = mktime(0, 0, 0, $intMonth, 10);

        Carbon::setLocale('auto');
        $strReturn = Carbon::createFromTimestamp($intTimestamp)->shortMonthName;

        return $strReturn;
    }

    /**
     * Returns the quarter for a month.
     *
     * @param int $intMonth
     * @return false|float
     */
    public static function getQuarter(int $intMonth)
    {
        $intReturn = ceil($intMonth / 3);

        return $intReturn;
    }

    /**
     * This method parses a date/time value using a defined format.
     * It returns a timestamp or false if the date could not be parsed.
     * remark: the given string can only be in english
     *
     * @param string $strDate
     * @param string $strIsoFormat
     * @return bool|int
     */
    public static function parseDate(string $strDate, string $strIsoFormat)
    {
        try {
            $objCarbon = Carbon::createFromIsoFormat($strIsoFormat, $strDate);
        } catch (InvalidFormatException $ex) {
            return false;
        }

        /**
         * If there are only date related formats and no time formats,
         * we set the time to the start of the day.
         */
        if (!static::stringContainsItemFromArray($strIsoFormat, ["HH", "mm", "ss"])) {
            $objCarbon->startOfDay();
        }

        $intReturn = $objCarbon->getTimestamp();

        //*** Check if reverse result is the same otherwise return false.
        if ($objCarbon->locale('en')->isoFormat($strIsoFormat) !== $strDate) {
            $intReturn = false;
        }

        return $intReturn;
    }

    /**
     * Parse and test a date string using a specific format.
     *
     * @param string $strDate
     * @param string $strIsoFormat
     * @param int $intMinYear
     * @param int|null $intMaxYear
     * @return DateTime|null
     */
    public static function testParsedDate(
        string $strDate,
        string $strIsoFormat,
        int $intMinYear,
        int $intMaxYear = null
    ): ?DateTime {
        $objReturn = null;

        $intTimestamp = static::parseDate($strDate, $strIsoFormat);
        $objTestDate = DateTime::createFromFormat('U', $intTimestamp);

        if ($intTimestamp !== false) {
            //*** An invalid date returns 1899 as year.
            if ($objTestDate->format("Y") < $intMinYear) {
                $intTimestamp = false;
            }

            if (!is_null($intMaxYear) && $objTestDate->format("Y") > $intMaxYear) {
                $intTimestamp = false;
            }

            if ($intTimestamp !== false) {
                $objReturn = $objTestDate;
            }
        }

        return $objReturn;
    }

    /**
     * Get the delimiter from a string formatted date.
     *
     * @param string $strDate
     * @return string|null
     */
    public static function getDateDelimiter(string $strDate): ?string
    {
        $strReturn = null;

        if (strpos($strDate, "/") !== false) {
            $strReturn = "/";
        }

        if (strpos($strDate, "-") !== false) {
            $strReturn = "-";
        }

        if (strpos($strDate, ".") !== false) {
            $strReturn = ".";
        }

        return $strReturn;
    }

    /**
     * Convert a 2 digit year in a date string to a 4 digit year.
     *
     * @param string $strDate
     * @return string
     */
    public static function fixShortYearInDate(string $strDate): string
    {
        $strReturn = $strDate;

        $strDelimiter = static::getDateDelimiter($strDate);

        if (is_null($strDelimiter)) {
            if (strlen($strDate) < 8) {
                $strNewDate = "";
                $arrDate = str_split($strDate, 2);

                foreach ($arrDate as $strPart) {
                    if ((int)$strPart > 31) {
                        $strPart = (int)$strPart + 1900;
                    }

                    $strNewDate .= $strPart;
                }

                $strReturn = $strNewDate;
            }
        } else {
            if (strlen($strDate) < 10) {
                $arrNewDate = [];
                $arrDate = explode($strDelimiter, $strDate);

                $arrLengths = array_map('strlen', $arrDate);

                if (max($arrLengths) < 4) {
                    foreach ($arrDate as $strPart) {
                        if ((int)$strPart > 31) {
                            $strPart = (int)$strPart + 1900;
                        }

                        $arrNewDate[] = $strPart;
                    }

                    $strReturn = implode($strDelimiter, $arrNewDate);
                }
            }
        }

        return $strReturn;
    }

    /**
     * This method takes a date/time value and converts it from one format to the other.
     * It returns the converted value.
     *
     * @param string $strDate
     * @param string $strInIsoFormat
     * @param string $strOutIsoFormat
     * @return string
     */
    public static function convertDate(string $strDate, string $strInIsoFormat, string $strOutIsoFormat): string
    {
        Carbon::setLocale('auto');
        return Carbon::createFromTimestamp(static::parseDate($strDate, $strInIsoFormat))->isoFormat($strOutIsoFormat);
    }

    /**
     * Determine the ordinal suffixes using the day and an array of suffixes.
     *
     * @param integer $intDay
     * @param array $arrSuffixes An array like ['th','st','nd','rd','th','th','th','th','th','th']
     * @return string The day with the suffix
     */
    public static function getOrdinalSuffix(int $intDay, array $arrSuffixes): string
    {
        $intDay = abs($intDay);
        $intMod100 = $intDay % 100;
        $strReturn =  $intDay . ($intMod100 >= 11 && $intMod100 <= 13 ? $arrSuffixes[9] :  $arrSuffixes[$intDay % 10]);

        return $strReturn;
    }

    /**
     * @param string $strDate1
     * @param string $strDate2
     * @param int $precision
     * @param array $arrDiffSingular
     * @param array $arrDiffPlural
     * @return false|string
     */
    public static function dateDifference(
        string $strDate1,
        string $strDate2,
        int $precision = 6,
        array $arrDiffSingular = array('year', 'month', 'day', 'hour', 'minute', 'second'),
        array $arrDiffPlural = array('years', 'months', 'days', 'hours', 'minutes', 'seconds')
    ) {
        /* This method calculates the difference between 2 dates and
         * returns the result in a human readable format.
        */
        if (preg_match('/\D/', $strDate1) && ($strDate1 = strtotime($strDate1)) === false) {
            return false;
        }

        if (preg_match('/\D/', $strDate2) && ($strDate2 = strtotime($strDate2)) === false) {
            return false;
        }

        if ($strDate1 > $strDate2) {
            list($strDate1, $strDate2) = array($strDate2, $strDate1);
        }

        $diffs = array(
            'year' => 0, 'month' => 0, 'day' => 0,
            'hour' => 0, 'minute' => 0, 'second' => 0
        );

        foreach (array_keys($diffs) as $interval) {
            while ($strDate2 >= ($t3 = strtotime("+1 ${interval}", $strDate1))) {
                $strDate1 = $t3;
                ++$diffs[$interval];
            }
        }

        $stack = array();
        foreach ($diffs as $interval => $num) {
            $stack[] = array($num, $interval);
        }

        $ret = array();
        $max = count($stack);
        while ($max - count($stack) < $precision && ($item = array_shift($stack)) !== null) {
            if ($item[0] > 0) {
                $strLabel = ($item[0] > 1) ?
                    $arrDiffPlural[$max - count($stack) - 1] : $arrDiffSingular[$max - count($stack) - 1];
                $ret[] = "{$item[0]} {$strLabel}";
            }
        }

        return implode(', ', $ret);
    }

    /**
     * @param int|null $intTimestamp
     * @return false|int
     */
    public static function getFirstDayTimestamp(?int $intTimestamp = null)
    {
        if (is_null($intTimestamp)) {
            $intTimestamp = time();
        }

        return mktime(0, 0, 0, (date("m", $intTimestamp)), 1, date("Y", $intTimestamp));
    }

    /**
     * @param int|null $intTimestamp
     * @return false|int
     */
    public static function getLastDayTimestamp(?int $intTimestamp = null)
    {
        if (is_null($intTimestamp)) {
            $intTimestamp = time();
        }

        return mktime(0, 0, 0, (date("m", $intTimestamp) + 1), 0, date("Y", $intTimestamp));
    }

    /**
     * @throws Exception
     */
    public static function getDateDifference($strFirst, $strSecond): DateInterval
    {
        $objFirstDate = new DateTime($strFirst);
        $objSecondDate = new DateTime($strSecond);

        return $objFirstDate->diff($objSecondDate);
    }

    /**
     * Converts deprecated strftime format to iso format.
     *
     * https://www.php.net/manual/en/function.strftime.php
     * https://carbon.nesbot.com/docs/#api-localization
     *
     * @param string $strFormat
     * @return string|null
     */
    public static function convertStrftimeFormat(string $strFormat): ?string
    {
        $strPhpDateFormat = str_replace(
            ['%a', '%A',  '%d','%e','%u','%w','%W','%b', '%h', '%B',  '%m', '%y', '%Y',  '%D',       '%F',         '%x',        '%n', '%t', '%H', '%k', '%I', '%l', '%M', '%p', '%P', '%r',         '%R',    '%S', '%T',       '%X',    '%z', '%Z', '%s', '%%', '%-e'],
            ['ddd','dddd','DD', 'D', 'd', 'E', 'W', 'MMM','MMM','MMMM','MM', 'YY', 'YYYY','MM/DD/YY', 'YYYY-MM-DD', 'MM/DD/YYYY',"\n", "\t", 'HH', 'H',  'hh', 'h',  'mm', 'A',  'a',  'hh:mm:ss A', 'HH:mm', 'ss', 'HH:mm:ss', 'H:i:s', 'ZZ', 'zz', 'X', '%',  'D'],
            $strFormat
        );

        return $strPhpDateFormat;
    }

    /**
     * Check if one of the items in the array has a match in the string.
     *
     * @param string $strLine
     * @param array $arrItems
     * @return bool
     */
    protected static function stringContainsItemFromArray(string $strLine, array $arrItems): bool
    {
        foreach($arrItems as $item) {
            if (strpos($strLine, $item) !== false) {
                return true;
            }
        }

        return false;
    }
}
