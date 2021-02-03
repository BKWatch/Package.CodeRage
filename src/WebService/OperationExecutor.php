<?php

/**
 * Defines the class CodeRage\WebService\OperationExecutor
 *
 * File:        CodeRage/WebService/OperationExecutor.php
 * Date:        Sun Oct 11 19:01:10 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use CodeRage\Build\Config\Builtin as BuiltinConfig;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Test\Operations\Operation;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\BracketObjectNotation;
use CodeRage\Util\ConfigToken;
use CodeRage\Util\Factory;
use CodeRage\Xml;
use CodeRage\Xml\XsltProcessor;


/**
 * Invokes function or the method of an object; if the object is a webservice,
 * the method is treated as an operation name and the operation is invoked
 * using a specified protocol
 */
class OperationExecutor {

    /**
     * @var string
     */
    const MATCH_DENTIFIER = '/^[_a-z][_a-z0-9]*$/i';

    /**
     * @var string
     */
    const MATCH_PROTOCOL =
        '/^(soap|xml-post|xml-get|json-post|json-get)$/';

    /**
     * @var string
     */
    const GENERATE_PROTOCOL = 'test';

    /**
     * @var string
     */
    const XSL_PATH = __DIR__ . '/transform-operations.xsl';

    /**
     * @var string
     */
    const MATCH_MODE = \CodeRage\Test\Operations\Case_::MATCH_MODE;

    /**
     * @var string
     */
    const CONFIG_SESSION_LIFETIME = 300;

    /**
     * @var int
     */
    const SOAP_TIMEOUT = 3600;

    /**
     * Constructs a CodeRage\WebService\Test\OperationExecutor
     *
     * @param array $options The options array; supports the following options:
     *   class - A class name, specified as a sequence of identifiers separated
     *     by dots (required)
     *   params.XXX - A constructor parameter (optional)
     *   method - The function or method name
     *   protocol - One of 'soap', 'xml-post', 'xml-post', 'json-post', or
     *     'json-get'
     *   idekey - The IDE key for remote debugging (optional)
     * @throws CodeRage\Error if the component class cannot be located.
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'class', 'string');
        Args::checkKey($options, 'protocol', 'string');
        Args::checkKey($options, 'idekey', 'string', 'IDE key');
        Args::checkKey($options, 'method', 'string', 'method name', true);
        $params = [];
        foreach ($options as $n => $v)
            if (strncmp($n, 'param.', 6) == 0)
                $params[substr($n, 6)] = $v;
        if ( !isset($options['class']) &&
             (isset($options['classPath']) || !empty($params)) )
        {
            throw new
                Error([
                   'status' => 'INCONSISTENT_PARAMETERS',
                   'message' =>
                        'Class path and constructor parameters cannot be ' .
                        'provided without a class'
                ]);
        }
        if (!preg_match(self::MATCH_DENTIFIER, $options['method']))
            throw new
                Error([
                   'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid method name: {$options['method']}"
                ]);
        if (!preg_match(self::MATCH_PROTOCOL, $options['protocol']))
            throw new
                Error([
                   'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid protocol: {$options['protocol']}"
                ]);
        $this->method = $options['method'];
        if (isset($options['class'])) {
            $this->class = str_replace('.', '\\', $options['class']);
            if ( !Factory::methodExists($this->class, $this->method) ||
                 !(new \ReflectionMethod($this->class, $this->method))->isStatic() )
            {
                // Visible hard-coded non-static methods and methods implemented
                // using __call() must be invoked using a class instance;
                // visible hard-coded static methods are invoked using
                // callables of the form [$class, $method]; static methods
                // implemented with __callStatic() are not supported
                $this->instance =
                    Factory::create([
                        'class' => $options['class'],
                        'params' => $options['params'] ?? []
                    ]);
                if ($this->instance instanceof \CodeRage\Webservice\Service) {
                    if (!isset($options['protocol']))
                        throw new
                            Error([
                                    'status' => 'MISSING_PARAMETER',
                                    'message' => 'Missing protocol'
                            ]);
                    $this->instance->shareLogSession(false);
                    $this->protocol = $options['protocol'];
                    if (isset($options['idekey']))
                        $this->idekey = $options['idekey'];
                    $this->wsdl = new WsdlParser($this->instance->wsdl());
                }
            }
        }
    }

    /**
     * Executes the specified webservice operation with the given input and
     * returns the result
     *
     * @param mixed $input The operation input, represented as one or more
     *   native data structures, i.e., as values composed from scalars using
     *   arrays and instances of stdClass
     * @return mixed The webservice result as a native data structure, i.e., a
     *   value composed from scalars using indexed arrays and instances of
     *   stdClass
     * @throws Exception if an error occurs
     */
    public function execute(...$input)
    {
        if ($this->instance === null) {
            $func = $this->class === null ?
                $this->method :
                [$this->class,  $this->method];
            return $func(...$input);
        }
        if (!$this->instance instanceof \CodeRage\WebService\Service) {
            $method = $this->method;
            return $this->instance->$method(...$input);
        }
        switch ($this->protocol) {
        case 'soap':
            return $this->executeSoap(...$input);
        case 'xml-post':
            return $this->executeXmlPost(...$input);
        case 'xml-get':
            return $this->executeXmlGet(...$input);
        case 'json-post':
            return $this->executeJsonPost(...$input);
        case 'json-get':
            return $this->executeJsonGet(...$input);
        default:
            return false; // Can't occur
        }
    }

