<?php

namespace Bili;

class Rewrite
{
    public static $instance             = null;
    public static $sections             = [];
    public static $subsections             = [];
    public static $commands                = [];
    public static $parseTypes            = [];
    public static $defaultSection       = null;
    public static $defaultSubSection    = null;
    public static $defaultCommand         = null;
    public static $defaultParser        = null;
    private $department                    = null;
    private $section                    = null;
    private $subsection                    = null;
    private $command                    = null;
    private $element                    = null;
    private $parser                        = null;
    private $parameters                    = null;
    private $reservedParameters         = array("view");
    private $attributes                 = ["first"];

    private function __construct()
    {
        /* Private constructor to insure singleton behaviour */
    }

    public static function singleton(
        $arrSections,
        $arrSubSections,
        $arrCommands,
        $arrParseTypes,
        $intDefaultSection = null,
        $intDefaultSubSection = null,
        $intDefaultCommand = null,
        $intDefaultParser = null
    ) {
        /* Method to initially instanciate the class */
        self::$instance = new Rewrite();
        self::$instance->setSections($arrSections);
        self::$instance->setSubSections($arrSubSections);
        self::$instance->setCommands($arrCommands);
        self::$instance->setParseTypes($arrParseTypes);

        if (!is_null($intDefaultSection)) {
            self::$instance->setDefaultSection($intDefaultSection);
        }

        if (!is_null($intDefaultSubSection)) {
            self::$instance->setDefaultSubSection($intDefaultSubSection);
        }

        if (!is_null($intDefaultCommand)) {
            self::$instance->setDefaultCommand($intDefaultCommand);
        }

        if (!is_null($intDefaultParser)) {
            self::$instance->setDefaultParser($intDefaultParser);
        }

        return self::$instance;
    }

    /**
     * Get a static instance of the Rewrite object.
     *
     * @return Rewrite The current static Rewrite object
     */
    public static function getInstance()
    {
        /* Get the singleton instance for this class */

        if (is_null(self::$instance)) {
            self::$instance = new Rewrite();
        }

        return self::$instance;
    }

    public function setSections($arrValue)
    {
        self::$sections = $arrValue;
    }

    public function setSubSections($arrValue)
    {
        self::$subsections = $arrValue;
    }

    public function setCommands($arrValue)
    {
        self::$commands = $arrValue;
    }

    public function setParseTypes($arrValue)
    {
        self::$parseTypes = $arrValue;
    }

    public function setDefaultSection($intValue)
    {
        self::$defaultSection = $intValue;
    }

    public function getDefaultSection()
    {
        return self::$defaultSection;
    }

    public function setDefaultSubSection($intValue)
    {
        self::$defaultSubSection = $intValue;
    }

    public function setDefaultCommand($intValue)
    {
        self::$defaultCommand = $intValue;
    }

    public function setDefaultParser($intValue)
    {
        self::$defaultParser = $intValue;
    }

    public function getDepartment()
    {
        if (is_null($this->department)) {
            $this->getRewrite();
        }

        return $this->department;
    }

    public function getSection()
    {
        if (is_null($this->section)) {
            $this->getRewrite();
        }

        return $this->section;
    }

    public function getSubSection()
    {
        if (is_null($this->subsection)) {
            $this->getRewrite();
        }

        return $this->subsection;
    }

    public function getCommand()
    {
        if (is_null($this->command)) {
            $this->getRewrite();
        }

        return $this->command;
    }

    public function getElement()
    {
        if (is_null($this->element)) {
            $this->getRewrite();
        }

        return $this->element;
    }

    public function getParseType()
    {
        if (is_null($this->parser)) {
            $this->getRewrite();
        }

        return $this->parser;
    }

    public function getParameters()
    {
        if (is_null($this->parameters)) {
            $this->getRewrite();
        }

        return $this->parameters;
    }

    /**
     * Get a named parameter from the URL.
     * @param  string   $strKey           The name of the parameter
     * @param  mixed    $alternateValue   An alternate value if the parameter is not set
     * @param  function $validateFunction A validation lambda that returns FALSE if the parameter is not valid
     * @return mixed    The named parameter, the alternate value or an empty string
     */
    public function getParameter($strKey, $alternateValue = "", $validateFunction = null)
    {
        $strReturn = $alternateValue;

        $arrParameters = $this->parameters;
        if (is_array($arrParameters) && isset($arrParameters[$strKey])) {
            if (is_callable($validateFunction)) {
                $blnReturn = call_user_func($validateFunction, $arrParameters[$strKey]);
                if ($blnReturn !== false) {
                    $strReturn = $arrParameters[$strKey];
                }
            } else {
                $strReturn = $arrParameters[$strKey];
            }
        }

        return $strReturn;
    }

