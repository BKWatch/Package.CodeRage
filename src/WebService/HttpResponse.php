<?php

/**
 * Defines the class CodeRage\WebService\HttpResponse
 * 
 * File:        CodeRage/WebService/HttpResponse.php
 * Date:        Tue May 15 13:05:33 MDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Util\Bytes;

/**
 * @ignore
 */

/**
 * Very simple HTTP response class implemented using cURL, useful for testing
 */
class HttpResponse {

    /**
     * Construcs an instance of CodeRage\WebService\HttpResponse
     *
     * @param string $status The HTTP status code
     * @param array $headers A list of headers, represented as ($name, $value)
     *   pairs
     * @param string $body The response body
     * @param string $bodyFile The file, if any, containing the response body
     */
    public function __construct($status, $headers, $body, $bodyFile = null)
    {
        $this->status = $status;
        $this->body = $body;
        $this->bodyFile = $bodyFile;
        $this->headers = [];
        foreach ($headers as $p) {
            list($name, $value) = $p;
            $this->headers[$name][] = $value;
        }
    }

    /**
     * Returns the HTTP status code
     *
     * @return int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Returns true if the status code is of the form 2xx
     *
     * @return boolean
     */
    public function success()
    {
        return $this->status >= 200 & $this->status < 300;
    }

    /**
     * Returns the value of the content-type header, if any
     *
     * @return int
     */
    public function contentType()
    {
        return $this->headerField('content-type');
    }

    /**
     * Returns the list of headers, as [$name, $value] pairs
     *
     * @return array
     */
    public function headers()
    {
        $result = [];
        foreach ($this->headers as $name => $values)
            foreach ($values as $value)
                $result[] = [$name, $value];
        return $result;
    }

    /**
     * Returns the value of the header with the given name, if any. If there are
     * multiple headers with the same name, returns the value of the first
     * header.
     *
     * @param string
     * @return string
     */
    public function headerField($name)
    {
        $name = strtolower($name);
        return isset($this->headers[$name]) ?
            $this->headers[$name][0] :
            null;
    }

    /**
     * Returns the list of headers with the given name
     *
     * @param string
     * @return string
     */
    public function headerFieldList($name)
    {
        $name = strtolower($name);
        return isset($this->headers[$name]) ?
            $this->headers[$name] :
            null;
    }

    /**
     * Returns the response body
     *
     * @return string
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * Returns the file, if any, containing the response body
     *
     * @return string
     */
    public function bodyFile()
    {
        return $this->bodyFile;
    }

    /**
     * Returns a descriptive label to go with the given HTTP status code.
     * Implementation taken from Wikipedia.
     *
     * @param int $status
     * @return string
     */
    public static function statusText($status)
    {
        switch ($status) {
            case '100':
                return 'Continue';
            case '101':
                return 'Switching Protocols';
            case '102':
                return 'Processing';
            case '200':
                return 'OK';
            case '201':
                return 'Created';
            case '202':
                return 'Accepted';
            case '203':
                return 'Non-Authoritative Information';
            case '204':
                return 'No Content';
            case '205':
                return 'Reset Content';
            case '206':
                return 'Partial Content';
            case '207':
                return 'Multi-Status';
            case '208':
                return 'Already Reported';
            case '226':
                return 'IM Used';
            case '300':
                return 'Multiple Choices';
            case '301':
                return 'Moved Permanently';
            case '302':
                return 'Found';
            case '303':
                return 'See Other';
            case '304':
                return 'Not Modified';
            case '305':
                return 'Use Proxy';
            case '306':
                return 'Switch Proxy';
            case '307':
                return 'Temporary Redirect';
            case '308':
                return 'Permanent Redirect';
            case '400':
                return 'Bad Request';
            case '401':
                return 'Unauthorized';
            case '402':
                return 'Payment Required';
            case '403':
                return 'Forbidden';
            case '404':
                return 'Not Found';
            case '405':
                return 'Method Not Allowed';
            case '406':
                return 'Not Acceptable';
            case '407':
                return 'Proxy Authentication Required';
            case '408':
                return 'Request Timeout';
            case '409':
                return 'Conflict';
            case '410':
                return 'Gone';
            case '411':
                return 'Length Required';
            case '412':
                return 'Precondition Failed';
            case '413':
                return 'Request Entity Too Large';
            case '414':
                return 'Request-URI Too Long';
            case '415':
                return 'Unsupported Media Type';
            case '416':
                return 'Requested Range Not Satisfiable';
            case '417':
                return 'Expectation Failed';
            case '418':
                return "I'm a teapot";
            case '419':
                return 'Authentication Timeout';
            case '420':
                return 'Method Failure';
            case '422':
                return 'Unprocessable Entity';
            case '423':
                return 'Locked';
            case '424':
                return 'Failed Dependency';
            case '425':
                return 'Unordered Collection';
            case '426':
                return 'Upgrade Required';
            case '428':
                return 'Precondition Required';
            case '429':
                return 'Too Many Requests';
            case '431':
                return 'Request Header Fields Too Large';
            case '440':
                return 'Login Timeout';
            case '444':
                return 'No Response';
            case '449':
                return 'Retry With';
            case '450':
                return 'Blocked by Windows Parental Controls';
            case '451':
                return 'Unavailable For Legal Reasons';
            case '494':
                return 'Request Header Too Large';
            case '495':
                return 'Cert Error';
            case '496':
                return 'No Cert';
            case '496':
                return 'HTTP to HTTPS';
            case '499':
                return 'Client Closed Request';
            case '500':
                return 'Internal Server Error';
            case '501':
                return 'Not Implemented';
            case '502':
                return 'Bad Gateway';
            case '503':
                return 'Service Unavailable';
            case '504':
                return 'Gateway Timeout';
            case '505':
                return 'HTTP Version Not Supported';
            case '506':
                return 'Variant Also Negotiates';
            case '507':
                return 'Insufficient Storage';
            case '508':
                return 'Loop Detected';
            case '509':
                return 'Bandwidth Limit Exceeded';
            case '510':
                return 'Not Extended';
            case '511':
                return 'Network Authentication Required';
            case '520':
                return 'Origin Error';
            case '522':
                return 'Connection timed out';
            case '523':
                return 'Proxy Declined Request';
            case '524':
                return 'A timeout occurred';
            case '598':
                return 'Network read timeout error';
            case '599':
                return 'Network connect timeout error';
            default:
                return null;
        }
    }

    /**
     * The HTTP status code
     *
     * @var int
     */
    private $status;

    /**
     * An associative array mapping header names to lists of values
     *
     * @var string
     */
    private $headers;

    /**
     * The response body
     *
     * @var string
     */
    private $body;

    /**
     * The file, if any, containing the response body
     *
     * @var string
     */
    private $bodyFile;
}
