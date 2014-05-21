<?php

/**
 * Return Path API client library
 *
 * Please consult https://api.returnpath.com/docs/index.html
 */

require_once dirname(__FILE__) . '/class.returnpathresponse.php';

/**
 * Class to manage Return Path API access
 *
 */
class ReturnPath
{

    protected $responseHeaders;
    protected $requestHeaders;
    protected $username;
    protected $password;
    protected $saveHeaders;
    protected $ssl;
    protected $endPoint;
    protected $apiVersion;
    protected $lastResponse;
    protected $product;
    protected $authenticationMethod;
    protected $authenticationMethods = array('private', 'http_basic');

    /**
     * Instantiate a new object.
     */
    function __construct($username, $password, $authenticationMethod='http_basic')
    {
        $this->username = $username;
        $this->password = $password;
        $this->saveHeaders = false;
        $this->ssl = true;
        $this->endPoint = 'api.returnpath.com';
        $this->apiVersion = 'v1';
        $this->lastResponse = null;
        $this->product = null;
        if (! in_array($authenticationMethod, $this->authenticationMethods)) {
            throw new InvalidArgumentException("Invalid authentication method '$authenticationMethod''");
        }
        $this->authenticationMethod = $authenticationMethod;
    }

    /**
     * Specify the product.
     * @param string $product
     * @return boolean success
     */
    public function setProduct($product)
    {
        if (! in_array($product, array('ecm', 'im', 'preview', 'repmon'))) {
            throw new InvalidArgumentException("Invalid product '$product'");
        }
        $this->product = $product;
        return true;
    }

    /**
     * Executes HTTP GET request
     * @param string $action
     * @param null $parameters - specify as associative array
     * @param null $product
     * @return bool|ReturnPathResponse
     * @throws InvalidArgumentException
     */
    public function get($action = '', $parameters = null, $product = null)
    {
        if (is_null($this->product) && is_null($product)) {
            throw new InvalidArgumentException("you must specify a product");
        }
        return $this->_doCall('GET', $action, $parameters);
    }

    /**
     * Executes HTTP PUT request
     * @param string $action
     * @param null $parameters - specify as associative array
     * @param null $product
     * @return bool|ReturnPathResponse
     * @throws InvalidArgumentException
     */
    public function put($action, $parameters = null, $product = null)
    {
        if (is_null($this->product) && is_null($product)) {
            throw new InvalidArgumentException("you must specify a product");
        }
        return $this->_doCall('PUT', $action, $parameters);
    }

    /**
     * Executes HTTP POST request
     * @param string $action
     * @param null $parameters - specify as associative array
     * @param null $product
     * @return bool|ReturnPathResponse
     * @throws InvalidArgumentException
     */
    public function post($action = '', $parameters = null, $product = null)
    {
        if (is_null($this->product) && is_null($product)) {
            throw new InvalidArgumentException("you must specify a product");
        }
        return $this->_doCall('POST', $action, $parameters);
    }

    /**
     * Executes HTTP DELETE request
     * @param string $action
     * @param null $parameters - specify as associative array
     * @param null $product
     * @return bool|ReturnPathResponse
     * @throws InvalidArgumentException
     */
    public function delete($action = '', $parameters = null, $product = null)
    {
        if (is_null($this->product) && is_null($product)) {
            throw new InvalidArgumentException("you must specify a product");
        }
        return $this->_doCall('DELETE', $action, $parameters);
    }

