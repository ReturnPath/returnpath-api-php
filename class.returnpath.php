<?php

/**
 * Return Path API client library
 */

require_once dirname(__FILE__) . '/class.returnpathresponse.php';

/**
 * Class to manage Return Path API access
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

    /**
     * Instantiate a new object.
     */
    function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->saveHeaders = false;
        $this->ssl = true;
        $this->endPoint = 'api.returnpath.com';
        $this->apiVersion = 'v1';
        $this->lastResponse = null;
    }

    /**
     * Specify the API endpoint.
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
     * Returns the ReturnPathResponse object for the last API call.
     * @return ReturnPathResponse
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }


    public function saveHeaders($yes = true)
    {
        $this->saveHeaders = $yes;
    }

    public function get($action = '', $parameters = null, $acceptableContentTypes = null)
    {
        return $this->_doCall('GET', $action, $parameters, $acceptableContentTypes);
    }

    public function put($action, $parameters = null, $httpHeadersToSet = array())
    {
        return $this->_doCall('PUT', $action, $parameters, null, $httpHeadersToSet);
    }

    public function post($action = '', $parameters = null, $httpHeadersToSet = array())
    {
        return $this->_doCall('POST', $action, $parameters, null, $httpHeadersToSet);
    }

    public function delete($action = '', $parameters = null)
    {
        return $this->_doCall('DELETE', $action, $parameters);
    }

    protected function _doCall($httpMethod, $action, $parameters = null, $acceptableContentTypes = null, $httpHeadersToSet = array())
    {
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

        $url = 'http';
        if ($this->ssl) {
            $url = 'https';
        }
        $url .= '://' . $this->endPoint . '/';
        if ($this->apiVersion != '') {
            $url .= $this->apiVersion . '/';
        }
        $url .= $action;
        if ($httpMethod == 'GET') {
            $url .= '?' . $parameters;
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Return Path API PHP');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
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

        if (is_null($acceptableContentTypes)) {
            $acceptableContentTypes = array('application/json');
        }
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

    public function _setHeader($curl, $headers)
    {
        $this->responseHeaders[] = trim($headers, "\n\r");
        return strlen($headers);
    }

    protected function _filterParams($givenParams, $validParams, $requiredParams = array())
    {
        $filteredParams = array();
        foreach ($givenParams as $name => $value) {
            if (in_array(strtolower($name), $validParams)) {
                $filteredParams[strtolower($name)] = $value;
            } else {
                return false;
            }
        }
        foreach ($requiredParams as $name) {
            if (!array_key_exists(strtolower($name), $filteredParams)) {
                return false;
            }
        }
        return $filteredParams;
    }

}

?>
