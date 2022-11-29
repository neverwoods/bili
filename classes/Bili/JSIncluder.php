<?php

namespace Bili;

/**
 * Class to hold Javascript include logic.
 *
 * @package Bili
 */
class JSIncluder
{
    private $arrClasses;
    private $sourcePath;

    public function __construct($strSourcePath, $varClasses = null)
    {
        $this->sourcePath = $strSourcePath;
        $this->arrClasses = array();

        if (is_array($varClasses)) {
            foreach ($varClasses as $value) {
                $this->add($value);
            }
        } elseif (is_string($varClasses)) {
            $this->add($varClasses);
        }
    }

    public function add($strClass)
    {
        if (is_file($this->sourcePath . $strClass . ".js")) {
            array_push($this->arrClasses, $strClass);
        } else {
            throw new \Exception("Javascript class file \"{$strClass}\" not found.");
        }
    }

    public function toHtml($strVersion = "")
    {
        $strReturn = "";

        if (count($this->arrClasses) > 0) {
            $strFilter = implode(",", $this->arrClasses);
            if (!empty($strVersion)) {
                $strFilter .= ",version-" . $strVersion;
            }

            $strReturn = "<script type=\"text/javascript\" src=\"/js?{$strFilter}\"></script>";
        }

        return $strReturn;
    }

    public static function render($arrFilter)
    {
        $dtLastModified = 0;

        //*** Load sources from sources directory.
        if (is_dir($GLOBALS["_PATHS"]['js'])) {
            //*** Directory exists.
            foreach ($arrFilter as $strFilter) {
                //*** Check if we are requesting a version hash. In that case we skip the file.
                if (mb_stripos($strFilter, "version-") !== 0) {
                    //*** No version cache, proceed normally.
                    $strFile = $GLOBALS["_PATHS"]['js'] . $strFilter . ".js";
                    if (is_file($strFile)) {
                        $lngLastModified = filemtime($strFile);
                        if (empty($dtLastModified) || $lngLastModified > $dtLastModified) {
                            $dtLastModified = $lngLastModified;
                        }
                    }
                }
            }
        }

        //*** Check if we can send a "Not Modified" header.
        \HTTP_ConditionalGet::check($dtLastModified, true, array("maxAge" => 1200000));

        //*** Modified. Get contents.
        $strOutput = self::minify($arrFilter);

        //*** Gzip the Javascript.
        $objEncoder = new \HTTP_Encoder(
            array(
                "content" => $strOutput,
                "type" => "text/javascript"
            )
        );

        $objEncoder->encode();
        $objEncoder->sendAll();
    }

    private static function minify($arrFilter)
    {
        $strOutput = "";

        if (is_dir($GLOBALS["_PATHS"]['js'])) {
            //*** Directory exists.
            foreach ($arrFilter as $strFilter) {
                $strFile = $GLOBALS["_PATHS"]['js'] . $strFilter . ".js";
                if (is_file($strFile)) {
                    $strOutput .= @file_get_contents($strFile) . "\n";
                }
            }
        }

        //*** Minify the Javascript and cache the result.
        $strHash = md5(implode(",", $arrFilter));
        $objCache = new \Cache_Lite($GLOBALS["_CONF"]["cache"]);
        $strReturn = $objCache->get($strHash, "js");
        if ($strReturn) {
            $strOutput = $strReturn;
        } else {
            if ($GLOBALS["_CONF"]["cache"]["caching"]) {
                $strOutput = \JSMin::minify($strOutput);
            }

            $objCache->save($strOutput, $strHash, "js");
        }

        return $strOutput;
    }
}