    /**
     * Specify the API endpoint.
     * If setEndPoint isn't used, then api.returnpath.com is used by default
     * @param string $endPoint
     * @return boolean success
     */
    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
        return true;
    }

    /**
     * Specify whether or not API calls should be made over a secure connection.
     * HTTPS is used on all calls by default.
     * @param bool $sslOn Set to false to make calls over HTTP, true to use HTTPS
     */
    public function setSSL($sslOn = true)
    {
        $this->ssl = (is_bool($sslOn)) ? $sslOn : true;
    }

    /**
     * Specify whether or not you want to keep a trace of HTTP headers
     * default is to not keep a trace of HTTP headers
     * @param bool $yes Set to true to keep a trace of HTTP headers
     */
    public function saveHeaders($yes = true)
    {
        $this->saveHeaders = $yes;
    }

    /**
     * Returns the ReturnPathResponse object for the last API call.
     * @return ReturnPathResponse
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Internal method returning the call signature
     * @param $url
     * @return string
     */
    protected function getPublicPrivateSignature($url)
    {
        $timestamp = time();
        $path = parse_url($url, PHP_URL_PATH);
        $hash = hash_hmac('sha256', "$path:$timestamp", $this->password, false);
        return base64_encode($this->username . ":$hash:$timestamp");
    }

    /**
     * Internal method handling HTTP request
     * @param $httpMethod
     * @param $action
     * @param null $parameters
     * @param null $product
     * @return bool|ReturnPathResponse
     */
    protected function _doCall($httpMethod, $action, $parameters = null, $product = null)
    {
        $url = 'http';
        if ($this->ssl) {
            $url = 'https';
        }
        $url .= '://' . $this->endPoint . '/';
        if ($this->apiVersion != '') {
            $url .= $this->apiVersion . '/';
        }
        $url .= (is_null($product) ? $this->product : $product) . "/$action";

        if ($this->authenticationMethod == "private") {
            $url .= '?' . "api_digest=" . $this->getPublicPrivateSignature($url);
        }

        $httpHeadersToSet = array();
        if (is_array($parameters)) {
            $newParams = '';
            foreach ($parameters as $key => $value) {
                if ($newParams != '') {
                    $newParams .= '&';
                }
                if (!is_array($value)) {
                    $newParams .= "$key=" . urlencode($value);
                } else {
                    foreach ($value as $currentValue) {
                        $newParams .= $key . '[]=' . urlencode($currentValue);
                    }
                }
            }
            $parameters = $newParams;
        }
        if (($httpMethod == 'GET') && ! is_null($parameters) && (count($parameters) > 0)) {
            $url .= (($this->authenticationMethod == "private") ? '&' : '?') . $parameters;
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Return Path API PHP');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        if ($this->authenticationMethod == "http_basic") {
            curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':');
        }
        if ($this->ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($httpMethod != 'GET') {
            if ($httpMethod == 'POST') {
                curl_setopt($curl, CURLOPT_POST, true);
                if (!is_null($parameters)) {
                    $httpHeadersToSet[] = 'Content-Length: ' . strlen($parameters);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
                } else {
                    $httpHeadersToSet[] = 'Content-Length: 0';
                }
            } else {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpMethod);
                if ($httpMethod == 'PUT') {
                    if (is_string($parameters)) {
                        $httpHeadersToSet[] = 'Content-Length: ' . strlen($parameters);
                    }
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
                }
            }
        }

        if (count($httpHeadersToSet) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeadersToSet);
        }

        if ($this->saveHeaders) {
            $this->responseHeaders = array();
            $this->requestHeaders = array();
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, '_setHeader'));
            curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
        }
        $result = curl_exec($curl);

        $httpHeadersIn = ($this->saveHeaders) ? $this->responseHeaders : null;
        $httpHeadersOut = ($this->saveHeaders) ? preg_split('/(\\n|\\r){1,2}/', curl_getinfo($curl, CURLINFO_HEADER_OUT)) : null;

        $acceptableContentTypes = array('application/json');
        $response = new ReturnPathResponse(
            curl_getinfo($curl, CURLINFO_HTTP_CODE),
            $httpHeadersOut,
            $httpHeadersIn,
            curl_getinfo($curl, CURLINFO_CONTENT_TYPE),
            $result,
            $acceptableContentTypes);
        curl_close($curl);
        if ($response->hasError()) {
            $this->lastResponse = $response;
            return false;
        }
        return $response;
    }

    /**
     * Internal method used to build HTTP header trace
     * @param $curl
     * @param $headers
     * @return int
     */
    public function _setHeader($curl, $headers)
    {
        $this->responseHeaders[] = trim($headers, "\n\r");
        return strlen($headers);
    }

}

?>
