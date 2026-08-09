<?php

namespace Bili;

/**
 * Class to hold Stylesheet include logic.
 *
 * @package Bili
 */
class CSSIncluder
{
    /** @var array<string, array<int, string>> */
    private $arrFiles;
    /** @var array<string, array<string, string>> */
    private $arrLayers;
    /** @var string */
    private $sourcePath;

    /**
     * @param string $strSourcePath
     * @param array<int, array<string, string|null>>|array<string, string|null>|null $varFiles
     */
    public function __construct($strSourcePath, $varFiles = null)
    {
        $this->sourcePath = $strSourcePath;
        $this->arrFiles = array("all" => array(), "screen" => array(), "print" => array());
        $this->arrLayers = array("all" => array(), "screen" => array(), "print" => array());

        if (is_array($varFiles)) {
            if (isset($varFiles[0]) && is_array($varFiles[0])) {
                /** @var array<int, array<string, string|null>> $varFiles */
                foreach ($varFiles as $value) {
                    $this->add($value);
                }
            } else {
                /** @var array<string, string|null> $varFiles */
                $this->add($varFiles);
            }
        }
    }

    /**
     * Add a stylesheet. An entry with a "layer" is assigned to that CSS cascade
     * layer, which requires emitting it as an `@import url(...) layer(...)` inside
     * a style element instead of a plain link, so the file itself stays layer-free
     * on disk.
     *
     * @param array<string, string|null> $arrFile Keys: "href", "media", optional "layer".
     * @return void
     */
    public function add($arrFile)
    {
        if (is_array($arrFile)) {
            if (is_file($this->sourcePath . $arrFile["href"] . ".css")) {
                if (array_key_exists($arrFile["media"], $this->arrFiles)) {
                    if (empty($arrFile["layer"])) {
                        array_push($this->arrFiles[$arrFile["media"]], $arrFile["href"]);
                    } else {
                        $this->arrLayers[$arrFile["media"]][$arrFile["href"]] = $arrFile["layer"];
                    }
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

    /**
     * @param string $strVersion
     * @return string
     */
    public function toHtml($strVersion = "")
    {
        $strReturn = "";

        foreach ($this->arrFiles as $key => $value) {
            $strReturn .= $this->renderHtml($key, $strVersion);
        }

        foreach ($this->arrLayers as $key => $value) {
            $strReturn .= $this->renderLayerHtml($key, $strVersion);
        }

        return $strReturn;
    }

    /**
     * @param string $strMedia
     * @param string $strVersion
     * @return string
     */
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

    /**
     * Render the style element importing the layered files of a media type:
     * one `@import url(...) layer(...)` per layer. Each import URL is preceded
     * by a preload link so the browser fetches it in parallel instead of on
     * @import discovery.
     *
     * @param string $strMedia
     * @param string $strVersion
     * @return string
     */
    private function renderLayerHtml($strMedia, $strVersion = "")
    {
        $strPreloads = "";
        $strImports = "";
        $arrGroups = array();

        foreach ($this->arrLayers[$strMedia] as $strHref => $strLayer) {
            $arrGroups[$strLayer][] = $strHref;
        }

        foreach ($arrGroups as $strLayer => $arrHrefs) {
            $strFilter = implode(",", $arrHrefs);

            if (!empty($strVersion)) {
                $strFilter .= ",version-" . $strVersion;
            }

            $strPreloads .= '<link rel="preload" href="/css?' . $strFilter
                . '" as="style" media="' . $strMedia . '" />';
            $strImports .= '@import url("/css?' . $strFilter . '") layer(' . $strLayer . ');';
        }

        if (empty($strImports)) {
            return "";
        }

        return $strPreloads . '<style media="' . $strMedia . '">' . $strImports . '</style>';
    }

    /**
     * @param array<int, string> $arrFilter
     * @return void
     */
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

    /**
     * @param string $strFile
     * @param int|null $dtLastModified
     * @return int|null
     */
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

    /**
     * @param string $strFile
     * @return string
     */
    private static function getFileContents($strFile)
    {
        $strReturn = "";

        if (is_file($strFile)) {
            $strReturn = @file_get_contents($strFile) . "\n";
        }

        return $strReturn;
    }

    /**
     * @param array<int, string> $arrFilter
     * @return string
     */
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
