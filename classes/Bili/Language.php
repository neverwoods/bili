<?php

namespace Bili;

/**
 * Language Selection Class v0.1.2
 * Used to get the default language, set a specific language and list
 * available languages.
 *
 * CHANGELOG
 * version 0.1.2, 26 Mar 2013
 *   NEW: Show translation key and category
 * version 0.1.1, 19 Mar 2013
 *   FIX: Made the $_LANG variable global.
 * version 0.1.0, 03 Apr 2006
 *   NEW: Created class.
 */

class Language
{
    private static $instance         = null;
    private static $sanitizeType    = null;
    private static $languages         = array();
    private static $error            = "TRANSLATION '%s' NOT FOUND IN '%s'.";
    public $name             = "";
    public $language         = "";
    private $defaultLang     = "";
    private $langPath         = "";
    private $langOverwritePath     = null;
    private $activeLang     = "";

    private function __construct($strLang, $langPath, $overwritePath)
    {
        $this->defaultLang = $strLang;
        $this->langPath = $langPath;
        $this->langOverwritePath = $overwritePath;

        $this->getLang();
    }

    public static function singleton($strLang = "english-utf-8", $langPath = "./lng/", $strOverwritePath = null)
    {
        self::$instance = new Language($strLang, $langPath, $strOverwritePath);

        return self::$instance;
    }

    /**
     *
     * @return Language An instance of Language
     */
    public static function getInstance()
    {
        /* Get the singleton instance for this class */

        if (is_null(self::$instance)) {
            self::$instance = new Language();
        }

        return self::$instance;
    }

    public static function setSanitize($strSanitizeType)
    {
        self::$sanitizeType = $strSanitizeType;
    }

    public function getActiveLang()
    {
        return $this->activeLang;
    }

    public static function get($strName, $strCategory = "global", $blnReturnError = true)
    {
        //*** Get a translation from the language file.
        $strReturn = ($blnReturnError) ? sprintf(self::$error, $strName, $strCategory) : "";

        if (isset(self::$languages[$strCategory][$strName])) {
            $strReturn = self::$languages[$strCategory][$strName];
        }

        //*** Output sanitisation?
        switch (self::$sanitizeType) {
            case "xhtml":
                if (class_exists("Bili\\Sanitize", true)) {
                    $strReturn = Sanitize::toXhtml($strReturn);
                }

                break;
        }

        return $strReturn;
    }

    public function getLang($strLang = "")
    {
        /* Get a specific language or, if argument is empty, get the
         * language by checking session, cookie and default.
         */
        $blnReturn = false;

        if (empty($strLang)) {
            if (!empty($_SESSION['language']) && file_exists($this->langPath . "/" . $_SESSION['language'] . ".php")) {
                //*** Session variable exists. Load the language file.
                $this->activeLang = $_SESSION['language'];
            } elseif (!empty($_COOKIE['language'])
                    && file_exists($this->langPath . "/" . $_COOKIE['language'] . ".php")) {
                //*** Cookie variable exists. Load the language file.
                $this->activeLang = $_COOKIE['language'];
            } elseif (file_exists($this->langPath . "/" . $this->defaultLang . ".php")) {
                //*** Load default language file.
                $this->activeLang = $this->defaultLang;
            }
        } elseif (file_exists($this->langPath . "/" . $strLang . ".php")) {
            //*** Load the specific language file.
            $this->activeLang = $strLang;
        }

        //*** Really load the file.
        if (!empty($this->activeLang)) {
            require($this->langPath . "/" . $this->activeLang . ".php");

            if (file_exists($this->langOverwritePath . "/" . $this->activeLang . ".php")) {
                require($this->langOverwritePath . "/" . $this->activeLang . ".php");
            }

            //*** Check if the expected variable exists.
            (isset($_LANG)) ? self::$languages = $_LANG : self::$languages = array();

            //*** Set internal variables.
            $this->name = $this->activeLang;

            $arrTemp = explode("-", $this->activeLang);
            $this->language = str_replace("_", " ", $arrTemp[0]);

            $this->setLocale();

            $blnReturn = true;
        }

        return $blnReturn;
    }

    public function setLang($strLang)
    {
        //*** Set a specific language and write it to the session and cockie.
        $blnReturn = false;

        //*** Check if the language file exists and is different from the current language.
        if (file_exists($this->langPath . "/" . $strLang . ".php")
                && ($strLang !== $this->activeLang
                    || count(self::$languages) === 0)) {
            //*** Write to cookie.
            setcookie('language', $strLang, time()+60*60*24*30, '/');

            //*** Write to session.
            $_SESSION['language'] = $strLang;

            //*** Load new language file;
            $this->getLang($strLang);
            $blnReturn = true;
        }

        return $blnReturn;
    }

    public function getLangs()
    {
        $objReturn = new LanguageCollection();

        //*** List all files in the language directory.
        if (is_dir($this->langPath)) {
            $dirHandle = opendir($this->langPath);
            if ($dirHandle) {
                while (($objFile = readdir($dirHandle)) !== false) {
                    if (is_file($this->langPath . $objFile)) {
                        //*** Create Language_File object and set properties,
                        $objLanguage = new LanguageFile();
                        $objLanguage->name = basename($objFile, ".php");

                        $arrTemp = explode("-", $objFile);
                        $objLanguage->language = str_replace("_", " ", $arrTemp[0]);

                        //*** Add to the collection.
                        $objReturn->addObject($objLanguage);
                    }
                }
                closedir($dirHandle);
            }
        }

        return $objReturn;
    }

    public function setLocale()
    {
        $varReturn = false;

        $strLocale = $this->get("locale");
        $varReturn = setlocale(LC_ALL, $strLocale);

        return $varReturn;
    }
}
