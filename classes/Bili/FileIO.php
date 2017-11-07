<?php

namespace Bili;

/**
 * File IO operations.
 *
 * @package Bili
 * @author Felix Langfeldt <felix@neverwoods.com>
 * @author Robin van Baalen <robin@neverwoods.com>
 * @version 1.2
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
        $strReturn = "";

        $strBase = basename($filename, self::extension($filename));
        if (substr($strBase, -1) == ".") {
            $strBase = substr($strBase, 0, -1);
        }

        $strReturn = $strBase . $addition;

        $strExtension = self::extension($filename);
        if (!is_null($strExtension)) {
            $strReturn .= "." . $strExtension;
        }

        return $strReturn;
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

        $strHash = $strFilePrefix . "-" . $sid;
        $strPdfFile = $arrSettings["tempPath"] . $strHash . ".pdf";
        $strHtmlFile = $arrSettings["tempPath"] . $strHash . ".html";

        file_put_contents($strHtmlFile, $strHtml);
        $strInput = $strHtmlFile;
        $strOutput = $strPdfFile;

        //*** Extra parameters.
        $strParameters = (is_array($arrParameters)) ? implode(" ", $arrParameters) : "";

        //*** Append parameters.
        $strAppendParameters = '';
        if (isset($arrSettings['appendParameters'])) {
            if (is_array($arrSettings['appendParameters'])) {
                $strAppendParameters = implode(' ', $arrSettings['appendParameters']);
            } elseif (is_string($arrSettings['appendParameters'])) {
                $strAppendParameters = $arrSettings['appendParameters'];
            }
        }

        $arrExec = array();
        $arrExec[] = $arrSettings["wkhtmltopdfPath"];
        $arrExec[] = $strParameters;
        $arrExec[] = $strInput;
        $arrExec[] = $strOutput;
        $arrExec[] = $strAppendParameters;
        $strExec = implode(" ", $arrExec);

        exec($strExec);

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
     * @param string|array $varPathB The path(s) to the files that we want to merge.
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

        //*** Save the command to a temporary bash file to circumvent the max. argument problem of the exec method.
        $strCommandFile = sys_get_temp_dir() . "/" . Crypt::generateToken([], 16) . ".sh";
        file_put_contents($strCommandFile, $strCommand);
        chmod($strCommandFile, 0777);

        //*** Execute the bash script.
        exec($strCommandFile);

        if (file_exists($strSaveFile)) {
            if ($blnMove) {
                //*** Move the temp file to the original.
                @unlink($strPathA);
                @rename($strSaveFile, $strPathA);
            }

            $blnReturn = true;
        }

        //*** Remove the bash script.
        @unlink($strCommandFile);

        return $blnReturn;
    }

    public static function handleUpload(
        $targetDir,
        $intMaxSize = null,
        $blnReturnInfo = false,
        $arrAllowedExtensions = null
    ) {
        $arrReturn = null;

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
        $originalName = $fileName;
        $fileName = filter_var($fileName, FILTER_SANITIZE_STRING);
        $fileName = str_replace(" ", "-", $fileName);
        $fileName = str_replace("---", "-", $fileName);
        $fileName = str_replace("--", "-", $fileName);
        $fileName = str_replace(FileIO::extension($fileName), strtolower(FileIO::extension($fileName)), $fileName);

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

        //*** Check if the file extension is allowed.
        if (is_array($arrAllowedExtensions)) {
            if (!in_array(FileIO::extension($originalName), $arrAllowedExtensions)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 105, "message": "File extension not allowed."}, "id" : "id"}');
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
            if ($blnReturnInfo) {
                $arrReturn = ["id" => $fileId, "file" => $fileName, "original" => $originalName];
            } else {
                $_SESSION["app-uploads"][$fileId] = array("file" => $fileName, "original" => $originalName);

                // Return JSON-RPC response
                die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
            }
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "File size over maximum allowed size."}, "id" : "id"}');
        }

        return $arrReturn;
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
     * @param array $arrValidations The array of validation rules
     * @param array|null $arrHeaders The array of HTTP request headers
     * @return boolean True if the remote file exists and matches the validation rules, false if not
     */
    public static function webFileExists($strUrl, $arrValidations = [], $arrHeaders = null)
    {
        $blnReturn = false;

        if (!empty($strUrl)) {
            //*** Make it browser save.
            $strUrl = str_replace(" ", "%20", $strUrl);

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

            if (is_array($arrHeaders)) {
                curl_setopt($objCurl, CURLOPT_HTTPHEADER, $arrHeaders);
            }

            curl_exec($objCurl);

            $intValidationCounter = 0;
            $intHttpResponseCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);
            if ($intHttpResponseCode == 200) {
                foreach ($arrValidations as $intCurlType => $varDesiredValue) {
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
            if (count($arrValidations) === $intValidationCounter) {
                $blnReturn = true;
            }

            curl_close($objCurl);
        }

        return $blnReturn;
    }

    /**
     * Download a file from a webserver.
     *
     * @param string $strUrl The fully qualified path to the remote file
     * @param null|array $arrHeaders The array of HTTP request headers
     * @return mixed
     */
    public static function getWebFile($strUrl, $arrHeaders = null)
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

            if (is_array($arrHeaders)) {
                curl_setopt($objCurl, CURLOPT_HTTPHEADER, $arrHeaders);
            }

            $strReturn = curl_exec($objCurl);

            curl_close($objCurl);
        }

        return $strReturn;
    }

    /**
     * Get the amount of lines in a file.
     *
     * @param string $strFilePath
     * @return number
     */
    public static function getLineCount($strFilePath)
    {
        $intReturn = 0;

        if (file_exists($strFilePath)) {
            $intReturn = 1;

            $resFile = fopen($strFilePath, 'rb');

            while (!feof($resFile)) {
                $intReturn += substr_count(fread($resFile, 8192), PHP_EOL);
            }

            fclose($resFile);
        }

        return $intReturn;
    }

    /**
     * Get the file encoding from a file.
     *
     * @param $strFilePath
     * @return null|string
     */
    public static function detectFileEncoding($strFilePath) {
        $strReturn = null;

        if (file_exists($strFilePath)) {
            $arrOutput = array();

            exec('file -i "' . $strFilePath . '"', $arrOutput);

            if (isset($arrOutput[0])){
                $arrEncoding = explode('charset=', $arrOutput[0]);
                $strReturn = isset($arrEncoding[1]) ? $arrEncoding[1] : null;
            }
        }

        return $strReturn;
    }
}
