<?php

namespace Bili;

/**
 *
 * Interface Class for the Template Parser.
 *
 * @version 1.2.1
 *
 * CHANGELOG
 * 	version 1.2.1
 * 		[Felix] Added internal template handling methods and properties.
 * 	version 1.0.1
 * 		[Robin] Updated parseDynamic method
 */
class TemplateInterface
{
    protected $section;
    protected $subsection;
    protected $command;
    protected $element;
    protected $templatePath;
    protected $template;
    private static $cssIncludes = array();
    private static $headerIncludes = array();
    private static $footerIncludes = array();
    private static $jsHeaderBlocks = array();
    private static $jsFooterBlocks = array();

    /**
     * Construct an TplInterface object.
     *
     * @param integer $intCommand The current page command
     * @param integer $intElement The current page element
     */
    public function __construct($intCommand = null, $intElement = null)
    {
        $this->element = $intElement;
        $this->command = $intCommand;
    }

    /**
     * Parse a method of the Tpl impelementation dynamically.
     *
     * @param  string                 $method    Method name
     * @param  array                  $args      Array of arguments
     * @param  string                 $strPrefix Prefix for the method name, defaults to 'parse'
     * @return mixed                  The return value of the dynamic method
     * @throws BadMethodCallException If method not callable
     */
    protected function parseDynamic($method, $args = array(), $strPrefix = "parse")
    {
        $varReturn 	= null;
        $method 	= $strPrefix . $method;
        $arrMethod 	= array($this, $method);

        if (is_callable($arrMethod)) {
            $varReturn = call_user_func_array($arrMethod, $args);
        } else {
            throw new \BadMethodCallException(
                sprintf('The required method "%s" does not exist for %s', $method, get_class($this)),
                E_ERROR
            );
        }

        return $varReturn;
    }

    /**
     * Parse a HTML_Template_IT block.
     *
     * @param  string           $strBlockName The name of the block in the template file
     * @param  array            $arrVariables An array with variables that need to be parsed. Key is the variable
     *                                        name and value is the value to parse.
     * @param  HTML_Template_IT $objTemplate  Optional template object that will be used for parsing this
     *                                        particular block.
     * @return HTML_Template_IT The template object that was used for parsing.
     */
    protected function parseBlock($strBlockName, $arrVariables, &$objTemplate = null)
    {
        if (!is_object($objTemplate)) {
            $objTemplate = &$this->template;
        }

        if (is_object($objTemplate)) {
            $objTemplate->setCurrentBlock($strBlockName);

            foreach ($arrVariables as $key => $value) {
                $objTemplate->setVariable($key, $value);
            }

            $objTemplate->parseCurrentBlock();
        }

        return $objTemplate;
    }

    /**
     * Touch a HTML_Template_IT block.
     *
     * @param  string           $strBlockName The name of the block in the template file
     * @param  HTML_Template_IT $objTemplate  Optional template object that will be used for touching this
     *                                        particular block
     * @return HTML_Template_IT The template object that was used for parsing.
     */
    protected function touchBlock($strBlockName, &$objTemplate = null)
    {
        if (!is_object($objTemplate)) {
            $objTemplate = &$this->template;
        }

        if (is_object($objTemplate)) {
            $objTemplate->touchBlock($strBlockName);
        }

        return $objTemplate;
    }

    /**
     * Set the path to the template files.
     *
     * @param string $strTemplatePath
     */
    public function setTemplatePath($strTemplatePath)
    {
        $this->templatePath = $strTemplatePath;
    }

    /**
     * Get the path to the template files.
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * Initiate the internal HTML_Template_IT object that will be used for parsing.
     *
     * @param  string           $strTemplateFile Name of the template file
     * @param  string           $strTemplatePath Path to the template files
     * @return \HTML_Template_IT The template object that was initiated
     */
    public function setTemplate($strTemplateFile, $strTemplatePath = null)
    {
        if (!is_null($strTemplatePath)) {
            $this->templatePath = $strTemplatePath;
        }

        $this->template = new \HTML_Template_IT($this->templatePath);
        $this->template->loadTemplatefile($strTemplateFile);

        return $this->template;
    }

    /**
     * Get the internal HTML_Template_IT object that is used for parsing.
     *
     * @return \HTML_Template_IT The template object that was initiated
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Parse the page. Placeholder method.
     *
     * @return string The output of the Template Parser
     */
    public function parse()
    {
        return "";
    }

    /**
     * Try to resolve a command to an "action" call through the parseDynamic method.
     *
     * @param array $args
     * @return Ambigous <\Bili\mixed, NULL, mixed>
     */
    protected function resolveAction($args = array())
    {
    	return $this->parseDynamic($this->command, $args, "action");
    }

