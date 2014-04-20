<?php

namespace Bili;

/**
 * Class to hold the message logic.
 *
 * @package Bili
 */

class BubbleMessage extends ClassDynamic implements \JsonSerializable
{
    protected $message;
    protected $title;
    protected $type;
    protected $location;
    protected $timeout;
    protected $permanent;
    protected $id;

    public function __construct($message, $options = array())
    {
        $this->message = $message;
        $this->title = (isset($options["title"])) ? $options["title"] : "";
        $this->type = (isset($options["type"])) ? $options["type"] : MSG_TYPE_INFO;
        $this->location = (isset($options["location"])) ? $options["location"] : MSG_LOC_CONTAINER;
        $this->timeout = (isset($options["timeout"])) ? $options["timeout"] : 0;
        $this->permanent = (isset($options["permanent"])) ? $options["permanent"] : false;

        $this->setId();
    }

    public function setId()
    {
        $this->id = "message-" . mt_rand(10000, 100000);
    }

    /*
     * Get the CSS class for a message type.
     */
    public function getCssType()
    {
        return $this->type;
    }

    public function jsonSerialize()
    {
        return [
	        "type" => $this->type,
	        "title" => $this->title,
        	"body" => $this->message,
        	"location" => $this->location,
        	"timeout" => $this->timeout
        ];
    }
}
