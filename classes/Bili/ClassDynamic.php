<?php

namespace Bili;

class ClassDynamic
{
    /**
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        $property = lcfirst($property);
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        $property = strtolower($property);
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        $strErrorMessage = "Property Error in " . get_class($this) . "::get({$property}) on line " . __LINE__ . ".";
        throw new \BadMethodCallException($strErrorMessage, 1);
    }

    /**
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function __set($property, $value)
    {
        $blnExists = false;

        $property = lcfirst($property);
        if (property_exists($this, $property)) {
            $this->$property = $value;
            $blnExists = true;
        }

        $property = strtolower($property);
        if (property_exists($this, $property)) {
            $this->$property = $value;
            $blnExists = true;
        }

        if (!$blnExists) {
            $strErrorMessage = "Property Error in " . get_class($this) . "::set({$property}) on line " . __LINE__ . ".";
            throw new \BadMethodCallException($strErrorMessage, 1);
        }
    }

    /**
     * @param string $method
     * @param array<int, mixed> $values
     * @return mixed
     */
    public function __call($method, $values)
    {
        if (substr($method, 0, 3) == "get") {
            $property = substr($method, 3);

            return $this->$property;
        }

        if (substr($method, 0, 3) == "set") {
            $property = substr($method, 3);
            $this->$property = $values[0];

            return;
        }

        $strErrorMessage = "Method Error in " . get_class($this) . "::{$method} on line " . __LINE__ . ".";
        throw new \BadMethodCallException($strErrorMessage, 1);
    }
}
