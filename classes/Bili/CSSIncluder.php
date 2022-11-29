<?php

namespace Bili;

/**
 * Class to hold Stylesheet include logic.
 *
 * @package Bili
 */
class CSSIncluder
{
    private $arrFiles;
    private $sourcePath;

    public function __construct($strSourcePath, $varFiles = null)
    {
        $this->sourcePath = $strSourcePath;
        $this->arrFiles = array("all" => array(), "screen" => array(), "print" => array());

        if (is_array($varFiles)) {
            if (is_array($varFiles[0])) {
                foreach ($varFiles as $value) {
                    $this->add($value);
                }
            } else {
                $this->add($varFiles);
            }
        }
    }

    public function add($arrFile)
    {
        if (is_array($arrFile)) {
            if (is_file($this->sourcePath . $arrFile["href"] . ".css")) {
                if (array_key_exists($arrFile["media"], $this->arrFiles)) {
                    array_push($this->arrFiles[$arrFile["media"]], $arrFile["href"]);
                } else {
                    throw new \Exception("Stylesheet media type \"" . $arrFile["media"] . "\" not permitted.");
                }
            } else {
                throw new \Exception("Stylesheet file \"" . $arrFile["href"] . "\" not found.");
            }
        } else {
            throw new \Exception("Added invalid stylesheet.");
        }
    }

    public function toHtml($strVersion = "")
    {
        $strReturn = "";

        foreach ($this->arrFiles as $key => $value) {
            $strReturn .= $this->renderHtml($key, $strVersion);
        }

        return $strReturn;
    }

    private function renderHtml($strMedia, $strVersion = "")
    {
        $strReturn = "";

        if (count($this->arrFiles[$strMedia]) > 0) {
            $strFilter = implode(",", $this->arrFiles[$strMedia]);

            if (!empty($strVersion)) {
                $strFilter .= ",version-" . $strVersion;
            }

            $strReturn = '<link rel="stylesheet" type="text/css" href="/css?'
                . $strFilter . '" media="' . $strMedia . '" />';
        }

        return $strReturn;
    }

    public static function render($arrFilter)
    {
        $dtLastModified = 0;

        //*** Load sources from sources directory.
        if (is_dir($GLOBALS["_PATHS"]['css'])) {
            //*** Directory exists.
            foreach ($arrFilter as $strFilter) {
                //*** Check if we are requesting a version hash. In that case we skip the file.
                if (mb_stripos($strFilter, "version-") !== 0) {
                    //*** No version cache, proceed normally.
                    $strFile = $GLOBALS["_PATHS"]['css'] . "{$strFilter}.css";
                    $dtLastModified = self::getLastModified($strFile, $dtLastModified);

                    //*** Auto check custom files.
                    if (strpos($strFilter, "-custom") === false) {
                        $strFile = $GLOBALS["_PATHS"]['css'] . "{$strFilter}-custom.css";
                        $dtLastModified = self::getLastModified($strFile, $dtLastModified);
                    }
                }
            }
        }

        //*** Check if we can send a "Not Modified" header.
        \HTTP_ConditionalGet::check($dtLastModified, true, array("maxAge" => 1200000));

        //*** Modified. Get contents.
        $strOutput = self::minify($arrFilter);

        //*** Gzip the CSS.
        $objEncoder = new \HTTP_Encoder(
            array(
                "content" => $strOutput,
                "type" => "text/css"
            )
        );

        $objEncoder->encode();
        $objEncoder->sendAll();
    }

    private static function getLastModified($strFile, $dtLastModified = null)
    {
        $intReturn = $dtLastModified;

        if (is_file($strFile)) {
            $lngLastModified = filemtime($strFile);
            if (empty($dtLastModified) || $lngLastModified > $dtLastModified) {
                $intReturn = $lngLastModified;
            }
        }

        return $intReturn;
    }

    private static function getFileContents($strFile)
    {
        $strReturn = "";

        if (is_file($strFile)) {
            $strReturn = @file_get_contents($strFile) . "\n";
        }

        return $strReturn;
    }

    private static function minify($arrFilter)
    {
        $strOutput = "";

        if (is_dir($GLOBALS["_PATHS"]['css'])) {
            //*** Directory exists.
            foreach ($arrFilter as $strFilter) {
                $strFile = $GLOBALS["_PATHS"]['css'] . "{$strFilter}.css";
                $strOutput .= self::getFileContents($strFile);

                //*** Auto check custom files.
                if (strpos($strFilter, "-custom") === false) {
                    $strFile = $GLOBALS["_PATHS"]['css'] . "{$strFilter}-custom.css";
                    $strOutput .= self::getFileContents($strFile);
                }
            }
        }

        //*** Minify the CSS and cache the result.
        $strHash = md5(implode(",", $arrFilter));
        $objCache = new \Cache_Lite($GLOBALS["_CONF"]["cache"]);
        $strReturn = $objCache->get($strHash, "css");
        if ($strReturn) {
            $strOutput = $strReturn;
        } else {
            if ($GLOBALS["_CONF"]["cache"]["caching"]) {
                $strOutput = \Minify_CSS::minify(
                    $strOutput,
                    array(
                        "preserveComments" => false
                    )
                );
            }
            $objCache->save($strOutput, $strHash, "css");
        }

        return $strOutput;
    }
}
