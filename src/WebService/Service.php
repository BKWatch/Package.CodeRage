<?php

/**
 * Defines the class CodeRage\WebService\Service
 *
 * File:        CodeRage/WebService/Service.php
 * Date:        Sun Mar 20 13:25:04 MDT 2011
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
use CodeRage\Access\Session;
use CodeRage\Access\User;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\ConfigToken;
use CodeRage\WebService\Types;
use CodeRage\Xml;
use CodeRage\Xml\XsltProcessor;

/**
 * @ignore
 */

/**
 * Encapsultates a webservice that can be called via SOAP, XML over HTTP POST,
 * JSON, or JSON over HTTP GET
 */
abstract class Service extends \CodeRage\Util\BasicSystemHandle {

    /**
     * Regular expression matching identifiers
     *
     * @var string
     */
    const MATCH_IDENTIFIER = '/^[a-z][_a-z0-9]+$/i';

    /**
     * Regular expression matching identifiers
     *
     * @var string
     */
    const MATCH_TRANSFORM_PATH =
        '#^[-_a-z0-9]+[-_a-z0-9.]*(/[-_a-z0-9]+[-_a-z0-9.]*)*$#i';

    /**
     * The length of the prefix of long requests that should be logged
     */
    const REQUEST_TEASER_LENGTH = 10000;

    /**
     * The length of the prefix of long responses that should be logged
     *
     * @var int
     */
    const RESPONSE_TEASER_LENGTH = 65536;

    /**
     * Configuration variable used to store the IDE key for remote debugging
     *
     * @var string
     */
    const IDEKEY_CONFIG_VARIABLE = 'coderage.webservice.service.idekey';

    /**
     * The library interface version; affects how URL parameters are treated
     *
     * @var string
     */
    private $version;

    /**
     * The log
     *
     * @var CodeRage\Log
     */
    private $log;

    /**
     * Maps operation names to XML encoders
     *
     * @var array
     */
    private $xmlEncoder = [];

    /**
     * One of 'soap', 'xml-post', 'json-post', or 'json-get'.
     *
     * @var string
     */
    private $protocol;

    /**
     * true if the web service log session should be shared with tools it
     * invokes using runTool(); by default the log session is shared
     *
     * @var boolean
     */
    private $shareLogSession;

    /**
     * Constructs an instance of CodeRage\WebService\Service
     *
     * @param string $options The options array; supports all options supported
     *   by CodeRage\Util\BasicSystemHandle, as well as:
     *     version - The library interface version; affects how URL
     *       parameters are treated
     *     shareLogSession - true if the web service log session should be
     *       shared with tools it invokes using runTool(); by default the log
     *       session is shared
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->version = isset($options['version']) ?
            $options['version'] :
            '1.0';
        $this->shareLogSession = isset($options['shareLogSession']) ?
            $options['shareLogSession'] :
            true;
    }

    /**
     * Handles the current webservice request. Does not return.
     */
    public final function handle()
    {
        try {
            $level = E_ALL & ~E_STRICT;
            if (defined('E_DEPRECATED'))
                $level &= ~E_DEPRECATED & ~E_USER_DEPRECATED;
            set_error_handler(
                function()
                {
                    $args = func_get_args();
                    $this->handleError(...$args);
                },
                $level
            );
            $this->handleImpl();
            restore_error_handler();
        } catch (Throwable $e) {
            restore_error_handler();
            $outer = Error::wrap($e);
            $outer->log($this->log());
            if ($outer->status() == 'INTERNAL_ERROR') {
                header('HTTP/1.0 500 Internal Error');
            } else {

                // Exception relates to SOAP, XML over HTTP POST, or JSON
                // encoding or decoding
                header('HTTP/1.0 400 Bad Request');
            }
        }
        exit;
    }

    /**
     * Returns library interface version; affects how URL parameters are treated
     *
     * @return string
     */
    public final function version() { return $this->version; }

