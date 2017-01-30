<?php

namespace Bili;

class Crypt
{
    /**
     * Generate a token using a dynamic array of parameters.
     *
     * @param array $arrInput
     * @param int|number $intMaxLength
     * @return string
     */
    public static function generateToken($arrInput = [], $intMaxLength = 40)
    {
        $strReturn = null;

        if (count($arrInput) > 0) {
            $intMaxLength = ($intMaxLength > 16) ? 16 : $intMaxLength;
            $strReturn = substr(sha1(implode("", $arrInput)), 0, $intMaxLength);
        } else {
            $strChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

            if ($intMaxLength < 1) {
                throw new \InvalidArgumentException('Length must be a positive integer');
            }

            $alphaMax = strlen($strChars) - 1;
            if ($alphaMax < 1) {
                throw new \InvalidArgumentException('Invalid alphabet');
            }

            for ($i = 0; $i < $intMaxLength; ++$i) {
                $strReturn .= substr($strChars, random_int(0, $alphaMax), 1);
            }
        }

        return $strReturn;
    }

    public static function doEncode($in)
    {
        if (is_numeric($in) && $in > 0) {
            $key = '7398541620';
            $out = "";

            for ($i=0; $i < strlen($in); $i++) {
                $out .= $key[substr($in, $i, 1)]; // Encode string according to key.
            }

            if (strlen($out) < 7) {
                $padding = (7 - strlen($out));
                $out .= substr(($in * 534648), 0, $padding); // Add padding characters.
                $out .= $padding; // Add number of padding characters.
            } else {
                $out .= '0'; // No padding characters.
            }

            return $out;
        }

        return false;
    }

    public static function doDecode($in)
    {
        if (is_numeric($in) && $in > 0) {
            $key = '7398541620';
            $padding = substr($in, -1);
            $out = "";

            if ($padding > 0) {
                $in = substr($in, 0, 0 - (1 + $padding)); // Remove padding characters.
            } else {
                $in = substr($in, 0, -1); // Remove the padding.
            }

            for ($i=0; $i<strlen($in); $i++) {
                $out .= strpos($key, $in[$i]); // Decode string according to key.
            }

            return $out;
        }

        return false;
    }
}
