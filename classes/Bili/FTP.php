<?php

namespace Bili;

class FTP
{
    private $objFTP;
    private $strHost;
    private $intPort;
    private $intTimeout;
    private $strUsername;
    private $strPassword;

    /* public Void __construct(): Constructor */
    public function __construct($host, $port = 21, $timeout = 90, $blnSecure = false)
    {
        if (is_null($port)) {
            $port = 21;
        }

        if (is_null($timeout)) {
            $timeout = 90;
        }

        $this->strHost = $host;
        $this->intPort = $port;
        $this->intTimeout = $timeout;

        if ($blnSecure && function_exists('ftp_ssl_connect')) {
            $this->objFTP = ftp_ssl_connect($host, $port, $timeout);
        }

        if (!$this->objFTP) {
            $this->objFTP = ftp_connect($host, $port, $timeout);
        }
    }

    /* public Void __destruct(): Destructor */
    public function __destruct()
    {
        try {
            @ftp_close($this->objFTP);
        } catch (\Exception $ex) {
            //*** Already disconnected. Continue.
        }
    }

    /* public Mixed __call(): Re-route all function calls to the PHP-functions */
    public function __call($function, $arguments)
    {
        $varReturn = false;

        //*** Prepend the ftp resource to the arguments array
        array_unshift($arguments, $this->objFTP);

        //*** Call the PHP function
        try {
            $varReturn = @call_user_func_array('ftp_' . $function, $arguments);
            if ($varReturn === false && $function == "login") {
                //*** Retry connect unsecured if login fails.
                ftp_close($this->objFTP);
                $this->objFTP = ftp_connect($this->strHost, $this->intPort, $this->intTimeout);

                //*** Re-call the command.
                array_shift($arguments);
                array_unshift($arguments, $this->objFTP);
                $varReturn = call_user_func_array('ftp_' . $function, $arguments);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $varReturn;
    }

    public function login($strUsername, $strPassword)
    {
        $this->strUsername = $strUsername;
        $this->strPassword = $strPassword;

        return ftp_login($this->objFTP, $strUsername, $strPassword);
    }

    public function delete($strPath)
    {
        if (stristr($strPath, "*") === false) {
            //*** Regular FTP delete.
            try {
                @ftp_delete($this->objFTP, $strPath);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        } else {
            //*** Wildcard delete.
            $strBasePath = dirname($strPath);
            $strFileName = basename($strPath);

            //*** Get files in remote folder.
            $arrFiles = $this->nlist($strBasePath);
            if ($arrFiles !== false) {
                foreach ($arrFiles as $strFile) {
                    $strBaseFile = basename($strFile);
                    if (!$this->isDir($strFile) && $this->hasWildcard($strFileName, $strBaseFile)) {
                        @ftp_delete($this->objFTP, $strBasePath . "/" . $strBaseFile);
                    }
                }
            }
        }
    }

    public function isDir($strPath)
    {
        $origin = @ftp_pwd($this->objFTP);

        if (@ftp_chdir($this->objFTP, $strPath)) {
            ftp_chdir($this->objFTP, $origin);
            return true;
        } else {
            return false;
        }
    }

    public function mksubdirs($ftpath, $ftpbasedir = null)
    {
        if (!is_null($ftpbasedir)) {
            @ftp_chdir($this->objFTP, $ftpbasedir);
        }

        $parts = explode('/', $ftpath);
        foreach ($parts as $part) {
            try {
                @ftp_chdir($this->objFTP, $part);

	            //*** Extra check for the case that error reporting is disabled.
	            if (stripos(ftp_pwd($this->objFTP), $part) === false) {
    	            @ftp_mkdir($this->objFTP, $part);
    	            @ftp_chdir($this->objFTP, $part);
	            }
            } catch (\Exception $ex) {
                @ftp_mkdir($this->objFTP, $part);
                @ftp_chdir($this->objFTP, $part);
            }
        }
    }

    /**
     * Quick remove method for a single file.
     *
     * @param string $strFile
     * @param array $ftpSettings (host, username, password)
     * @throws \RuntimeException
     */
    public static function ftpRemove($strFile, $ftpSettings, $blnSecure = false)
    {
        $objFtp = new FTP($ftpSettings['host'], 21, 90, $blnSecure);
        $objRet = $objFtp->login($ftpSettings['username'], $ftpSettings['password']);
        if (!$objRet) {
            throw new \RuntimeException("Could not login to FTP server.", 404);
        }

        //*** Passive mode.
        $objFtp->pasv(true);

        //*** Remove the file.
        try {
            @$objFtp->delete($strFile);
        } catch (\Exception $ex) {
            //*** Ignore. Probably already removed.
        }
    }

    /**
     * Quick remove method for a single folder.
     *
     * @param string $strFolder
     * @param array $ftpSettings (host, username, password)
     * @param boolean $blnSecure
     * @throws \RuntimeException
     */
    public static function ftpRemoveDir($strFolder, $ftpSettings, $blnSecure = false)
    {
        $objFtp = new FTP($ftpSettings['host'], 21, 90, $blnSecure);
        $objRet = $objFtp->login($ftpSettings['username'], $ftpSettings['password']);
        if (!$objRet) {
            throw new \RuntimeException("Could not login to FTP server.", 404);
        }

        //*** Passive mode.
        $objFtp->pasv(true);

        //*** Remove the file.
        try {
            //*** Check if the folder is empty.
            $arrFiles = $objFtp->nlist($strFolder);

            if ($arrFiles !== false && count($arrFiles) == 0) {
                @$objFtp->rmdir($strFolder);
            }
        } catch (\Exception $ex) {
            //*** Ignore. Probably already removed.
        }
    }

    /**
     * Quick upload mthod for a single file.
     *
     * @param string $sourceFile local file name
     * @param array $ftpSettings (path.uploads, host, username, password)
     * @throws \RuntimeException
     */
    public static function ftpUpload($sourceFile, $ftpSettings, $targetFile = null, $blnSecure = false)
    {
        $strFtpFileName = (is_null($targetFile)) ? basename($sourceFile) : $targetFile;
        $strFtpFileDir = $ftpSettings['path']['uploads'];

        $objFtp = new FTP($ftpSettings['host'], 21, 90, $blnSecure);
        $objRet = $objFtp->login($ftpSettings['username'], $ftpSettings['password']);
        if (!$objRet) {
            throw new \RuntimeException("Could not login to FTP server.", 404);
        }

        //*** Passive mode.
        $objFtp->pasv(true);

        //*** Create dealer folder.
        try {
            $objFtp->mksubdirs($strFtpFileDir);
        } catch (\Exception $ex) {
            //*** Ignore. The folder probably already exists.
        }

        //*** Transfer file.
        $objRet = $objFtp->nb_put($strFtpFileName, $sourceFile, FTP_BINARY);
        while ($objRet == FTP_MOREDATA) {
            // Continue uploading...
            $objRet = $objFtp->nb_continue();
        }
        if ($objRet != FTP_FINISHED) {
            //*** Something went wrong.
            throw new \RuntimeException("FTP transfer of {$strFtpFileName} interruppted.", 500);
        }

        //*** Remove local file.
        @unlink($sourceFile);
    }

    private function hasWildcard($strWildcard, $strName)
    {
        $blnReturn = false;

        if (stristr($strWildcard, "*") !== false) {
            if (strpos($strWildcard, "*") === 0) {
                if (strrpos($strWildcard, "*") === (strlen($strWildcard) - 1)) {
                    //*** Wildcard at start and end.
                    $strNoWildcard = substr(substr($strWildcard, 0, (strlen($strWildcard) - 1)), 1);
                    if (strpos($strName, $strNoWildcard) !== false) {
                        $blnReturn = true;
                    }
                } else {
                    //*** Wildcard at start.
                    $strNoWildcard = substr($strWildcard, 1);
                    if (strpos($strName, $strNoWildcard) === strlen($strName) - strlen($strNoWildcard)) {
                        $blnReturn = true;
                    }
                }
            } elseif (strpos($strWildcard, "*") === (strlen($strWildcard) - 1)) {
                //*** Wildcard at end.
                $strNoWildcard = substr($strWildcard, 0, (strlen($strWildcard) - 1));
                if (strpos($strName, $strNoWildcard) === 0) {
                    $blnReturn = true;
                }
            }
        }

        return $blnReturn;
    }
}
