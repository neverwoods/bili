<?php

namespace Bili;

/**
 * Class to hold Session logic.
 *
 * @package    Bili
 * @author    felix
 * @version 1.1
 */
class SessionManager
{
    private static $instance = null;
    private $transferId = null;
    private $timeout = 1440;
    private $session = null;

    public static function singleton($transferId = null, $timeout = 1440, $diSession = null)
    {
        self::$instance = new SessionManager($transferId, $timeout, $diSession);

        //*** Register this object as the session handler.
        session_set_save_handler(
            array(&self::$instance, "open"),
            array(&self::$instance, "close"),
            array(&self::$instance, "read"),
            array(&self::$instance, "write"),
            array(&self::$instance, "destroy"),
            array(&self::$instance, "gc")
        );

        ini_set('session.gc_maxlifetime', $timeout);
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
            session_unset();     // unset $_SESSION variable for the run-time
            session_destroy();   // destroy session data in storage
        }
        $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

        return self::$instance;
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    private function __construct($transferId = null, $timeout = 1440, $diSession = null)
    {
        if (!is_null($transferId) && !empty($transferId)) {
            $this->transferId = $transferId;
        }

        $this->timeout = $timeout;
        $this->session = $diSession;

        ini_set('session.gc_maxlifetime', $this->timeout);
    }

    public function open($strSavePath, $strSessionName)
    {
        return true;
    }

    public function close()
    {
        $this->gc();

        return true;
    }

    public function read($strId)
    {
        $session = $this->session;

        $strId = (!is_null($this->transferId)) ? $this->transferId : $strId;
        $strReturn = $session::getSessionData($strId);

        return $strReturn;
    }

    public function write($strId, $strData)
    {
        $session = $this->session;

        $strId = (!is_null($this->transferId)) ? $this->transferId : $strId;
        $session::setSessionData($strId, $strData);

        return true;
    }

    public function destroy($strId)
    {
        $session = $this->session;
        $session::destroy($strId);

        return true;
    }

    public function gc()
    {
        $session = $this->session;
        $session::clean();

        return true;
    }

    public function writeClose()
    {
        session_write_close();
    }

    public function reset()
    {
        session_unset();
        session_destroy();
    }

    public static function setData($strKey, $varData = null)
    {
        if (isset($_SESSION)) {
            if (is_null($varData) && isset($_SESSION[$strKey])) {
                //*** Clear key/value from the session array if the value is null.
                unset($_SESSION[$strKey]);
            } else {
                //*** Set the specified value.
                $_SESSION[$strKey] = $varData;
            }
        }
    }

    public static function getData($strKey)
    {
        if (isset($_SESSION[$strKey])) {
            return $_SESSION[$strKey];
        }
    }
}