    /**
     * Returns the path to the service description.
     *
     * @return string
     */
    public final function wsdl() { return $this->doWsdl(); }

    /**
     * Returns the XML encoder
     *
     * @param string $operation The operation name
     * @return CodeRage\Util\XmlEncoder
     */
    public final function xmlEncoder($operation = '')
    {
        if (!isset($this->xmlEncoder[$operation]))
            $this->xmlEncoder[$operation] = $this->doXmlEncoder($operation);
        return $this->xmlEncoder[$operation];
    }

    /**
     * Returns the path to the directory containing XSL transformations that
     * can be applied to XML output
     *
     * @return string
     */
    public final function transformDirectory()
    {
        return $this->defineTransformDirectory();
    }

    /**
     * Specifies whether the web service log session should be shared with tools
     * it invokes using runTool(); by default the log session is shared
     *
     * @param boolean $shared
     */
    public function shareLogSession($share)
    {
        $this->shareLogSession = $share;
    }

    /**
     * Executes the named operation with the given input.
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input, encoded as a string, an array,
     *   or an instance of stdClass
     * @return mixed The result of performing the operation encoded as a string,
     *   an array, or an instance of stdClass
     */
    public final function execute($operation, $input)
    {
        $config = $this->installConfig();
        $output = null;
        try {
            $output = $this->executeImpl($operation, $input);
        } finally {
            if ($config !== null)
                Config::setCurrent($config);
        }
        return $output;
    }

    /**
     * Returns the path to the service description.
     *
     * @return string
     */
    protected abstract function doWsdl();

    /**
     * Returns an instance of CodeRage\Util\XmlEncoder
     *
     * @param string The operation name
     * @return CodeRage\Util\XmlEncoder
     */
    protected abstract function doXmlEncoder($operation = '');

    /**
     * Returns the path to the directory containing XSL transformations that
     * can be applied to XML output
     *
     * @return string
     */
    protected function defineTransformDirectory()
    {
        return null;
    }

    /**
     * Returns an associative array of options to be passed to the SoapServer
     * constructor.
     *
     * The default implementation returns an empty array.
     *
     * @return array
     */
    protected function defineSoapOptions()
    {
        return ['cache_wsdl' => WSDL_CACHE_NONE];
    }

    /**
     * If the given input encodes a username/password pair, authorization
     * token, or a session ID, returns an object with properties among
     * "username", "password", "authtoken", and "sessionid"
     *
     * The default implementation returns null.
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input
     * @return stdClass
     */
    protected function extractCredentials($operation, $input)
    {
        return null;
    }

    /**
     * Throws an exception with status 'UNAUTHORIZED' if the specified
     * credentials are valid
     *
     * The default implementation tests the credentials using
     * CodeRage\Access\Session::authenticate()
     *
     * @param string $operation The operation name
     * @param stdClass $credentials an object with properties among "username",
     *   "password", "authtoken", and "sessionid"
     * @return CodeRage::Access::User The authenticated user
     * @throws CodeRage\Error if the credentials are invalid
     */
    protected function testCredentials($credentials)
    {
        return User::load((array) $credentials);
    }

    /**
     * Called immediately before an operation is executed
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input, encoded as a string, an array,
     *   or an instance of stdClass
     * @return mixed An arbitrary piece of data to be passed to the
     *   corresponding invocation to postExecute() to identify this invocation
     *   of preExecute()
     */
    protected function preExecute($operation, $input)
    {
        return null;
    }

    /**
     * Called immediately after an operation is executed
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input, encoded as a string, an array,
     *   or an instance of stdClass
     * @param mixed $output The operation output
     * @param mixed $token The value, if any, returned by the corresponding
     *   invocation of preExecute()
     */
    protected function postExecute($operation, $input, $output, $token)
    {

    }

