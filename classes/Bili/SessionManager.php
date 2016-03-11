<?php

namespace Bili;

/**
 * Class to hold Session logic.
 *
 * @package     Bili
 * @author      Felix Langfeldt
 * @version     1.2
 */
class SessionManager
{
    private static $instance = null;
    private $transferId = null;
    private $timeout = 1440;
    private $session = null;

    public static function singleton($transferId = null, $timeout = 1440, $diSession = null, $blnStart = true)
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

        if ($blnStart) {
            session_start();
        }

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

    /**
     * Validate a session. If not valid the session will be reset via the "reset" method.
     *
     * @return boolean
     */
    public function validate()
    {
        $blnReturn = true;

        if (!$this->isValid($this->timeout)) {
            $this->reset();

            $blnReturn = false;
        }

        return $blnReturn;
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
        session_regenerate_id(true);
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

    protected function isExpired($timeout = 1800)
    {
        $blnReturn = false;

        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $timeout)) {
            $blnReturn = true;
        }

        $_SESSION['_last_activity'] = time();

        return $blnReturn;
    }

    protected function isFingerprint()
    {
        $blnReturn = true;

        if (isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REMOTE_ADDR'])) {
            $hash = md5($_SERVER['HTTP_USER_AGENT'] . (ip2long($_SERVER['REMOTE_ADDR']) & ip2long('255.255.0.0')));

            if (isset($_SESSION['_fingerprint'])) {
                $blnReturn = $_SESSION['_fingerprint'] === $hash;
            }

            $_SESSION['_fingerprint'] = $hash;
        }

        return $blnReturn;
    }

    /**
     * Execute the validation methods. At this time the fingerprint (user agent and IP address) and expiration timeout
     * are checked.
     *
     * @param integer $timeout
     * @return boolean
     */
    protected function isValid($timeout = 1800)
    {
        return (!$this->isExpired($timeout) && $this->isFingerprint());
    }

    /**
     * Deserialize a session encoded string.
     *
     * @param string $session_data
     * @throws \Exception
     * @return multitype:mixed
     */
    public static function unserialize($session_data)
    {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserializPhp($session_data);
                break;
            case "php_binary":
                return self::unserializePhpBinary($session_data);
                break;
            default:
                throw new \Exception(
                    "Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary"
                );
        }
    }

    private static function unserializPhp($session_data)
    {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserializePhpBinary($session_data)
    {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}
