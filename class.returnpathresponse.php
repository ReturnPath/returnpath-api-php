<?php

/**
 * Class to manage Return Path API responses
 *
 * Objects of this class will be created automatically
 * from the ReturnPath object, when calling ->get(), ->put
 * ->post or ->delete
 *
 * In most cases, you only need to use the ->getData() method
 */
class ReturnPathResponse
{

    protected $headers;
    protected $rawResponseHeaders;
    protected $rawRequestHeaders;
    protected $rawResponse;
    protected $decodedResponse;
    protected $httpCode;
    protected $contentType;
    protected $hasError;

    function __construct($httpCode, $requestHeaders, $responseHeaders, $contentType, $rawResponse, $acceptableContentTypes = array('application/json'))
    {
        $this->httpCode = (int)$httpCode;
        $this->contentType = $contentType;
        $this->rawResponse = $rawResponse;
        $this->rawResponseHeaders = (is_array($responseHeaders)) ? $responseHeaders : false;
        $this->rawRequestHeaders = (is_array($requestHeaders)) ? $requestHeaders : false;
        $this->hasError = false;
        $this->headers = array('request' => $requestHeaders, 'response' => null);
        $this->_decodeResponse($acceptableContentTypes);
        $this->_parseHeaders('response');
        $this->_parseHeaders('request');
    }

    private function _parseHeaders($which = 'response')
    {
        $raw = ($which == 'response') ? $this->rawResponseHeaders : $this->rawRequestHeaders;

        if ($raw !== false) {
            $headers = array();
            $headers[($which == 'response') ? 'Status-Line' : 'Request-Line'] = trim(array_shift($raw));
            $headerName = '';
            foreach ($raw as $headerLine) {
                $firstChar = substr($headerLine, 0, 1);
                if ($firstChar == chr(32) || $firstChar == chr(9)) {
                    // continuing value of previous header line
                    if (is_array($headers[$headerName])) {
                        $idx = count($headers[$headerName]) - 1;
                        $headers[$headerName][$idx] .= "\n" . trim($headerLine);
                    } else {
                        $headers[$headerName] .= "\n" . trim($headerLine);
                    }
                } else {
                    // New header line
                    $idx = strpos($headerLine, ':');
                    if ($idx !== false) {
                        $headerName = trim(substr($headerLine, 0, $idx));
                        $headerValue = trim(substr($headerLine, $idx + 1));
                        if (array_key_exists($headerName, $this->headers)) {
                            // Already have an occurence of this header. Make the header an array with all occurences
                            if (is_array($headers[$headerName])) {
                                $headers[$headerName][] = $headerValue;
                            } else {
                                $headers[$headerName] = array(
                                    $headers[$headerName],
                                    $headerValue
                                );
                            }
                        } else {
                            // First occurence, simply give value as a string
                            $headers[$headerName] = $headerValue;
                        }
                    }
                }
            }
            $this->headers[$which] = $headers;
        }
    }

    private function _decodeResponse($acceptableContentTypes)
    {
        if (!(($this->httpCode >= 200) && ($this->httpCode < 400))) {
            $this->hasError = true;
        }
        if (!in_array($this->contentType, $acceptableContentTypes)) {
            $this->hasError = true;
            return;
        }
        if ($this->contentType == 'application/json') {
            $this->decodedResponse = json_decode($this->rawResponse, true);
        }
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function getRawResponseHeaders()
    {
        return $this->rawResponseHeaders;
    }

    public function getResponseHeaders()
    {
        return $this->headers['response'];
    }

    public function getRawRequestHeaders()
    {
        return $this->rawRequestHeaders;
    }

    public function getRequestHeaders()
    {
        return $this->headers['request'];
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * Returns the response body parsed into a PHP structure. To get the JSON
     * string, use getRawResponse()
     */
    public function getData()
    {
        return $this->decodedResponse;
    }

    public function hasError()
    {
        return $this->hasError;
    }
}

?>
