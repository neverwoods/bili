<?php

namespace Bili;

/**
 * File IO operations.
 *
 * @package Bili
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 1.1
 */
class FileIO
{
    public static function extension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }

    public static function add2Base($filename, $addition)
    {
        $strBase = basename($filename, self::extension($filename));
        return substr($strBase, 0, -1) . $addition . "." . self::extension($filename);
    }

    public static function unlinkDir($dir)
    {
        if (is_dir($dir) === true) {
            $files = array_diff(scandir($dir), array('.', '..'));

            foreach ($files as $file) {
                self::unlinkDir(realpath($dir) . '/' . $file);
            }

            return rmdir($dir);
        } elseif (is_file($dir) === true) {
            return unlink($dir);
        }

        return false;
    }

    public static function createTempFolder($strBaseFolder)
    {
        $strReturn = "";

        $strBaseFolder = (substr($strBaseFolder, -1) !== DIRECTORY_SEPARATOR)
            ? $strBaseFolder . DIRECTORY_SEPARATOR
            : $strBaseFolder;

        $strFolderName = $strBaseFolder . Crypt::generateToken([], 16);
        if (mkdir($strFolderName)) {
            $strReturn = $strFolderName;
        }

        return $strReturn;
    }

    /**
     * Convert HTML markup to a binary PDF
     * @param  string      $strHtml The HTML input
     * @return binary|null The binary PDF output or null if something went wrong.
     */
    public static function html2pdf($strHtml, $strFilePrefix = "document", $arrParameters = null, $arrSettings = [])
    {
        $varReturn = null;

	    if (!isset($arrSettings["tempPath"]) && isset($GLOBALS["_PATHS"]["cache"])) {
	       $arrSettings["tempPath"] = $GLOBALS["_PATHS"]["cache"];
	    }

	    if (!isset($arrSettings["wkhtmltopdfPath"]) && isset($GLOBALS["_CONF"]["app"]["wkhtmltopdf"])) {
	       $arrSettings["wkhtmltopdfPath"] = $GLOBALS["_CONF"]["app"]["wkhtmltopdf"];
	    }

        srand((double) microtime()*1000000);
        $random_number = rand();
        $sid = md5($random_number);

        $strHash         = $strFilePrefix . "-" . $sid;
	    $strPdfFile 	= $arrSettings["tempPath"] . $strHash . ".pdf";
	    $strHtmlFile 	= $arrSettings["tempPath"] . $strHash . ".html";

        file_put_contents($strHtmlFile, $strHtml);
        $strInput = $strHtmlFile;
        $strOutput = $strPdfFile;

        //*** Extra parameters.
        $strParameters = (is_array($arrParameters)) ? implode(" ", $arrParameters) : "";

        $arrExec = array();
        $arrExec[] = $arrSettings["wkhtmltopdfPath"];
        $arrExec[] = $strParameters;
        $arrExec[] = $strInput;
        $arrExec[] = $strOutput;
        $strExec = implode(" ", $arrExec);

        $blnCreated = exec($strExec);

        if (file_exists($strPdfFile)) {
            $varReturn = file_get_contents($strPdfFile);

            // Clean up
            @unlink($strPdfFile);
        }

        // Clean up
        @unlink($strHtmlFile);

        return $varReturn;
    }

    /**
     * Merge 2 or more PDF files.
     *
     * @param string $strPathA The path to save to. If it's an exisiting file it will be added to the merge
     *                         and replaced after the successful merge.
     * @param string $varPathB The path(s) to the files that we want to merge.
     * @return boolean
     */
    public static function mergePdfFiles($strPathA, $varPathB, $arrSettings = [])
    {
        $blnReturn = false;

        if (!isset($arrSettings["ghostscriptPath"]) && isset($GLOBALS["_CONF"]["app"]["gs"])) {
	       $arrSettings["ghostscriptPath"] = $GLOBALS["_CONF"]["app"]["gs"];
	    }

        $strSaveFile = $strPathA;
        $blnMove = false;

        if (is_array($varPathB)) {
            $varPathB = "\"" . implode("\" \"", $varPathB) . "\"";
        }

        if (file_exists($strPathA)) {
            $blnMove = true;
            $varPathB .= " \"" . $strPathA . "\"";
            $strSaveFile = dirname($strPathA) . "/" . Crypt::generateToken([], 16);
        }

        $strCommand = $arrSettings["ghostscriptPath"]
            . " -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=\"{$strSaveFile}\" -dBATCH {$varPathB}";

        $blnReturn = exec($strCommand);

        if (file_exists($strSaveFile) && $blnMove) {
            //*** Move the temp file to the original.
            @unlink($strPathA);
            @rename($strSaveFile, $strPathA);
        }

        return $blnReturn;
    }

    public static function handleUpload($targetDir, $intMaxSize = null)
    {
        // HTTP headers for no cache etc
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // 5 minutes execution time
        @set_time_limit(5 * 60);

        // Get parameters
        $chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
        $chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
        $fileId = isset($_REQUEST["id"]) ? $_REQUEST["id"] : '';

        // Clean the fileName for security reasons
        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING);
        $fileName = str_replace(" ", "-", $fileName);
        $fileName = str_replace("---", "-", $fileName);
        $fileName = str_replace("--", "-", $fileName);
        $originalName = $fileName;

        // Make sure the fileName is unique but only if chunking is disabled
        if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            $ext = strrpos($fileName, '.');
            $fileName_a = substr($fileName, 0, $ext);
            $fileName_b = substr($fileName, $ext);

            $count = 1;
            while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b)) {
                $count++;
            }

            $fileName = $fileName_a . '_' . $count . $fileName_b;
        }

        // Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        // Look for the content type header
        if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
        }

        if (isset($_SERVER["CONTENT_TYPE"])) {
            $contentType = $_SERVER["CONTENT_TYPE"];
        }

        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                try {
                    $out = @fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
                    if ($out) {
                        // Read binary input stream and append it to temp file
                        $in = @fopen($_FILES['file']['tmp_name'], "rb");

                        if ($in) {
                            while ($buff = fread($in, 4096)) {
                                fwrite($out, $buff);
                            }
                        } else {
                            die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                        }
                        fclose($in);
                        fclose($out);
                        @unlink($_FILES['file']['tmp_name']);
                    } else {
                        die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                    }
                } catch (\Exception $ex) {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                }
            } else {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }
        } else {
            // Open temp file
            try {
                $out = @fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = @fopen("php://input", "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                    } else {
                        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                    }
                    fclose($in);
                    fclose($out);
                } else {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
                }
            } catch (\Exception $ex) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
        }

        //*** Check if the uploaded file is under the max. size limit.
        if (!is_null($intMaxSize)) {
            try {
                $intSize = filesize($targetDir . DIRECTORY_SEPARATOR . $fileName);
                if ($intSize > $intMaxSize) {
                    $fileName = "";
                }
            } catch (\Exception $ex) {
                //*** Fail silent.
            }
        }

        if (!empty($fileName)) {
            // Save the upload info.
            $_SESSION["app-uploads"][$fileId] = array("file" => $fileName, "original" => $originalName);

            // Return JSON-RPC response
            die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "File size over maximum allowed size."}, "id" : "id"}');
        }
    }

    /**
     * Check if a remote file on a webserver exists.
     *
     * This can be used like:
     * <code>
     * $blnFileExsits = \Bili\FileIO::webFileExists('http://neverwoods.com/css/default.css');
     * </code>
     * Returns true since neverwoods.com automatically forwards to the main page and returns a 200 header
     *
     * But to verify it's actually a CSS file that exists remotely, we can add a validation array:
     * <code>
     * $blnFileExists = \Bili\FileIO::webFileExists(
     *     'http://neverwoods.com/css/default.css',
     *     array(
     *         CURLINFO_CONTENT_TYPE => "text/css"
     *     )
     * );
     * </code>
     * In this case, `$blnFileExists` is false. The remote file is an HTML page instead of a CSS file.
     * You can add all `CURLINFO_` constants as a key and add their desired value as the value.
     *
     * Note:
     * Be aware that when you validate for HTML pages, CURLINFO_CONTENT_TYPE returns 'text/html; charset=UTF-8'
     * or something similar instead of the possibly expected 'text/html'.
     *
     * @param string $strUrl The fully qualified path to the remote file
     * @param array $validations The array of validation rules
     * @return boolean True if the remote file exists and matches the validation rules, false if not
     */
    public static function webFileExists($strUrl, $validations = array())
    {
        $blnReturn = false;

        if (!empty($strUrl)) {
            $objCurl = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $strUrl);
            curl_setopt($objCurl, CURLOPT_HEADER, true);
            curl_setopt($objCurl, CURLOPT_NOBODY, true);
            curl_setopt($objCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($objCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($objCurl, CURLOPT_MAXREDIRS, 10); //follow up to 10 redirections - avoids loops

            $strData = curl_exec($objCurl);

            $intValidationCounter = 0;
            $intHttpResponseCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            if ($intHttpResponseCode == 200) {
                foreach ($validations as $intCurlType => $varDesiredValue) {
                    $varReturnValue = curl_getinfo($objCurl, $intCurlType);

                    if ($varReturnValue === $varDesiredValue) {
                        //*** Validate all validations and keep track of the amount of valid ones
                        $intValidationCounter++;
                    }
                }
            }

            /**
             * When we have to validate all validations, compare the validations array length
             * against the amount of valid validations we've encountered.
             */
            if (count($validations) === $intValidationCounter) {
                $blnReturn = true;
            }

            curl_close($objCurl);
        }

        return $blnReturn;
    }

    /**
     * Download a file from a webserver.
     *
     * @param string $strUrl
     * @return mixed
     */
    public static function getWebFile($strUrl)
    {
        $strReturn = null;

        if (!empty($strUrl)) {
            //*** Make it browser save.
            $strUrl = str_replace(" ", "%20", $strUrl);

            $objCurl = curl_init();
            curl_setopt($objCurl, CURLOPT_URL, $strUrl);
            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($objCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($objCurl, CURLOPT_MAXREDIRS, 10); //follow up to 10 redirections - avoids loops

            $strReturn = curl_exec($objCurl);

            curl_close($objCurl);
        }

        return $strReturn;
    }
}
