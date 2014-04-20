<?php
namespace Bili;

class Select2Helper
{

    private $rows = [];

    private $total = 0;

    public static function getInitialServerResponse()
    {
        return new Select2Helper();
    }

    public static function getSearchValue()
    {
        $strReturn = Request::get("q");

        return $strReturn;
    }

    public static function getSqlSearchValue()
    {
        $strReturn = "%" . self::getSearchValue() . "%";

        return $strReturn;
    }

    public static function getPage()
    {
        return Request::get("page", 1);
    }

    public static function getPageLength()
    {
        return Request::get("per", 10);
    }

    public function addRow($arrRow)
    {
        $this->rows[] = $arrRow;
    }

    public function setTotalRows($intRows)
    {
        $this->total = $intRows;
    }

    public function toJson()
    {
        $arrReturn = [
            "rows" => $this->rows,
            "total" => $this->total
        ];

        return json_encode($arrReturn);
    }
}