    /**
     * Called after authentication to check whether the user is authorized to
     * invoke the specified operation with the given input
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input, encoded as a string, an array,
     *   or an instance of stdClass
     * @throws CodeRage\Error with status "ACCESS_DENIED"
     */
    protected function authorize($operation, $input)
    {
        return null;
    }

    /**
     * Gives subclasses a chance to modify decoded input before it is passed
     * to the webservice operation.
     *
     * The default implementation returns $input.
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input
     * @return mixed The value to pass to the webservice operation
     */
    protected function transformInput($operation, $input)
    {
        return $input;
    }

    /**
     * Gives subclasses a chance to modify webservice operation output before it
     * is encoded.
     *
     * The default implementation returns $input.
     *
     * @param string $operation The operation name
     * @param mixed $output The operation output
     * @return mixed The value from which the webservice response should be
     *   generated
     */
    protected function transformOutput($operation, $output)
    {
        return $output;
    }

    /**
     * If the given exception can be handled, returns a value to be encoded
     * and passed to the webservice client.
     *
     * The default implementation rethrows $error.
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input
     * @param Throwable $error The exception
     * @return mixed
     */
    protected function handleException($operation, $input, Throwable $error)
    {
        throw $error;
    }

    /**
     * Returns on of 'soap', 'xml-post', 'json-post', or 'json-get'
     *
     * @return string
     */
    protected final function protocol() { return $this->protocol; }

    /**
     * Returns the authenticated user, if any, associated with the current
     * operation
     *
     * @return CodeRage\Access\User
     */
    protected final function user()
    {
        return $this->session() !== null ?
           $this->session()->user() :
           null;
    }
    /**
     * Converts the given value to a boolean
     *
     * @param mixed $value A value that validates as a boolean using
     *   CodeRage\WebService\validate()
     */
    protected final function convertToBoolean($value)
    {
        return (is_bool($value) && $value) ||
               $value === 1 ||
               $value === 'true' ||
               $value === '1';
    }

