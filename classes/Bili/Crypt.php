<?php

namespace Bili;

class Crypt
{
    /**
     * Generate a token using a dynamic array of parameters.
     *
     * @param array $arrInput
     * @param number $intMaxLength
     * @return string
     */
    public static function generateToken($arrInput = [], $intMaxLength = 16)
    {
        return substr(sha1(implode("", $arrInput)), 0, $intMaxLength);
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