    /**
     * Loads an operation or operation list from the specified path,
     * transforming it to use instances of
     * CodeRage\WebService\OperationExecutor
     *
     * @param array $options The options array; supports the following options:
     *   path - The path to an XML operation or operation list description
     *   protocol - One of "soap", "xml-post", "xml-post", "json-post", or
     *     "json-get"; may only be used if the value of the option
     *     "mode" is "test" (optional)
     *   idekey - The IDE key for remote debugging (optional)
     *   includeXpath - An XPath expression or list of XPath expressions
     *     evaluating to 0 or 1 when applied to the parsed XML document; if
     *     any expression evaluates to 0, the return value will be null;
     *     the prefix "x" can be used to reference the operations namespace
     *     (optional)
     *   excludeXpath - An XPath expression or list of XPath expressions
     *     evaluating evaluating to 0 or 1 when applied to the parsed XML
     *     document; if any expression evaluates to 1, the return value will
     *     be null; the prefix "x" can be used to reference the operations
     *     namespace (optional)
     *   includePattern - A regular expression or list of regular expressions
     *     to be matched against the path of the XML document; if any
     *     expression fails to match, the return value will be null
     *     (optional)
     *   excludePattern - A regular expression or list of regular expressions
     *     to be matched against the path of the XML document; if any
     *     expression matches, the return value will be null (optional)
     *   mode - One of "test", "generate", or "list"
     * @throws CodeRage\Error if the component class cannot be located.
     */
    public static function loadOperation(array $options)
    {
        Args::checkKey($options, 'path', 'string', null, true);
        Args::checkKey($options, 'protocol', 'string');
        Args::checkKey($options, 'idekey', 'string', 'IDE key');
        Args::checkKey($options, 'mode', 'string', 'mode', true);
        $mode = $options['mode'];
        try {
            if (!preg_match(self::MATCH_MODE, $options['mode']))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid mode: {$mode}"
                    ]);
            if (isset($options['protocol'])) {
                if (!preg_match(self::MATCH_PROTOCOL, $options['protocol']))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => "Invalid protocol: " . $options['protocol']
                        ]);
                if ($mode !== 'test')
                    throw new
                        Error([
                           'status' => 'INCONSISTENT_PARAMETERS',
                            'message' =>
                                "The option 'protocol' is incompatible with mode " .
                                "'$mode'"
                        ]);
            }
            $temp = null;
            if ($mode == 'test') {
                $operationDom = Xml::loadDocument($options['path']);
                $operationDom->xinclude();
                $proc = new XsltProcessor;
                $proc->loadStylesheetFromFile(self::XSL_PATH);
                $proc->setParameter('protocol', $options['protocol'], '');
                if (isset($options['idekey']))
                    $proc->setParameter('idekey', $options['idekey'], '');
                $proc->loadSourceFromDoc($operationDom);
                $operationDom = $proc->transformToDoc();

                // Create a temporary file whose pathname has a trailing segment
                // matching a trailing segment of the pathname of the original file,
                // allowing the patterns specified by 'includePattern' and
                // 'excludePattern' to access as much of the original file pathname
                // as possible; we have to be careful not to allow dots in the
                // original pathname to cause us to create a file outside of the
                // temporary directory
                $origParts = explode('/', ltrim($options['path'], '/'));
                $tempParts = [];
                foreach (array_reverse($origParts) as $p) {
                    if ($p == '..')
                        break;
                    array_unshift($tempParts, $p);
                }
                $temp = File::tempDir() . '/' . join('/', $tempParts);
                File::mkdir(dirname($temp));
                $operationDom->save($temp);
            }
            $operation =
                Operation::load(
                    $temp !== null ? $temp : $options['path'],
                    [
                        'baseUri' => $options['path'],
                        'includeXpath' =>
                            isset($options['includeXpath']) ?
                                $options['includeXpath'] :
                                null,
                        'excludeXpath' =>
                            isset($options['excludeXpath']) ?
                                $options['excludeXpath'] :
                                null,
                        'includePattern' =>
                            isset($options['includePattern']) ?
                                $options['includePattern'] :
                                null,
                        'excludePattern' =>
                            isset($options['excludePattern']) ?
                                $options['excludePattern'] :
                                null
                    ]
                );
            if ($operation !== null)
                $operation->setPath($options['path']);
            return $operation;
        } catch (\Throwable $e) {
            $inner = Error::wrap($e);
            throw new
                Error([
                    'details' => "Failed loading operation {$options['path']}",
                    'inner' => $e
                ]);
        }
    }

    /**
     * Executes the underlying operation using the SOAP protocol
     *
     * @param mixed $input The operation input, represented as a value
     *   composed from scalars using arrays and instances of stdClass
     * @return mixed The webservice result as a native data structure, i.e., a
     *   value composed from scalars using indexed arrays and instances of
     *   stdClass
     * @throws Exception if an error occurs
     */
    private function executeSoap($input)
    {
        $timeout = null;
        try {
            $timeout = ini_set('default_socket_timeout', self::SOAP_TIMEOUT);
            if ($timeout === false)
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' => 'Failed setting default socket timeout'
                    ]);
            $client =
                new \SoapClient(
                        $this->wsdl->path(),
                        [
                            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
                            'cache_wsdl' => WSDL_CACHE_NONE
                        ]
                    );
            $client->__setLocation($this->serviceAddress());
            $method = $this->method;
            $response = $client->$method($input);
            return $this->instance
                        ->xmlEncoder($method)
                        ->fixSoapEncoding($response);
        } finally {
            if ($timeout !== null)
                ini_set('default_socket_timeout', $timeout);
        }
    }

    /**
     * Executes the underlying operation using the XML over HTTP POST protocol
     *
     * @param mixed $input The operation input, represented as a value
     *   composed from scalars using arrays and instances of stdClass
     * @return mixed The webservice result as a native data structure, i.e., a
     *   value composed from scalars using indexed arrays and instances of
     *   stdClass
     * @throws Exception if an error occurs
     */
    private function executeXmlPost($input)
    {
        // Construct request XML document
        $xmlEncoder = $this->instance->xmlEncoder($this->method);
        $requestDom = new \DOMDocument;
        $requestElt =
            $xmlEncoder->encode(
                "{$this->method}Request",
                $input,
                $requestDom
            );
        $requestDom->appendChild($requestElt);

        // Submit request
        $uri = $this->serviceAddress([['PROTOCOL','XML']]);
        $request = new HttpRequest($uri, 'POST', $requestDom->saveXml());
        $request->setHeaderField('Content-Type', 'text/xml');
        $response = $request->submit(['throwOnError' => true]);

        // Decode and validate response
        $responseDom = Xml::loadDocumentXml($response->body());
        $responseDom->schemaValidateSource($this->wsdl->schema());
        if ($responseDom->documentElement->localName != "{$this->method}Response")
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' =>
                        "Expected '{$this->method}Response' element; found " .
                        $responseDom->documentElement->localName
                ]);
        return $xmlEncoder->decode($responseDom->documentElement);
    }

    /**
     * Executes the underlying operation using the XML over HTTP GET protocol
     *
     * @param mixed $input The operation input, as an associative array or
     *   instance of stdClass with scalar values
     * @return mixed The webservice result as a native data structure, i.e., a
     *   value composed from scalars using indexed arrays and instances of
     *   stdClass
     * @throws Exception if an error occurs
     */
    private function executeXmlGet($input)
    {
        // Submit request
        $params = BracketObjectNotation::encode($input);
        $params[] = ['PROTOCOL', 'XML'];
        $params[] = ['OPERATION', $this->method];
        $uri = $this->serviceAddress($params);
        $request = new HttpRequest($uri);
        $response = $request->submit(['throwOnError' => true]);

        // Decode and validate response
        $responseDom = Xml::loadDocumentXml($response->body());
        $responseDom->schemaValidateSource($this->wsdl->schema());
        if ($responseDom->documentElement->localName != "{$this->method}Response")
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' =>
                        "Expected '{$this->method}Response' element; found " .
                        $responseDom->documentElement->localName
                ]);
        return $this->instance
                    ->xmlEncoder($this->method)
                    ->decode($responseDom->documentElement);
    }

    /**
     * Executes the underlying operation using the JSON over HTTP POST protocol
     *
     * @param mixed $input The operation input, represented as a value
     *   composed from scalars using arrays and instances of stdClass
     * @return mixed The webservice result as a native data structure, i.e., a
     *   value composed from scalars using indexed arrays and instances of
     *   stdClass
     * @throws Exception if an error occurs
     */
    private function executeJsonPost($input)
    {
        $uri =
            $this->serviceAddress([
                ['PROTOCOL', 'JSON'],
                ['OPERATION', $this->method]
            ]);
        $request = new HttpRequest($uri, 'POST', json_encode($input));
        $request->setHeaderField('Content-Type', 'application/json');
        $response = $request->submit();
        return json_decode($response->body());
    }

    /**
     * Executes the underlying operation using the JSON over HTTP GET protocol
     *
     * @param mixed $input The operation input, as an associative array or
     *   instance of stdClass with scalar values
     * @return mixed The webservice result as a native data structure, i.e., a
     *   value composed from scalars using indexed arrays and instances of
     *   stdClass
     * @throws Exception if an error occurs
     */
    private function executeJsonGet($input)
    {
        $params = BracketObjectNotation::encode($input);
        $params[] = ['PROTOCOL', 'JSON'];
        $params[] = ['OPERATION', $this->method];
        $uri = $this->serviceAddress($params);
        $request = new HttpRequest($uri);
        $response = $request->submit(['throwOnError' => true]);
        return json_decode($response->body());
    }

    /**
     * Returns the service address, patched to incorporated the site domain and
     * the IDE key, if any
     *
     * @param array $params Additional query parameters, if any, as a list of
     *   pairs of the form [name, value]
     */
    private function serviceAddress(array $params = [])
    {
        $query = [];
        foreach ($params as list($n, $v))
            $query[] = rawurlencode($n) . '=' . rawurlencode($v);
        $components = \parse_url($this->wsdl->serviceAddress());
        if (isset($components['query']))
            $query[] = $components['query'];
        $config = Config::current();
        if (!$config instanceof BuiltinConfig)
            $query[] = 'CONFIG=' . ConfigToken::create();
        if ($this->idekey !== null) {
            $query[] = 'XDEBUG_SESSION_START=' . rawurlencode($this->idekey);
            $query[] = 'DEBUG=1';
        }
        if (!empty($query))
            $components['query'] = join('&', $query);
        $config = \CodeRage\Config::current();
        $address =
            $components['scheme'] . '://' .
            $config->getRequiredProperty('site_domain') . $components['path'] .
            ( isset($components['query']) ?
                  '?' . $components['query'] :
                  '' ) .
            ( isset($components['fragment']) ?
                  '#' . $components['fragment'] :
                  '' );
        return $address;
    }

    /**
     * Returns true if the argument is an instance of stdClass or an associative array
     *
     * @param mixed $value
     * @return boolean
     */
    private function isAssociative($value)
    {
        return $value instanceof \stdClass ||
               is_array($value) && !Array_::isIndexed($value);
    }

     /**
      * The name of the class whose method is to be invoked, if the method is a
      * static method
      *
      * @var string
      */
     private $class;

     /**
      * The object whose method is to be invoked, if the method is not a
      * static method
      *
     * @var CodeRage\WebService\Service
     */
    private $instance;

     /**
      * The name of the method to be invoked
      *
      * @var string
      */
     private $method;

    /**
     * One of 'soap', 'xml-post', 'xml-post', 'json-post', or 'json-get'
     *
     * @var string
     */
    private $protocol;

    /**
     * The IDE key, for remote debugging
     *
     * @var string
     */
    private $idekey;

    /**
     * A parser for the webservice WSDL; access only via the method wsdl()
     *
     * @var CodeRage\WebService\WsdlParser
     */
    private $wsdl;
}
