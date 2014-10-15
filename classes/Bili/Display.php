<?php

namespace Bili;

/**
 * Display Class
 * Holds methods for interface related methods.
 *
 * CHANGELOG
 * version 0.1.0, 21 Apr 2011
 *   NEW: Created class.
 *
 * @version 0.1.0
 * @author felix
 */
class Display
{
    /**
     * Convert HTML markup to a binary PDF
     * @param  string      $strHtml The HTML input
     * @return binary|null The binary PDF output or null if something went wrong.
     */
    public static function html2pdf($strHtml, $strFilePrefix = "document")
    {
        $varReturn = null;

        srand((double) microtime()*1000000);
        $random_number = rand();
        $sid = md5($random_number);

        $strHash         = $strFilePrefix . "-" . $sid;
        $strPdfFile     = $GLOBALS["_PATHS"]["cache"] . $strHash . ".pdf"; // TODO: Check if global exists.
        $strHtmlFile     = $GLOBALS["_PATHS"]["cache"] . $strHash . ".html";

        file_put_contents($strHtmlFile, $strHtml);
        $strInput = $strHtmlFile;
        $strOutput = $strPdfFile;

        $arrExec = array();
        $arrExec[] = $GLOBALS["_CONF"]["app"]["wkhtmltopdf"]; // TODO: Check if global exists.
        $arrExec[] = $strInput;
        $arrExec[] = $strOutput;
        $strExec = implode(" ", $arrExec);

        $blnCreated = exec($strExec);

        if (file_exists($strPdfFile)) {
            $varReturn = file_get_contents($strPdfFile);

            // Clean up
            @unlink($strHtmlFile);
            @unlink($strPdfFile);
        }

        return $varReturn;
    }

    public static function renderLink($strLabel, $strLink, $blnExternal = false, $strClass = "")
    {
        $strExternal = ($blnExternal) ? " rel=\"external\"" : "";
        $strClass = (!empty($strClass)) ? " class=\"{$strClass}\"" : "";

        return "<a href=\"{$strLink}\"{$strExternal}{$strClass}>{$strLabel}</a>";
    }

    public static function renderAddLink($strLink, $strLabel = null)
    {
        $strLabel = (!is_null($strLabel)) ? $strLabel : Language::get("add", "button");

        return self::renderLink($strLabel, $strLink, false, "addData");
    }

    public static function wrapText($strInput, $intMaxCharacters)
    {
        return wordwrap($strInput, $intMaxCharacters, "<br />");
    }

    /**
     * Get the string value for the boolean value.
     *
     * @return string string representation of a boolean value
     */
    public static function renderBoolean($blnValue, $blnEmptyOnFalse = false)
    {
        $strReturn = "";

        if ($blnValue) {
            $strReturn = Language::get("yes", "label");
        } else {
            if (!$blnEmptyOnFalse) {
                $strReturn = Language::get("no", "label");
            }
        }

        return Sanitize::toXhtml($strReturn);
    }

    /**
     * Format a numeric value according to the language settings.
     *
     * @return string string representation of a numeric value
     */
    public static function renderNumber(
        $fltValue,
        $intMaxDecimal = 2,
        $intMinDecimal = 2,
        $blnShowThousandSeparator = true
    ) {
        $fltValue = floatval($fltValue);
        $intValue = floor($fltValue);

        for ($intDecimals = 0; $fltValue != round($fltValue, $intDecimals); $intDecimals++) {
            //*** Just counting.
        }

        if ($intDecimals <= $intMinDecimal) {
            $intMaxDecimal = $intMinDecimal;
        }

        $strDecimalPoint = Language::get("decimal_separator", "global", false);
        $strThousandSeparator = Language::get("thousands_separator", "global", false);
        if (empty($strDecimalPoint) || empty($strThousandSeparator)) {
            $arrLocaleInfo = localeconv();

            if (empty($strDecimalPoint) && isset($arrLocaleInfo["decimal_point"])) {
                $strDecimalPoint = $arrLocaleInfo["decimal_point"];
            }

            if (empty($strThousandSeparator) && isset($arrLocaleInfo["thousands_sep"])) {
                $strThousandSeparator = $arrLocaleInfo["thousands_sep"];
            }
        }

        $strThousandSeparator = ($blnShowThousandSeparator) ? $strThousandSeparator : "";
        $strReturn = number_format($fltValue, $intMaxDecimal, $strDecimalPoint, $strThousandSeparator);

        return Sanitize::toXhtml($strReturn);
    }

