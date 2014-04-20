<?php

namespace Bili;

/**
 * Class to hold the messaging logic.
 *
 * @package Bili
 */

class BubbleMessenger
{
    /*
     * Add a new message to the message stack.
     *
     * @var string $message The message to display
     * @var array $options An array of options for the message
     *                     "title" = Title of the message
     *                     "type" = Message type (MSG_TYPE_INFO, MSG_TYPE_ERROR, MSG_TYPE_WARNING, MSG_TYPE_CONFIRM)
     *                     "location" = Location on the page (MSG_LOC_PAGE, MSG_LOC_CONTAINER, MSG_LOC_SIDEBAR)
     *                     "timeout" = Timeout in milliseconds (MSG_HIDE_TIME_INFO, MSG_HIDE_TIME_ERROR)
     *                     "permanent" = Indicate if the message should be displayed on every page and not only once.
     */
    public static function add($message, $options = array())
    {
        if (!isset($_SESSION["bubble-messages"]) || (isset($_SESSION["bubble-messages"])
        		&& !is_array(unserialize($_SESSION["bubble-messages"])))) {
            $_SESSION["bubble-messages"] = serialize(array());
        }

        $objMessages = unserialize($_SESSION["bubble-messages"]);
        array_push($objMessages, new BubbleMessage($message, $options));
        $_SESSION["bubble-messages"] = serialize($objMessages);
    }

    public static function get($messageLocation)
    {
        $arrReturn = array();
        $arrTemp = array();

        if (isset($_SESSION["bubble-messages"])) {
            $objMessages = unserialize($_SESSION["bubble-messages"]);
            if (is_array($objMessages)) {
                foreach ($objMessages as $objMessage) {
                    if ($objMessage->getLocation() == $messageLocation) {
                        array_push($arrReturn, $objMessage);
                        if ($objMessage->getPermanent()) {
                            array_push($arrTemp, $objMessage);
                        }
                    } else {
                        array_push($arrTemp, $objMessage);
                    }
                }

                $_SESSION["bubble-messages"] = serialize($arrTemp);
            }
        }

        return $arrReturn;
    }
    
    public static function remove($strKey)
    {
        $arrTemp = array();
        
    	if (isset($_SESSION["bubble-messages"])) {
    		$objMessages = unserialize($_SESSION["bubble-messages"]);
    		if (is_array($objMessages)) {
    			foreach ($objMessages as $objMessage) {
    				if ($objMessage->getKey() != $strKey) {
    					array_push($arrTemp, $objMessage);
    				}
    			}
    			
    			$_SESSION["bubble-messages"] = serialize($arrTemp);
    		}
    	}
    }

    public static function clear()
    {
        $_SESSION["bubble-messages"] = serialize(array());
    }

    public static function locationToString($location)
    {
        $strReturn = "";

        switch ($location) {
            case MSG_LOC_CONTAINER:
                $strReturn = "container";
                break;
            case MSG_LOC_PAGE:
                $strReturn = "page";
                break;
            case MSG_LOC_SIDEBAR:
                $strReturn = "side";
                break;
        }

        return $strReturn;
    }
}
