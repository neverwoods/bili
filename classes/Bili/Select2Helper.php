<?php
namespace Bili;

class Select2Helper
{
    /** @var array<int, mixed> */
    private $rows = [];

    /** @var int */
    private $total = 0;

    /**
     * @return self
     */
    public static function getInitialServerResponse()
    {
        return new Select2Helper();
    }

    /**
     * @return string
     */
    public static function getSearchValue()
    {
        $strReturn = Request::get("q");

        return $strReturn;
    }

    /**
     * @return string
     */
    public static function getSqlSearchValue()
    {
        $strReturn = "%" . self::getSearchValue() . "%";

        return $strReturn;
    }

    /**
     * @return mixed
     */
    public static function getPage()
    {
        return Request::get("page", 1);
    }

    /**
     * @return mixed
     */
    public static function getPageLength()
    {
        return Request::get("per", 10);
    }

    /**
     * @param mixed $arrRow
     * @return void
     */
    public function addRow($arrRow)
    {
        $this->rows[] = $arrRow;
    }

    /**
     * @param int $intRows
     * @return void
     */
    public function setTotalRows($intRows)
    {
        $this->total = $intRows;
    }

    /**
     * @return string|false
     */
    public function toJson()
    {
        $arrReturn = [
            "rows" => $this->rows,
            "total" => $this->total
        ];

        return json_encode($arrReturn);
    }
}
