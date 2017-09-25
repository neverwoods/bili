<?php

namespace Bili;

/**
 * Class to hold the message logic.
 *
 * @package Bili
 *
 * @method void setMessage(string $value) Sets the message body
 * @method string getMessage() Returns the message body
 * @method void setTitle(string $value) Sets the message title
 * @method string getTitle() Returns the message title
 * @method void setType(string $value) Sets the message type
 * @method string getType() Returns the message type
 * @method void setIcon(string $value) Sets the message icon
 * @method string getIcon() Returns the message icon
 * @method void setLocation(int $value) Sets the message location
 * @method int getLocation() Returns the message location
 * @method void setTimeout(int $value) Sets the message time out in milliseconds
 * @method int getTimeout() Returns the message time out in milliseconds
 * @method void setPermanent(bool $value) Sets if the message should be shown permanently
 * @method bool getPermanent() Returns if the message should be shown permanently
 * @method void setDismiss(bool $value) Sets if the message can be dismissed
 * @method bool getDismiss() Returns if the message can be dismissed
 * @method void setKey(string $value) Sets the message key
 * @method string getKey() Returns the message key
 * @method string getId() Returns the message id
 */

class BubbleMessage extends ClassDynamic implements \JsonSerializable
{
    const MSG_TYPE_INFO = "info";
    const MSG_TYPE_ERROR = "error";
    const MSG_TYPE_WARNING = "warning";
    const MSG_TYPE_CONFIRM = "success";

    const MSG_ICON_INFO = "info-circle";
    const MSG_ICON_ERROR = "times-circle";
    const MSG_ICON_WARNING = "warning";
    const MSG_ICON_CONFIRM = "check-circle";

    const MSG_LOC_PAGE = 1;
    const MSG_LOC_CONTAINER = 2;
    const MSG_LOC_SIDEBAR = 3;

    const MSG_HIDE_TIME_INFO = 5000;
    const MSG_HIDE_TIME_ERROR = 15000;

    protected $message;
    protected $title;
    protected $type;
    protected $icon;
    protected $location;
    protected $timeout;
    protected $permanent;
    protected $dismiss;
    protected $key;
    protected $id;

    public function __construct($message, $options = array())
    {
        $this->message = $message;
        $this->title = (isset($options["title"])) ? $options["title"] : "";
        $this->type = (isset($options["type"])) ? $options["type"] : static::MSG_TYPE_INFO;
        $this->icon = (isset($options["icon"])) ? $options["icon"] : static::MSG_ICON_INFO;
        $this->location = (isset($options["location"])) ? $options["location"] : static::MSG_LOC_CONTAINER;
        $this->timeout = (isset($options["timeout"])) ? $options["timeout"] : 0;
        $this->permanent = (isset($options["permanent"])) ? $options["permanent"] : false;
        $this->dismiss = (isset($options["dismiss"])) ? $options["dismiss"] : false;
        $this->key = (isset($options["key"])) ? $options["key"] : "";

        $this->setId();
    }

    public function setId()
    {
        $this->id = "message-";

        if (empty($this->key)) {
            $this->id .= mt_rand(10000, 100000);
        } else {
            $this->id .= $this->key;
        }
    }

    /*
     * Get the CSS class for a message type.
     */
    public function getCssType()
    {
        return $this->type;
    }

    /*
     * Get the CSS icon class for a message type.
     */
    public function getCssIcon()
    {
        return $this->icon;
    }

    public function jsonSerialize()
    {
        return [
            "type" => $this->type,
            "icon" => $this->icon,
            "title" => $this->title,
            "body" => $this->message,
            "location" => $this->location,
            "timeout" => $this->timeout,
            "key" => $this->key
        ];
    }
}
