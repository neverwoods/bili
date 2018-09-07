<?php

namespace Bili;

/**
 * Helper class for the Datatable javascript library.
 *
 * @package Bili
 */
class DataTableHelper
{
    /**
     * Get a default response array for server side results.
     *
     * @return array
     */
    public static function getInitialServerResponse()
    {
        return [
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "sEcho" => self::getEcho(),
            "aaData" => []
        ];
    }

    /**
     * Get the "echo" variable sent by datatable. This value has to be included in the response.
     *
     * @return string
     */
    public static function getEcho()
    {
        $strReturn = Request::get("sEcho");

        return $strReturn;
    }

    /**
     * Get the first order column that was send by the client.
     *
     * @param string $strDefaultColumn
     * @param array|null $arrWhiteList
     * @return string
     */
    public static function getOrderColumn($strDefaultColumn, $arrWhiteList = null)
    {
        $strReturn = $strDefaultColumn;

        $arrColumns = static::getOrderColumns($strDefaultColumn, $arrWhiteList);
        if (count($arrColumns) > 0) {
            $strReturn = array_shift($arrColumns);
        }

        return $strReturn;
    }

    /**
     * Get all order columns that were sent by the client.
     *
     * @param string $strDefaultColumn
     * @param array|null $arrWhiteList
     * @return array
     */
    public static function getOrderColumns($strDefaultColumn, $arrWhiteList = null)
    {
        $arrReturn = [$strDefaultColumn];
        $arrWhitelisted = [];

        $intColumns = Request::get("iSortingCols");
        for ($intCount = 0; $intCount < $intColumns; $intCount++) {
            $intOrderColumn = Request::get("iSortCol_{$intCount}");
            if (is_numeric($intOrderColumn)) {
                $strColumn = Request::get("mDataProp_" . $intOrderColumn);
                if (!empty($strColumn)) {
                    if (is_null($arrWhiteList)
                            || (!is_null($arrWhiteList) && in_array($strColumn, $arrWhiteList))) {
                        $arrWhitelisted[] = $strColumn;
                    }
                }
            }
        }

        if (count($arrWhitelisted) !== 0) {
            $arrReturn = $arrWhitelisted;
        }

        return $arrReturn;
    }

    /**
     * Check if a single column or array of columns have been send as order column(s) by the client.
     *
     * @param array|string $varColumn
     * @param string $strDefaultColumn
     * @param array|null $arrWhiteList
     * @return bool
     */
    public static function hasOrderColumn($varColumn, $strDefaultColumn, $arrWhiteList = null)
    {
        $blnReturn = false;

        $arrOrderColumns = static::getOrderColumns($strDefaultColumn, $arrWhiteList);

        if (is_array($varColumn)) {
            $blnReturn = (count(array_intersect($arrOrderColumns, $varColumn)) > 0);
        } else {
            $blnReturn = in_array($varColumn, $arrOrderColumns);
        }

        return $blnReturn;
    }

    /**
     * Get the order direction for the first or a specific order column.
     *
     * @param string $strDefaultDirection
     * @param string|null $strOrderColumn
     * @return string
     */
    public static function getOrderDirection($strDefaultDirection, $strOrderColumn = null)
    {
        $strReturn = $strDefaultDirection;

        $arrWhiteList = ["Asc", "Desc"];

        if (!is_null($strOrderColumn)) {
            $intColumns = Request::get("iSortingCols");
            for ($intCount = 0; $intCount < $intColumns; $intCount++) {
                $intOrderColumn = Request::get("iSortCol_{$intCount}");
                if (is_numeric($intOrderColumn)) {
                    $strColumn = Request::get("mDataProp_" . $intOrderColumn);
                    if (!empty($strColumn) && $strColumn == $strOrderColumn) {
                        $strReturn = ucfirst(Request::get("sSortDir_{$intCount}", $strDefaultDirection));
                        if (!in_array($strReturn, $arrWhiteList)) {
                            $strReturn = $strDefaultDirection;
                        }

                        break;
                    }
                }
            }
        } else {
            $strReturn = ucfirst(Request::get("sSortDir_0", $strDefaultDirection));
            if (!in_array($strReturn, $arrWhiteList)) {
                $strReturn = $strDefaultDirection;
            }
        }

        return $strReturn;
    }

    /**
     * Get the search query sent by the client.
     *
     * @return string
     */
    public static function getSearchValue()
    {
        $strReturn = Request::get("sSearch");

        return $strReturn;
    }

    /**
     * Get the search query sent by the client, formatted for SQL.
     *
     * @return string
     */
    public static function getSqlSearchValue()
    {
        $strReturn = "%" . self::getSearchValue() . "%";

        return $strReturn;
    }

    /**
     * Get the requested page by the client.
     *
     * @return int
     */
    public static function getPage()
    {
        $intReturn = 1;

        if (self::getPageLength() > 0) {
            $intReturn = floor(self::getPageStart() / self::getPageLength()) + 1;
        }

        return $intReturn;
    }

    /**
     * Get the first item index requested by the client.
     *
     * @return int
     */
    public static function getPageStart()
    {
        return (int)Request::get("iDisplayStart", 0);
    }

    /**
     * Get the total amount of items requested by the client.
     *
     * @return int
     */
    public static function getPageLength()
    {
        return (int)Request::get("iDisplayLength", 10);
    }
}
