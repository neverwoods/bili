<?php

namespace Bili;

/**
 * REST Client class
 */
class RestRequest
{
    /** @var string|null */
    protected $url;
    /** @var string */
    protected $verb;
    /** @var array<string, mixed>|string|null */
    protected $requestBody;
    /** @var array<string, string>|null */
    protected $requestHeaders;
    /** @var int */
    protected $requestLength;
    /** @var string|null */
    protected $username;
    /** @var string|null */
    protected $password;
    /** @var string */
    protected $acceptType;
    /** @var string|bool|null */
    protected $responseBody;
    /** @var array<string, mixed>|null */
    protected $responseInfo;
    /** @var bool */
    protected $multipart;

    /**
     * @param string|null $url
     * @param string $verb
     * @param array<string, mixed>|null $requestBody
     * @param array<string, string>|null $headers
     * @param bool $blnMultipart
     */
    public function __construct($url = null, $verb = "GET", $requestBody = null, $headers = null, $blnMultipart = false)
    {
        $this->url                = $url;
        $this->verb                = $verb;
        $this->requestBody        = $requestBody;
        $this->requestHeaders    = $headers;
        $this->requestLength    = 0;
        $this->username            = null;
        $this->password            = null;
        $this->acceptType        = "application/json";
        $this->responseBody        = null;
        $this->responseInfo        = null;
        $this->multipart        = $blnMultipart;

        if ($this->requestBody !== null) {
            $this->buildPostBody();
        }
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->requestBody        = null;
        $this->requestLength    = 0;
        $this->verb                = "GET";
        $this->responseBody        = null;
        $this->responseInfo        = null;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $ch = curl_init();

        //*** Ignore the SSL certificate.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $this->setAuth($ch);

        try {
            switch (strtoupper($this->verb)) {
                case "GET":
                    $this->executeGet($ch);
                    break;
                case "POST":
                    $this->executePost($ch);
                    break;
                case "PUT":
                    $this->executePut($ch);
                    break;
                case "DELETE":
                    $this->executeDelete($ch);
                    break;
                default:
                    throw new \InvalidArgumentException("Current verb '" . $this->verb . "' is an invalid REST verb.");
            }
        } catch (\InvalidArgumentException $e) {
            curl_close($ch);
            throw $e;
        } catch (\Exception $e) {
            curl_close($ch);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed>|object|string|null $data
     * @return void
     */
    public function buildPostBody($data = null)
    {
        $data = ($data !== null) ? $data : $this->requestBody;

        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException("Invalid data input for postBody. Array expected.");
        }

        if (!$this->multipart) {
            $data = http_build_query($data, "", "&");
        }

        $this->requestBody = $data;
    }

    /**
     * @return array<int, string>
     */
    public function buildHeaders()
    {
        $arrReturn = array();

        $this->requestHeaders = (!is_array($this->requestHeaders)) ? array() : $this->requestHeaders;
        foreach ($this->requestHeaders as $key => $value) {
            $arrReturn[] = "{$key}: {$value}";
        }
        $arrReturn[] = "Accept: " . $this->acceptType;

        return $arrReturn;
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     */
    protected function executeGet($ch)
    {
        if (is_string($this->requestBody)) {
            $this->url .= "?" . $this->requestBody;
        }

        $this->doExecute($ch);
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     */
    protected function executePost($ch)
    {
        if (!is_string($this->requestBody) && !$this->multipart) {
            $this->buildPostBody();
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);

        $this->doExecute($ch);
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     */
    protected function executePut($ch)
    {
        if (!is_string($this->requestBody)) {
            $this->buildPostBody();
        }

        $this->requestLength = strlen((string)$this->requestBody);

        $fh = fopen("php://memory", "rw");
        fwrite($fh, $this->requestBody);
        rewind($fh);

        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
        curl_setopt($ch, CURLOPT_PUT, true);

        $this->doExecute($ch);

        fclose($fh);
    }

    /**
     * @param \CurlHandle $ch
     * @return void
     */
    protected function executeDelete($ch)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        if (is_string($this->requestBody)) {
            $this->url .= "?" . $this->requestBody;
        }

        $this->doExecute($ch);
    }

    /**
     * @param \CurlHandle $curlHandle
     * @return void
     */
    protected function doExecute(&$curlHandle)
    {
        $this->setCurlOpts($curlHandle);
        $this->responseBody     = curl_exec($curlHandle);
        $this->responseInfo      = curl_getinfo($curlHandle);

        curl_close($curlHandle);
    }

    /**
     * @param \CurlHandle $curlHandle
     * @return void
     */
    protected function setCurlOpts(&$curlHandle)
    {
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_URL, $this->url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $this->buildHeaders());
    }

    /**
     * @param \CurlHandle $curlHandle
     * @return void
     */
    protected function setAuth(&$curlHandle)
    {
        if ($this->username !== null && $this->password !== null) {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        }
    }

    /**
     * @return string
     */
    public function getAcceptType()
    {
        return $this->acceptType;
    }

    /**
     * @param string $acceptType
     * @return void
     */
    public function setAcceptType($acceptType)
    {
        $this->acceptType = $acceptType;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return void
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string|bool|null
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseInfo()
    {
        return $this->responseInfo;
    }

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * @param string $verb
     * @return void
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;
    }
}