    /**
     * Get the current active URL.
     *
     * @param bool $blnIncludeDepartment
     * @param null|string|array $varExcludeParameters
     * @return string
     */
    public function getCurrentUrl($blnIncludeDepartment = true, $varExcludeParameters = null)
    {
        //*** Exclude the department if provided.
        $varDepartment = ($blnIncludeDepartment) ? $this->getDepartment() : null;

        //*** Filter excluded parameters if provided.
        $arrParameters = $this->getParameters();
        if (is_array($arrParameters) && !is_null($varExcludeParameters)) {
            if (!is_array($varExcludeParameters)) {
                $varExcludeParameters = [$varExcludeParameters];
            }

            $arrFilteredParameters = [];
            foreach ($arrParameters as $key => $value) {
                if (!in_array($key, $varExcludeParameters)) {
                    $arrFilteredParameters[$key] = $value;
                }
            }

            $arrParameters = $arrFilteredParameters;
        }

        $strReturn = $this->getUrl(
            $this->getSection(),
            $this->getCommand(),
            $this->getElement(),
            $this->getParseType(),
            $this->getSubSection(),
            $arrParameters,
            $varDepartment
        );

        return $strReturn;
    }

    public function getUrl(
        $intSection,
        $intCommand = null,
        $intElement = null,
        $strParseType = null,
        $intSubSection = null,
        $arrParameters = null,
        $intDepartment = null,
        $strFragment = null
    ) {
        //*** Convert navigational elements to an URL.
        $strReturn = "/";

        //*** Department.
        if (!is_null($intDepartment) && ctype_digit(strval($intDepartment))) {
            $strReturn .= $this::encode($intDepartment) . "/";
        }

        //*** Section.
        foreach (self::$sections as $key => $value) {
            if ($value == $intSection) {
                $strReturn .= urlencode($key);
                break;
            }
        }

        //*** Sub section.
        if (!is_null($intSubSection)) {
            foreach (self::$subsections as $key => $value) {
                if ($value == $intSubSection) {
                    $strReturn .= "/" . urlencode($key);
                    break;
                }
            }
        }

        //*** Command.
        if (!is_null($intCommand)) {
            foreach (self::$commands as $key => $value) {
                if ($value == $intCommand) {
                    $strReturn .= "/" . urlencode($key);
                    break;
                }
            }
        }

        //*** Element.
        if (!is_null($intElement)) {
            if (ctype_digit(strval($intElement))) {
                $intElement = $this::encode($intElement);
            }
            $strReturn .= "/" . urlencode($intElement);
        }

        //*** Parameters.
        if (!is_null($arrParameters) && is_array($arrParameters)) {
            foreach ($arrParameters as $key => $value) {
                //*** Prevent setting of reserved parameters
                if (!in_array($key, $this->reservedParameters)) {
                    if (ctype_digit(strval($value))) {
                        $value = $this::encode($value);
                    }

                    $strReturn .= "/" . urlencode($key) . "/" . urlencode($value);
                }
            }
        }

        //*** Parse type.
        if (!is_null($strParseType)) {
            foreach (self::$parseTypes as $key => $value) {
                if ($value == $strParseType && !empty($key)) {
                    $strReturn .= "/view/" . urlencode($key);
                    break;
                }
            }
        }

        //*** Fragment.
        if (!is_null($strFragment)) {
            $strReturn .= $strFragment;
        }

        return $strReturn;
    }

