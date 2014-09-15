<?php

namespace Bili;

/**
 * Class to hold Sanitize logic.
 *
 * @package Bili
 */
class Sanitize
{
    public static function toXhtml($strOutput)
    {
        $strReturn = $strOutput;

        //*** Replace & characters with &amp;.
        self::filterAmpersandEntity($strReturn);

        //*** Replace $ characters with &#36;.
        self::filterDollarEntity($strReturn);

        //*** Replace BAD link targets with GOOD rels.
        self::filterXhtmlLinkTarget($strReturn);

        return $strReturn;
    }

    public static function toXml($strOutput)
    {
        $strReturn = $strOutput;

        //*** Replace & characters with &amp;.
        self::filterAmpersandEntity($strReturn);

        //*** Replace $ characters with &#36;.
        self::filterDollarEntity($strReturn);

        return $strReturn;
    }

    public static function toFilename($strOutput)
    {
    	$strOutput = preg_replace('/([^\w\s\d\-\.%_~,;:\[\]\|])/u', '', $strOutput);

    	return $strOutput;
    }

    /**
     * Convert any numeric input to a machine readable decimal.
     *
     * Possible input:
     * 1.541.045,45
     * 1,541,045.45
     * 1541045,45
     * 1541045.45
     *
     * Output:
     * 1541045.45
     *
     * @param  mixed   $varInput           Either number or string that needs conversion
     * @param  boolean $blnForceConversion Indicate if the input has to be converted in any case.
     *                                     Strings return 0 if true.
     * @return mixed   Either the converted value of the original value if conversion wasn't forced
     */
    public static function toDecimal($varInput, $blnForceConversion = true)
    {
        $varReturn = 0;

        if (strpos($varInput, ".") < strpos($varInput, ",")) {
            $varInput = str_replace(".", "", $varInput);
            $varInput = strtr($varInput, ",", ".");
        } else {
            $varInput = str_replace(",", "", $varInput);
        }

        $varReturn = (float) $varInput;

        // If the return value is 0 and the input was longer and conversion isn't forced we return the original value.
        if (!$blnForceConversion && $varReturn === 0.0 && strlen($varInput) > 1) {
            $varReturn = $varInput;
        }

        return $varReturn;
    }

    public static function br2nl($strInput)
    {
        $strReturn = str_replace("<br>", "\n", $strInput);
        $strReturn = str_replace("<br/>", "\n", $strReturn);
        $strReturn = str_replace("<br />", "\n", $strReturn);

        return $strReturn;
    }

    /**
     * Sanitize input to be an integer. Works on single values and arrays.
     *
     * @param  string|decimal|array $varInput
     * @param  boolean $blnDiscardInvalid Indicate if the input array should be compacted, leaving out invalid values.
     * @return Ambigous <NULL, number, multitype:number >
     */
    public static function toInteger($varInput, $blnDiscardInvalid = true)
    {
        $varReturn = null;

        if (is_array($varInput)) {
            $varReturn = array();
            foreach ($varInput as $key => $value) {
                if ($blnDiscardInvalid) {
                    if (is_numeric($value) || (int) $value > 0) {
                        $varReturn[] = (int) $value;
                    }
                } else {
                    $varReturn[$key] = (int) $value;
                }
            }
        } else {
            $varReturn = (int) $varInput;
        }

        return $varReturn;
    }

    /**
     * Sanitize input to be a numeric value. Works on single values and arrays.
     * This will retain leading zeros.
     *
     * @param  string|decimal|array $varInput
     * @param  boolean $blnDiscardInvalid Indicate if the input array should be compacted, leaving out invalid values.
     * @return Ambigous <NULL, number, multitype:number >
     */
    public static function toNumeric($varInput, $blnDiscardInvalid = true)
    {
        $varReturn = null;

        if (is_array($varInput)) {
            $varReturn = array();
            foreach ($varInput as $key => $value) {
                if ($blnDiscardInvalid) {
                    if (is_numeric($value) || (int) $value > 0) {
                        $varReturn[] = $value;
                    }
                } else {
                    $varReturn[$key] = (is_numeric($value)) ? $value : (int) $value;
                }
            }
        } else {
            $varReturn = (is_numeric($varInput)) ? $varInput : (int) $varInput;
        }

        return $varReturn;
    }

    public static function toFilename($strOutput)
    {
    	$strOutput = preg_replace('/([^\w\s\d\-\.%_~,;:\[\]\|])/u', '', $strOutput);

    	return $strOutput;
    }

    /**
     * Sanitize a string to a pure ascii string. No special characters or any other fancy UTF-8 stuff.
     *
     * @param string $strInput
     * @return string
     */
    public static function toAscii($strInput)
    {
        $strReturn = $strInput;

        $strNew = iconv("utf-8", "ascii//TRANSLIT", $strInput);
        if ($strNew !== false) {
            $strReturn = $strNew;
        }

        return $strReturn;
    }

    private static function filterAmpersandEntity(&$text)
    {
        $text = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w{1,8});)/i', "&amp;", $text);
    }

    private static function filterDollarEntity(&$text)
    {
        $text = str_replace("$", "&#36;", $text);
    }

    private static function filterXhtmlLinkTarget(&$text)
    {
        $text = str_ireplace("target=\"_blank\"", "rel=\"external\"", $text);
        $text = str_ireplace("target=\"_top\"", "rel=\"external\"", $text);
    }
}