    /**
     * Returns an instance of CodeRage\WebService\Legacy\Search
     *
     * @param array $fields An array of field specifications suitable for
     *   passing to the CodeRage\WebService\Search constructor
     * @param mixed An object whose 'filters' property, in any, encodes
     *   an array of filters, and whose 'sort' property, if any, encodes
     *   an array of sort specifiers
     * @param boolean $strict false if fields referenced in $input that don't
     *   appear in $fields should be ignored
     */
    protected final function createSearch($fields, $input, $strict = true)
    {
        // Input must be validated carefully because it may come directly from
        // the webservice client
        $search = new Legacy\Search($fields);
        if (isset($input->filters)) {
            Types::validate($input->filters, 'array', 'filter list');
            foreach ($input->filters as $filter) {
                if (!isset($filter->field))
                    throw new
                        Error([
                            'status' => 'MISSING_PARAMETER',
                            'Missing filter field name'
                        ]);
                $field = $filter->field;
                if (!$strict && !isset($fields[$field]))
                    continue;
                Types::validate($field, 'string', 'filter field name');
                if (!isset($filter->type))
                    throw new
                        Error([
                            'status' => 'MISSING_PARAMETER',
                            'Missing filter type'
                        ]);
                $type = $filter->type;
                Types::validate($type, 'string', 'filter type');
                if (!isset($filter->value))
                    throw new
                        Error([
                            'status' => 'MISSING_PARAMETER',
                            'Missing filter value'
                        ]);
                $value = $filter->value;
                $search->addFilter($field, $type, $value);
            }
        }
        if (isset($input->sort)) {
            Types::validate($input->sort, 'array', 'sort specifiers');
            foreach ($input->sort as $sort) {
                if (!isset($sort->name))
                    throw new
                        Error([
                            'status' => 'MISSING_PARAMETER',
                            'Missing sort field name'
                        ]);
                $field = $sort->name;
                if (!$strict && !isset($fields[$field]))
                    continue;
                Types::validate($field, 'string', 'sort field name');
                if (!isset($sort->direction))
                    throw new
                        Error([
                            'status' => 'MISSING_PARAMETER',
                            'Missing sort direction'
                        ]);
                $direction = $sort->direction;
                $search->addSortSpecifier($field, $direction);
            }
        }
        if (isset($input->range)) {
            Types::validate($input->range, 'object', 'range specifier');
            $range = $input->range;
            if (!isset($range->begin))
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'Missing beginning of range'
                    ]);
            if (!isset($range->end))
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'Missing end of range'
                    ]);
            $search->setRange($range->begin, $range->end);
        }
        return $search;
    }

    /**
     * Runs the specified tool and returns the result as a native data structure
     *
     * @param array $options An associative array of options:
     *   class - A class name, specified as a sequence of identifiers separated
     *      by dots (required)
     *   classPath - A directory to be dearched for class definitions; the
     *     source file to be search for is formed from class by replacing dots
     *     with slashes and appending the extension '.php' (optional)
     *   params - An associative array of tool parameters
     *   encoding - An associative array of encoding options to be passed to the
     *     native data encoder constructor (optional)
     *   config - An associative array of configuration variables used to
     *     construct a configuration to replace the project configuration during
     *     tool execution (optional)
     */
    protected final function runTool($options)
    {
        $options +=
            [
                'logSessionId' => $this->log()->sessionid(),
                'debug' => $this->urlParams()['XDEBUG_SESSION_START'] ?? null,
                'session' => $this->session() !== null ?
                    ['sessionid' => $this->session()->sessionid()] :
                    null
            ];
        return \CodeRage\Tool\Runner::run($options);
    }

    /**
     * Implementation of handle()
     */
    private final function handleImpl()
    {
        // Fetch request
        $request = file_get_contents("php://input");
        $this->log()->logMessage("Request URI = {$_SERVER['REQUEST_URI']}");
        $teaser = strlen($request) > self::REQUEST_TEASER_LENGTH ?
            substr($request, 0, self::REQUEST_TEASER_LENGTH) . ' ...' :
            $request;
        $this->log()->logMessage("Request = $teaser");
        $this->log()->logMessage("Client IP = {$_SERVER['REMOTE_ADDR']}");
        if (!mb_check_encoding($request, 'UTF-8'))
            $this->log()->logWarning("Request is non UTF-8 encoded");
        $urlParams = $this->urlParams();

        // Handle request for WSDL
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($urlParams['WSDL'])) {
            $wsdl = $this->doWsdl();
            $dom = Xml::loadDocument($wsdl);
            $doc = $dom->documentElement;
            foreach (Xml::childElements($doc, 'service') as $service) {
                foreach (Xml::childElements($service, 'port') as $port) {
                    foreach (Xml::childElements($port, 'address') as $add)
                    {
                        $config = Config::current();
                        $location =
                            preg_replace(
                                '{(https?://)[^/]+}',
                                '$1' . $config->getRequiredProperty('site_domain'),
                                $add->getAttribute('location')
                            );
                        $add->setAttribute('location', $location);
                    }
                }
            }
            header('Content-Type: text/xml');
            echo $dom->saveXml();
            exit;
        }

        // Generate response
        $this->protocol = isset($urlParams['PROTOCOL']) ?
            $urlParams['PROTOCOL'] :
            'SOAP';
        $response = $contentType = null;
        switch ($this->protocol) {
        case 'SOAP':
            $response = $this->handleSoap($request);
            break;
        case 'XML':
            list($response, $contentType) = $this->handleXml($request);
            break;
        case 'JSON':
            $contentType = 'application/json; charset=UTF-8';
            $response = $this->handleJson($request);
            break;
        default:
            throw new Error(['details' => "Invalid protocol: $this->protocol"]);
        }

        // Output response
        $teaser = strlen($response) > self::RESPONSE_TEASER_LENGTH ?
            substr($response, 0, self::RESPONSE_TEASER_LENGTH) . ' ...' :
            $response;
        $this->log()->logMessage("Response = $teaser");
        if ($contentType)
            header("Content-Type: $contentType");
        echo $response;
    }

    /**
     * Handles SOAP requests
     *
     * @param string $resuest The request body
     * @return string The response body
     */
    private function handleSoap($request)
    {
        $server =
            new ServiceSoapServer(
                    $this,
                    $this->doWsdl(),
                    $this->defineSoapOptions()
                );
        return $server->handle($request);
    }

    /**
     * Handles requests with protocol "XML"
     *
     * @param string $resuest The request body
     * @return array A pair [$response, $contentType]
     */
    private function handleXml($request)
    {
        $operation = $input = null;
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            list($operation, $input) = $this->decodeUrl();
        } else {
            if (!isset($_SERVER['HTTP_CONTENT_TYPE']))
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' => "Missing Content-Type header"
                    ]);
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
            if (!preg_match('#^text/xml\b#', $contentType))
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' =>
                            "Invalid Content-Type header: expected " .
                            "'text/xml'; found '$contentType'"
                    ]);

            // Parse input
            $dom = null;
            try {
                $dom = Xml::loadDocumentXml($request);
            } catch (Throwable $e) {
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' => 'Invalid XML',
                        'inner' => $e
                    ]);
            }
            $eltName = $dom->documentElement->localName;
            if (substr($eltName, -7) != 'Request')
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' => "Invalid document element: $eltName"
                    ]);
            $operation = substr($eltName, 0, -7);
            $input =
                $this->xmlEncoder($operation)->decode($dom->documentElement);
        }

        // Handle output transformations
        $output = $transformPath = null;
        try {
            $urlParams = $this->urlParams();
            if (isset($urlParams['TRANSFORM'])) {
                $transform = $urlParams['TRANSFORM'];
                if (!preg_match(self::MATCH_TRANSFORM_PATH, $transform))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => 'Illegal transform path'
                        ]);
                $directory = $this->transformDirectory();
                if ($directory === null)
                    throw new
                        Error([
                            'status' => 'UNSUPPORTED_OPERATION',
                            'message' =>
                                'Output transformations are not supported by ' .
                                'this web service'
                        ]);
                $transformPath = "$directory/$transform";
                try {
                    File::checkReadable($transformPath);
                } catch (Throwable $e) {
                    $transformPath = null;
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => 'Unsupported transformation'
                        ]);
                }
            }
        } catch (Throwable $e) {
            $e = Error::wrap($e);
            $e->log($this->log());
            $output = $this->handleException($operation, $input, $e);
        }

        // Execute operation
        if ($output === null)
            $output = $this->execute($operation, $input);

        // Return XML
        $dom = new \DOMDocument;
        $xmlEncoder = $this->xmlEncoder($operation);
        if ($transformPath !== null) {

            // Load transformation
            $proc = new XsltProcessor;
            $proc->loadStylesheetFromFile($transformPath);

            // Construct transformation source document
            $requestElt = $xmlEncoder->encode('request', $input, $dom);
            $responseElt = $xmlEncoder->encode('response', $output, $dom);
            $operationElt =
                $dom->createElementNS($requestElt->namespaceURI, $operation);
            $operationElt->appendChild($requestElt);
            $operationElt->appendChild($responseElt);
            $dom->appendChild($operationElt);

            // Apply transformation
            $proc->loadSourceFromDoc($dom);
            $dom = $proc->transformToDoc();
            if ($dom->documentElement === null)
                $dom->loadXml($dom->saveXml());  // Reparse text output
            $contentType = $dom->documentElement->localName == 'html' ?
                'text/html; charset=UTF-8' :
                'text/xml; charset=UTF-8';
        } else {
            $responseElt =
                $xmlEncoder->encode(
                    "{$operation}Response",
                    $output,
                    $dom
                );
            $dom->appendChild($responseElt);
            $contentType = 'text/xml; charset=UTF-8';
        }
        return [$dom->saveXml(), $contentType];
    }

    /**
     * Handles requests with protocol "JSON"
     *
     * @param string $resuest The request body
     * @return string The response body
     */
    private function handleJson($request)
    {
        $operation = $input = null;
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            list($operation, $input) = $this->decodeUrl();
        } else {
            if (!isset($_SERVER['HTTP_CONTENT_TYPE']))
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' => "Missing Content-Type header"
                    ]);
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
            if (!preg_match('#^application/json\b#', $contentType))
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' =>
                            "Invalid Content-Type header: expected " .
                            "'application/json'; found '$contentType'"
                    ]);
            $urlParams = $this->urlParams();
            if (!isset($urlParams['OPERATION']))
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' => "Missing operation name"
                    ]);
            $operation = $urlParams['OPERATION'];
            if (!preg_match(self::MATCH_IDENTIFIER, $operation))
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'message' => "Invalid operation name: $operation"
                    ]);
            $input = json_decode($request);
            if ($input === null && !preg_match('/^\s*null\s*$/', $request)) {
                throw new
                    Error([
                        'status' => 'BAD_REQUEST',
                        'details' => 'JSON encoding error'
                    ]);
            }
        }
        $output = $this->execute($operation, $input);
        try {
            return json_encode($output);
        } catch (Throwable $e) {
            $outer = Error::wrap($e);
            $outer->log($this->log());
            return json_encode($this->handleException($operation, $input, $e));
        }
    }

    /**
     * Installs the configuration specified by the CONFIG URL parameter, if
     * any
     *
     * @return CodeRage\Config The current configuration at the time the method
     *   is invoked, if the CONFIG URL paramer is present
     * @throws CodeRage\Error If the CONFIG URL parameter is present, but cannot
     *   be used to install a valid configuration
     */
    private function installConfig()
    {
        $prev = null;
        if (array_key_exists('CONFIG', $this->urlParams())) {
            $prev = Config::current();
            ConfigToken::load($this->urlParams()['CONFIG']);
        }
        return $prev;
    }

    /**
     * Authenticates the user, checks access rights, and executes the named
     * operation with the given input
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input, encoded as a string, an array,
     *   or an instance of stdClass
     * @return mixed The result of performing the operation encoded as a string,
     *   an array, or an instance of stdClass
     */
    private function executeImpl($operation, $input)
    {
        // Call preExecute(), authenticate, and check access rights
        $token = null;
        try {
            $token = $this->preExecute($operation, $input);
            $this->authenticate($operation, $input);
            $this->authorize($operation, $input);
        } catch (Throwable $e) {
            $e = Error::wrap($e);
            try {
                return $this->handleException($operation, $input, $e);
            } catch (Throwable $e) {
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'inner' => $e
                    ]);
            }
        }

        // Invoke operation
        try {
            $input = $this->transformInput($operation, $input);
            $output = $this->{'_' . $operation}($input);
            $output = $this->transformOutput($operation, $output);
        } catch (Throwable $e) {
            $e = Error::wrap($e);
            $e->log($this->log());
            $output = $this->handleException($operation, $input, $e);
        }

        // Call postExecute()
        try {
            $this->postExecute($operation, $input, $output, $token);
            return $output;
        } catch (Throwable $e) {
            $e = Error::wrap($e);
            $e->log($this->log());
        } finally {
            $this->userid = 0;  // Unauthenticate
        }
    }

    /**
     * Returns the URL parameters of the current request
     *
     * @param boolean $assoc true to return the parameters as an associative
     *   array rather than a list of pairs
     * @return array
     */
    private function urlParams($assoc = true)
    {
        static $list, $map;
        if ($list === null) {
            $list = $map = [];
            $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            $parts = explode('&', $query);
            foreach ($parts as $p) {
                $n = $v = null;
                if (($pos = strpos($p, '=')) !== false) {
                    $n = rawurldecode(substr($p, 0, $pos));
                    $v = rawurldecode(substr($p, $pos + 1));
                } else {
                    $n = rawurldecode($p);
                    $v = '';
                }
                $list[] = [$n, $v];
                $map[$n] = $v;
            }
            if (version_compare($this->version, '2.0') < 0) {
                foreach (['debug_tools', 'operation', 'protocol'] as $n) {
                    if (isset($map[$n])) {
                        $map[strtoupper($n)] = $map[$n];
                        unset($map[$n]);
                    }
                }
                if (isset($map['PROTOCOL'])) {
                    if ($map['PROTOCOL'] == 'xml-post')
                        $map['PROTOCOL'] = 'XML';
                    $map['PROTOCOL'] = strtoupper($map['PROTOCOL']);
                }
            }
        }
        return $assoc ? $map : $list;
    }

    /**
     * Returns the webservice operation and input encoded as URL parameters for
     * GET requests
     *
     * @return A pair ($operation, $input) where $operation is a string and
     *   $input is an instance of stdClass
     */
    private function decodeUrl()
    {
        $operation = null;
        $encoding = [];
        foreach ($this->urlParams(false) as list($n, $v)) {
            if ( $n == 'CONFIG' ||
                 $n == 'DEBUG' ||
                 $n == 'PROTOCOL' ||
                 $n == 'TRANSFORM' ||
                 $n == 'XDEBUG_SESSION_START' )
            {
                // No-op
            } elseif ($n == 'OPERATION') {
                $operation = $v;
            } else {
                $encoding[] = [$n, $v];
            }
        }
        if ($operation === null)
            throw new
                Error([
                    'status' => 'BAD_REQUEST',
                    'message' => "Missing operation name"
                ]);
        if (!preg_match(self::MATCH_IDENTIFIER, $operation))
            throw new
                Error([
                    'status' => 'BAD_REQUEST',
                    'message' => "Invalid operation name: $operation"
                ]);
        $input = \CodeRage\Util\BracketObjectNotation::decode($encoding);
        return [$operation, $input];
    }

    /**
     * Attempts to extract credentials from the given input, test them, and
     * set the current user ID.
     *
     * @param string $operation The operation name
     * @param mixed $input The operation input, encoded as a string, an array,
     *   or an instance of stdClass
     */
    private function authenticate($operation, $input)
    {
        if ($credentials = $this->extractCredentials($operation, $input)) {
            $session = Session::authenticate((array) $credentials);
            $this->setSession($session);
        }
    }

    /**
     * PHP error handler
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
     */
    private function handleError($errno, $errstr, $errfile, $errline)
    {
        throw new
            Error([
                'status' => 'INTERNAL_ERROR',
                'details' =>
                    \CodeRage\Util\ErrorHandler::errorCategory($errno) .
                    ": $errstr in $errfile on line $errline"
            ]);
    }

    /**
     * Handles calls to unsupported operations
     */
    public function __call($method, $arguments)
    {
        if (!method_exists($this, "_$method")) {
            throw new
                Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'message' =>
                        'Unsupported operation: ' . ltrim($method, '_')
                ]);
        } elseif (empty($arguments)) {
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing web service input'
                ]);
        } elseif (count($arguments) > 1) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Excpected one argument; found ' . count($arguments)
                ]);
        } else {
            $class = str_replace('\\', '.' , get_class($this));
            $idekey = Config::current()->getProperty(self::IDEKEY_CONFIG_VARIABLE);
            $operationExecutor =
                new OperationExecutor(
                    [
                        'class' => $class,
                        'protocol' => 'xml-post',
                        'method' => $method,
                        'idekey' => $idekey
                    ]
                );
            return $operationExecutor->execute($arguments[0]);
        }
    }
}