    /**
     * Do a simple conversion from bytes to Kilobytes, Megabytes or Gigabytes.
     *
     * @param integer $intFrom
     * @param string $strTargetUnit
     * @return decimal
     */
    public static function renderBytes($intFrom, $strTargetUnit, $strSourceUnit = "B")
    {
        $decReturn = $intFrom;

        switch ($strSourceUnit) {
            case "KB":
                $decReturn = $decReturn * 1024;
                break;
            case "MB":
                $decReturn = $decReturn * 1024 * 1024;
                break;
            case "GB":
                $decReturn = $decReturn * 1024 * 1024 * 1024;
                break;
        }

        switch ($strTargetUnit) {
            case "KB":
                $decReturn = $decReturn / 1024;
                break;
            case "MB":
                $decReturn = $decReturn / 1024 / 1024;
                break;
            case "GB":
                $decReturn = $decReturn / 1024 / 1024 / 1024;
                break;
        }

        return $decReturn;
    }

    /**
     * Format a numeric value according to the language settings for a form value.
     *
     * @return string string representation of a numeric value
     */
    public static function renderFormNumber($fltValue, $intMaxDecimal = 2, $intMinDecimal = 2)
    {
        return self::renderNumber(Sanitize::toDecimal($fltValue), $intMaxDecimal, $intMinDecimal, false);
    }

    /**
     * Shorten a string to a specific length of characters.
     *
     * @param  string  $strValue
     * @param  integer $intCharLength
     * @param  boolean $blnPreserveWord
     * @param  string  $strAppend
     * @return string  The short value
     */
    public static function getShortValue($strValue, $intCharLength = 200, $blnPreserveWord = true, $strAppend = " ...")
    {
        $strReturn = $strValue;

        $strReturn = substr($strValue, 0, $intCharLength);

        if ($blnPreserveWord == true && strlen($strReturn) < strlen($strValue)) {
            $intLastSpace = strrpos($strReturn, " ");
            $strReturn = substr($strReturn, 0, $intLastSpace);
        }

        if (strlen($strReturn) < strlen($strValue)) {
            $strReturn .= $strAppend;
        }

        return $strReturn;
    }

    public static function filterForXML($text)
    {
        $strReturn = $text;

        //*** Convert HTML entities to the real characters.
        $strReturn = html_entity_decode($strReturn, ENT_COMPAT, "UTF-8");

        //*** Replace & characters with &amp;.
        $strReturn = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w{1,8});)/i', "&amp;", $strReturn);

        //*** Replace 4 other characters with XML entities.
        $strReturn = str_replace("<", "&lt;", $strReturn);
        $strReturn = str_replace(">", "&gt;", $strReturn);
        $strReturn = str_replace("\"", "&quot;", $strReturn);
        $strReturn = str_replace("'", "&apos;", $strReturn);

        return $strReturn;
    }

    /**
     * Trim, strip tags and add slashes - makes strings javascript-safe.
     * @param  string $text           Input string
     * @param  string $strAllowedTags Allowed tags for the strip_tags function. E.g. "<a><span><div>"
     * @return string Formatted string
     */
    public static function filterForJavascript($text, $strAllowedTags = "<a>")
    {
        $text = trim($text);
        $text = strip_tags($text, $strAllowedTags);
        $text = json_encode($text);

        return $text;
    }

    /**
     * Get the first paragraph from an HTML string
     *
     * For example when you have a string like this:
     * ```php
     * $strHtml = "<strong>hello</strong><p>Cool</p><p>Awesome longer text!</p>";
     * ```
     *
     * This method would return `<p>Cool</p>` since that's the first paragraph.
     * This way, you could have both a summary and a full length text in one string without having to chop off text.
     *
     * @param string $strHtml The HTML string to look for the first paragraph element
     * @return string
     */
    public static function getFirstParagraph($strHtml)
    {
        $arrResults = array();
        preg_match('%(<p[^>]*>.*?</p>)%i', $strHtml, $arrResults);

        return $arrResults[0];
    }
}
