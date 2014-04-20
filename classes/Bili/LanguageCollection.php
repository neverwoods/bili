<?php

namespace Bili;

/** Language Collection Class v0.1.0
 * Collection that holds all available languages.
 *
 * CHANGELOG
 * version 0.1.0, 03 Apr 2006
 *   NEW: Created class.
 */

class LanguageCollection implements \Iterator
{
    private $collection = array();

    public function __construct($initArray = array())
    {
        if (is_array($initArray)) {
            $this->collection = $initArray;
        }
    }

    public function addObject($value)
    {
        /* Add an object to the collection.
         *
        * Method arguments are:
        * - object to add.
        */

        array_push($this->collection, $value);
    }

    public function count()
    {
        return count($this->collection);
    }

    public function current()
    {
        return current($this->collection);
    }

    public function next()
    {
        return next($this->collection);
    }

    public function key()
    {
        return key($this->collection);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function rewind()
    {
        reset($this->collection);
    }
}
