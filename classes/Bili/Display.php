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

        $strTousandSeparator = ($blnShowThousandSeparator) ? Language::get("thousands_separator") : "";
        $strReturn = number_format($fltValue, $intMaxDecimal, Language::get("decimal_separator"), $strTousandSeparator);

        return Sanitize::toXhtml($strReturn);
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

        return addslashes($text);
    }
}
