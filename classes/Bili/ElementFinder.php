<?php

namespace Bili;

/**
 * Use the ElementFinder to easily fetch PunchCMS elements based on a mixture of Template and API names.
 *
 * Example
 * --------
 *
 * Element structure:
 *
 * Root
 * - Pages (template: Pages)
 * --- Home page (template: Page, API name: HomePage)
 * ----- Slider (template: Slider)
 * ------- Item (template: Item)
 * --- Contact page (template: Page)
 *
 * To get the Slider element, simply do:
 *
 * ```
 * $objSlider = ElementFinder::find("{T}Pages|{A}HomePage|{T}Slider");
 * ```
 *
 * Create an element path separated by pipes ( | ). Prefix each TemplateName selector with {T} and
 * each API name selector with {A}.
 *
 * To get multiple elements with the same template, there is a TemplateNames (note the -s) prefix: {Ts}
 * Example:
 *
 * ```
 * $objSliderItems = ElementFinder::find("{T}Pages|{A}HomePage|{T}Slider|{Ts}Item");
 * ```
 *
 * @package Bili
 */

namespace Bili;

use \PunchCMS\Client\Element;

class ElementFinder
{
    const INDICATOR_TEMPLATE = "{T}";
    const INDICATOR_TEMPLATES = "{Ts}";
    const INDICATOR_APINAME = "{A}";

    protected $elementPath = "";
    protected $element = null;
    protected $pcms = null;

    private function __construct($path)
    {
        $this->elementPath = $path;
        $this->pcms = \PunchCMS\Client\Client::getInstance();

        if (!is_object($this->pcms)) {
            throw new \Exception(
                "Could not initialize PunchCMS Client. Unable to get CMS elements without PCMS Client library.",
                E_ERROR
            );
        }
    }

    public static function find($path)
    {
        return (new static($path))->getElementByPath();
    }

    public function getElementByPath()
    {
        // Make sure to use array_filter since we absolutely don't want any empty array values.
        $arrPath = array_filter(explode("|", $this->elementPath));
        $objReturn = null;
        foreach ($arrPath as $strPathPartial) {
            $objReturn = $this->getElementByPathPrefix($strPathPartial, $objReturn);

            if (!is_object($objReturn)) {
                throw new \Exception(
                    "Couldn't not traverse through element Path '{$this->elementPath}'. Couldn't find element {$strPathPartial}",
                    E_ERROR
                );
            }
        }

        return $objReturn;
    }

    private function getElementByPathPrefix($strPathPartial, Element $objParent = null)
    {
        $objParent = (is_null($objParent)) ? $this->pcms : $objParent;
        $objReturn = null;

        $strPrefix = mb_substr($strPathPartial, 0, 3);
        $strSuffix = mb_substr($strPathPartial, 3, mb_strlen($strPathPartial));
        switch ($strPrefix) {
            case self::INDICATOR_APINAME:
                $strMethod = "get";
                break;
            case self::INDICATOR_TEMPLATES:
                $strMethod = "getElementsByTemplate";
                break;
            default:
                $strMethod = "getElementByTemplate";
        }

        if (method_exists($objParent, $strMethod) && is_callable([$objParent, $strMethod])) {
            $objElement = call_user_func_array([$objParent, $strMethod], [$strSuffix]);
        }

        if (is_object($objElement)) {
            $objReturn = $objElement;
        }

        return $objReturn;
    }
}
