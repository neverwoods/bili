<?php

namespace Bili;

class Response
{
    /**
     * Gzip (encode) the HTTP response and write to output with a MIME type for JSON content.
     * @param string $strBody The content that should be in the response.
     */
    public static function sendJSON($strBody)
    {
        self::send($strBody, "application/json");
    }

    /**
     * Gzip (encode) the HTTP response and write to output with a MIME type for CSS content.
     * @param string $strBody The content that should be in the response.
     */
    public static function sendCSS($strBody)
    {
        self::send($strBody, "text/css");
    }

    /**
     * Gzip (encode) the HTTP response and write to output with a MIME type for JavaScript content.
     * @param string $strBody The content that should be in the response.
     */
    public static function sendJS($strBody)
    {
        self::send($strBody, "text/javascript");
    }

    /**
     * Gzip (encode) the HTTP response and write to output with a MIME type for JPG content.
     * @param string $binBody The content that should be in the response.
     */
    public static function sendJPEG($binBody)
    {
        self::send($binBody, "image/jpeg");
    }

    /**
     * Gzip (encode) the HTTP response and write to output with a MIME type for RSS feed content.
     * @param string $strBody The content that should be in the response.
     */
    public static function sendRSS($strBody)
    {
        self::send($strBody, "application/rss+xml; charset=utf-8");
    }

    /**
     * Gzip (encode) the HTTP response and write to output with a MIME type for Atom feed content.
     * @param string $strBody The content that should be in the response.
     */
    public static function sendAtom($strBody)
    {
        self::send($strBody, "application/atom+xml; charset=utf-8");
    }

    /**
     * Gzip (encode) the HTTP response and write to output.
     * @param string $strBody        The content that should be in the response.
     * @param string $strContentType The MIME type of the content.
     */
    public static function send($strBody, $strContentType = "text/html")
    {
        $objEncoder = new \HTTP_Encoder(
            array(
                "content" => $strBody,
                "type" => $strContentType
               )
        );

        $objEncoder->encode();
        $objEncoder->sendAll();
        exit;
    }

    /**
     * Store binary data in the cache and generate a download link.
     *
     * @param  binary $binData     The binary data
     * @param  string $strFilename File name
     * @return string The generated download link
     */
    public static function generateDownloadLink($binData, $strFilename, $strDownloadUrl = null)
    {
        // Generate a unique number.
        $strUniqueName = mt_rand(1000000, 9999999);

        // Store in the cache.
        file_put_contents($GLOBALS["_PATHS"]["cache"] . $strUniqueName, $binData);

        $strReturn = static::generateDownloadLinkForExisting($strUniqueName, $strFilename, $strDownloadUrl);

        return $strReturn;
    }

    /**
     * Generate a download link for an existing file in the cache.
     *
     * @param string $strCachedName The name of the file in the cache
     * @param string $strFilename File name
     * @param string $strDownloadUrl
     * @return string The generated download link
     */
    public static function generateDownloadLinkForExisting(
        string $strCachedName,
        string $strFilename,
        string $strDownloadUrl
    ): string {
        // Save in session
        $_SESSION["documents"][$strCachedName] = $strFilename;

        $strDownloadUrl .= "/t/" . Rewrite::encode($strCachedName);

        return Request::getRootURI() . $strDownloadUrl;
    }

    public static function pushDownloadHeadersToBrowser($mimeType, $strFilename, $intContentLength = 0)
    {
        header("HTTP/1.1 200 OK");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-type: " . $mimeType);
        header("Content-Disposition: attachment; filename=\"" . $strFilename . "\"");
        header("Content-Transfer-Encoding: binary");

        if ($intContentLength > 0) {
            header("Content-Length: " . $intContentLength);
        }
    }

    public static function pushFileHeadersToBrowser($mimeType, $intContentLength = 0)
    {
        header("HTTP/1.1 200 OK");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-type: " . $mimeType);
        header("Content-Transfer-Encoding: binary");

        if ($intContentLength > 0) {
            header("Content-Length: " . $intContentLength);
        }
    }

    public static function pushDownloadToBrowser($strFileData, $strFilename)
    {
        if ($strFileData !== false) {
            $mimeType = "application/octet-stream";

            try {
                if (class_exists("finfo")) {
                    $finfo = @new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($strFileData);
                }
            } catch (\Exception $ex) {
                //*** Skip detection.
            }

            self::pushDownloadHeadersToBrowser($mimeType, $strFilename, strlen((string)$strFileData));

            echo $strFileData;
        } else {
            header("HTTP/1.1 404 Not found");
            echo "No data.";
        }

        exit;
    }

    public static function pushFileToBrowser($varFileData)
    {
        if ($varFileData !== false) {
            $blnBinary = true;

            //*** Detect if the input is a file path or binary file.
            try {
                $blnBinary = @is_file($varFileData) !== true;
            } catch (\Exception $ex) {
                //*** Skip detection.
            }

            if (!$blnBinary) {
                //*** Load the file contents.
                $binFileData = file_get_contents($varFileData);
            } else {
                $binFileData = $varFileData;
            }

            $mimeType = "application/octet-stream";

            try {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($binFileData);
            } catch (\Exception $ex) {
                //*** Skip detection.
            }

            self::pushFileHeadersToBrowser($mimeType, strlen((string)$binFileData));

            echo $binFileData;
        } else {
            header("HTTP/1.1 404 Not found");
            echo "No data.";
        }

        exit;
    }
}