    private function getRewrite()
    {
        //*** Extract the logic from the URL.
        $strRewrite    = Request::get('rewrite');

        if (!empty($strRewrite)) {
            $strRewrite = rtrim($strRewrite, " \/");

            $blnHasSubsection = false;
            $arrUrl = explode("/", $strRewrite);

            $blnHasDepartment = false;
            if (count($arrUrl) > 0) {
                if (ctype_digit(strval($arrUrl[0]))) {
                    $this->department = $this::decode($arrUrl[0]);
                    $blnHasDepartment = true;
                }
            }

            foreach ($arrUrl as $key => $value) {
                //*** Department logic.
                if ($blnHasDepartment) {
                    if ($key == 0) {
                        continue;
                    }

                    //*** Reset key so the rest of the logic works.
                    $key = $key - 1;
                }

                switch ($key) {
                    case 0:
                        //*** Section.
                        if (isset(self::$sections[$value])) {
                            $this->section = self::$sections[$value];
                        }

                        break;
                    case 1:
                        //*** Sub section or command.
                        if (isset(self::$subsections[$value])) {
                            $this->subsection = self::$subsections[$value];
                            $blnHasSubsection = true;
                        } else {
                            if (isset(self::$commands[$value])) {
                                $this->command = self::$commands[$value];
                            }
                        }

                        break;
                    case 2:
                        //*** Command, element or parameters.
                        if ($blnHasSubsection) {
                            if (isset(self::$commands[$value])) {
                                $this->command = self::$commands[$value];
                            }
                        } else {
                            if (ctype_digit(strval($value))) {
                                $this->element = $this::decode($value);
                            } else if (in_array($value, $this->attributes)) {
                                $this->element = $value;
                            } else {
                                $this->parameters = $this->arrayToAssociated(
                                    array_slice($arrUrl, ($blnHasDepartment) ? $key + 1 : $key)
                                );
                                break 2;
                            }
                        }

                        break;
                    case 3:
                        //*** Element or parameters.
                        if ($blnHasSubsection) {
                            if (ctype_digit(strval($value))) {
                                $this->element = $this::decode($value);
                            } else if (in_array($value, $this->attributes)) {
                                $this->element = $value;
                            } else {
                                $this->parameters = $this->arrayToAssociated(
                                    array_slice($arrUrl, ($blnHasDepartment) ? $key + 1 : $key)
                                );
                                break 2;
                            }
                        } else {
                            $this->parameters = $this->arrayToAssociated(
                                array_slice($arrUrl, ($blnHasDepartment) ? $key + 1 : $key)
                            );
                            break 2;
                        }

                        break;
                    case 4:
                        //*** Parameters.
                        $this->parameters = $this->arrayToAssociated(
                            array_slice($arrUrl, ($blnHasDepartment) ? $key + 1 : $key)
                        );

                        break 2;
                }
            }

            //*** Parse type.
            $value = $this->getParameter("view");
            if (!empty($value) && array_key_exists($value, self::$parseTypes)) {
                $this->parser = self::$parseTypes[$value];
            }

            //*** Remove reserved parameters
            $this->cleanupParameters();

            //*** Defaults.
            if (empty($this->subsection)) {
                $this->subsection = self::$defaultSubSection;
            }

            if (empty($this->command)) {
                $this->command = self::$defaultCommand;
            }

            if (empty($this->parser)) {
                $this->parser = self::$defaultParser;
            }
        } else {
            if (!is_null(self::$defaultSection)) {
                Request::redirect(
                    $this->getUrl(
                        self::$defaultSection,
                        self::$defaultCommand,
                        null,
                        self::$defaultParser,
                        self::$defaultSubSection
                    )
                );
            }
        }
    }

    private function cleanupParameters()
    {
        if (is_array($this->parameters)) {
            foreach ($this->parameters as $key => $value) {
                if (in_array($key, $this->reservedParameters)) {
                    //*** This is a reserved parameter and should be removed.
                    unset($this->parameters[$key]);
                }
            }
        }
    }

    private function arrayToAssociated($arrInput, $arrReservedKeys = array())
    {
        $arrReturn = array();

        if (!(count($arrInput) & 1)) {
            for ($count = 0; $count < count($arrInput); $count++) {
                $value = $arrInput[$count + 1];
                if (ctype_digit(strval($value))) {
                    $value = $this::decode($value);
                }

                $arrReturn[$arrInput[$count]] = $value;

                $count++;
            }
        }

        return $arrReturn;
    }

    public static function encode($varInput)
    {
        $varReturn = null;

        if (is_array($varInput)) {
            $varReturn = array();
            foreach ($varInput as $key => $value) {
                $varReturn[$key] = Crypt::doEncode($value);
            }
        } else {
            $varReturn = Crypt::doEncode($varInput);
        }

        return $varReturn;
    }

    public static function decode($varInput)
    {
        $varReturn = null;

        if (is_array($varInput)) {
            $varReturn = array();
            foreach ($varInput as $key => $value) {
                $varReturn[$key] = Crypt::doDecode($value);
            }
        } else {
            $varReturn = Crypt::doDecode($varInput);
        }

        return $varReturn;
    }
}
