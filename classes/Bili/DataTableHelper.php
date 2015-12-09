<?php

namespace Bili;

class DataTableHelper
{
    public static function getInitialServerResponse()
    {
        return [
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "sEcho" => self::getEcho(),
            "aaData" => []
        ];
    }

    public static function getEcho()
    {
        $strReturn = Request::get("sEcho");

        return $strReturn;
    }

    public static function getOrderColumn($strDefaultColumn, $arrWhiteList = null)
    {
        $strReturn = $strDefaultColumn;

        $intOrderColumn = Request::get("iSortCol_0");
        if (is_numeric($intOrderColumn)) {
            $strColumn = Request::get("mDataProp_" . $intOrderColumn);
            if (!empty($strColumn)) {
                if (is_null($arrWhiteList)
                        || (!is_null($arrWhiteList) && in_array($strColumn, $arrWhiteList))) {
                    $strReturn = $strColumn;
                }
            }
        }

        return $strReturn;
    }

    public static function getOrderDirection($strDefaultDirection)
    {
        $strReturn = ucfirst(Request::get("sSortDir_0", $strDefaultDirection));

        return $strReturn;
    }

    public static function getSearchValue()
    {
        $strReturn = Request::get("sSearch");

        return $strReturn;
    }

    public static function getSqlSearchValue()
    {
        $strReturn = "%" . self::getSearchValue() . "%";

        return $strReturn;
    }

    public static function getPage()
    {
        return floor(self::getPageStart() / self::getPageLength()) + 1;
    }

    public static function getPageStart()
    {
        return (int)Request::get("iDisplayStart", 0);
    }

    public static function getPageLength()
    {
        return (int)Request::get("iDisplayLength", 10);
    }
}