    /**
     * Add a javascript block to be parsed in the footer.
     *
     * @param string $strBlock  The name of the javascript block to be parsed.
     * @param string $strPrefix Optional custom prefix. Default: "js" (e.g. js.blockName)
     * @param array $arrVars   Optional array of variables to parse in the block.
     * @param boolean $blnReplace Indicate if the block should be added as a new block or should overwrite any existing
     */
    protected static function addScriptBlock($strBlock, $strPrefix = "js", $arrVars = array(), $blnReplace = true)
    {
        self::doAddScriptBlock($strBlock, $strPrefix, self::$jsFooterBlocks, $arrVars, $blnReplace);
    }

    /**
     * Get all footer javascript blocks
     *
     * @return array The array holding all footer blocks
     */
    protected static function getScriptBlocks()
    {
        return self::$jsFooterBlocks;
    }

    /**
     * Add a javascript block to be parsed in the header.
     *
     * @param string $strBlock  The name of the javascript block to be parsed.
     * @param string $strPrefix Optional custom prefix. Default: "js" (e.g. js.blockName)
     * @param array $arrVars   Optional array of variables to parse in the block.
     * @param boolean $blnReplace Indicate if the block should be added as a new block or should overwrite any existing
     */
    protected static function addHeaderScriptBlock($strBlock, $strPrefix = "js", $arrVars = array(), $blnReplace = true)
    {
        self::doAddScriptBlock($strBlock, $strPrefix, self::$jsHeaderBlocks, $arrVars, $blnReplace);
    }

    /**
     * Get all header javascript blocks
     *
     * @return array The array holding all footer blocks
     */
    protected static function getHeaderScriptBlocks()
    {
        return self::$jsHeaderBlocks;
    }

    /**
     * Add a script file to be included in the footer.
     *
     * @param string $strScript The name of the file without the extension
     */
    protected static function addScript($strScript)
    {
        self::doAddScript($strScript, self::$footerIncludes);
    }

    /**
     * Get all footer include files.
     *
     * @return array The array holding all include files
     */
    protected static function getScripts()
    {
        return self::$footerIncludes;
    }

    /**
     * Add a script file to be included in the header.
     *
     * @param string $strScript The name of the file without the extension
     */
    protected static function addHeaderScript($strScript)
    {
        self::doAddScript($strScript, self::$headerIncludes);
    }

    /**
     * Get all header include files.
     *
     * @return array The array holding all script include files
     */
    protected static function getHeaderScripts()
    {
        return self::$headerIncludes;
    }

    /**
     * Add a CSS file to be included in the header.
     *
     * @param string $strInclude The name of the file without the extension
     * @param string $strMedia   Optional media argument. Default: "screen"
     */
    protected static function addCss($strInclude, $strMedia = "all")
    {
        if (!empty($strInclude)) {
            if (!array_key_exists($strInclude . "." . $strMedia, self::$cssIncludes)) {
                self::$cssIncludes[$strInclude . "." . $strMedia] = array("href" => $strInclude, "media" => $strMedia);
            }
        }
    }

    /**
     * Test if a CSS file exists in the CSS path.
     *
     * @param  string  $strInclude The name of the CSS file
     * @return boolean
     */
    protected static function testCss($strInclude)
    {
        $blnReturn = false;

        if (file_exists($GLOBALS["_PATHS"]["css"] . $strInclude . ".css")) {
            $blnReturn = true;
        }

        return $blnReturn;
    }

    /**
     * Get all CSS include files.
     *
     * @return array The array holding all CSS include files
     */
    protected static function getCss()
    {
        $arrReturn = array();

        foreach (self::$cssIncludes as $arrInclude) {
            array_push($arrReturn, $arrInclude);
        }

        return $arrReturn;
    }

    private static function doAddScriptBlock($strBlock, $strPrefix, &$arrBlocks, $arrVars = array(), $blnReplace = true)
    {
        if (!empty($strBlock)) {
            $strBlock = (empty($strPrefix)) ? $strBlock : $strPrefix . "." . $strBlock;
            if (!isset($arrBlocks[$strBlock])) {
                $arrBlocks[$strBlock] = [$arrVars];
            } elseif ($blnReplace === false) {
                $arrBlocks[$strBlock][] = $arrVars;
            }
        }
    }

    private static function doAddScript($strScript, &$arrIncludes)
    {
        if (!empty($strScript)) {
            if (!in_array($strScript, $arrIncludes)) {
                array_push($arrIncludes, $strScript);
            }
        }
    }
}
