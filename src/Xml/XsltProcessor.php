<?php

/**
 * Defines the class XsltProcessor
 *
 * File:        CodeRage/Xml/XsltProcessor.php
 * Date:        Tue Aug 2 18:37:06 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Xml;

use DOMDocument;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\ErrorHandler;
use CodeRage\Xml;


/**
 * Wrapper for the class Saxon\XsltProcessor from the Saxon C PHP extension or
 * XSLTProcessor from the standard XSL extension
 */
final class XsltProcessor {

    /**
     * Constructs an instance of CodeRage\Xml\XsltProcessor
     */
    public function __construct()
    {
        if (self::$saxonEnabled === null)
            self::enableSaxon(class_exists('Saxon\\XsltProcessor'));
        if (self::$libxsltEnabled === null)
            self::enableLibxslt(class_exists('XSLTProcessor'));
        if (self::$errorHandler === null)
            self::$errorHandler = new ErrorHandler;
        if (self::$saxonProcessor === null)
            self::$saxonProcessor = new \Saxon\SaxonProcessor();
        $this->library = null;
        $this->impl = null;
    }

    /**
     * Registers one or more PHP functions
     *
     * @param mixed $func
     */
    public function registerPHPFunctions($func)
    {
        $this->requireLibxslt('registering PHP functions');
        $this->impl()->registerPHPFunctions($func);
    }

    /**
     * Sets the given XSLT parameter
     *
     * @param string $namespace The parameter namespace URI
     * @param string $name The parameter name
     * @param string $value The parameter value
     */
    public function setParameter($name, $value, $namespace = null)
    {
        if ($namespace !== null)
            $this->requireLibxslt('parameters with namespace URIs');
        if ($this->impl !== null)
            $this->setParameterImpl($namespace, $name, $value);
        $this->parameters["$name#$namespace"] = $value;
    }

    /**
     * Clears the XSLT parameter values
     */
    public function clearParameters()
    {
        if ($this->impl !== null) {
            if ($this->library == 'saxon') {
                $this->impl->clearParameters();
            } elseif ($this->library == 'libxslt') {
                $handler = self::$errorHandler;
                foreach ($this->parameters as $param => $value) {
                    $pos = strpos($param, '#');
                    $name = substr($param, 0, $pos);
                    $namespace = $pos < strlen($param) - 1 ?
                        substr($param, $pos + 1) :
                        null;
                    $result =
                        $handler->_removeParameter($this->impl, $namespace, $name);
                    if (!$result || $handler->errno())
                        throw new
                            Error([
                                'status' => 'INTERNAL_ERROR',
                                'details' =>
                                    $handler->formatError(
                                        'Failed removing parameter'
                                    )
                            ]);

                }
            }
        }
        $this->parameters = [];
    }

    /**
     * Loads an XSLT transformation from the specified file
     *
     * @param string $stylesheet The file path
     */
    public function loadStylesheetFromFile($stylesheet)
    {
        File::checkReadable($stylesheet);
        $param = ['file', $stylesheet];
        if ($this->impl !== null) {
            $this->loadStylesheetImpl($param);
        } else {
            $this->currentStylesheet = $param;
        }
    }

    /**
     * Loads the given XSLT transformation, represented as a string containing
     * XML data
     *
     * @param string $stylesheet The XML data
     */
    public function loadStylesheetFromString($stylesheet)
    {
        $param = ['string', $stylesheet];
        if ($this->impl !== null) {
            $this->loadStylesheetImpl($param);
        } else {
            $this->currentStylesheet = $param;
        }
    }

    /**
     * Loads the given XSLT transformation, represented as an instance of
     * DOMDocument. If the Saxon C processor is in use, the given DOMDocument
     * will be serialized and re-parsed.
     *
     * @param DOMDocument $stylesheet The parsed stylesheet
     */
    public function loadStylesheetFromDoc(DOMDocument $stylesheet)
    {
        $param = ['document', $stylesheet];
        if ($this->impl !== null) {
            $this->loadStylesheetImpl($param);
        } else {
            $this->currentStylesheet = $param;
        }
    }

    /**
     * Loads a source document from the specified file
     *
     * @param string $source The XML data
     */
    public function loadSourceFromFile($source)
    {
        File::checkReadable($source);
        $this->currentSource = ['file', $source];
    }

    /**
     * Loads a source document from the specified file
     *
     * @param string $source The file path
     */
    public function loadSourceFromString($source)
    {
        $this->currentSource = ['string', $source];
    }

    /**
     * Loads a source document, represented as an instance of
     * DOMDocument. If the Saxon C processor is in use, the given DOMDocument
     * will be serialized and re-parsed.
     *
     * @param DOMDocument $source The parsed source document
     */
    public function loadSourceFromDoc(DOMDocument $source)
    {
        $this->currentSource = ['document', $source];
    }

    /**
     * Performs an XSLT transformation
     *
     * @param string $outputFile The output file
     */
    public function transformToFile($outputFile)
    {
        if ($this->currentSource === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' => 'No source document loaded'
                ]);
        if (!file_exists($outputFile))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "No such file: $outputFile"
                ]);
        if (!is_file($outputFile))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The file '$outputFile' is not a plain file"
                ]);
        if (!is_writable($outputFile))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The file '$outputFile' is not writable"
                ]);
        $impl = $this->impl();
        if ($this->library == 'saxon') {
            $this->loadSourceImpl($this->currentSource);
            $this->checkSaxonErrors();
            $result = $this->impl->TransformToString();
            file_put_contents($outputFile, $result);
            $this->checkSaxonErrors();
        } else {
            $handler = self::$errorHandler;
            $source = $this->convertToDoc($this->currentSource);
            $uri = 'file://' . realpath($outputFile);
            $result = $handler->_transformToURI($this->impl, $source, $uri);
            if (!$result || $handler->errno())
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            $handler->formatError(
                                'Failed applying XSLT transformation'
                            )
                    ]);
        }
        if ($this->library == 'saxon')
            $this->impl->clearProperties();
        $this->currentSource = null;
    }

    /**
     * Performs an XSLT transformation
     *
     * @return string The result of the transformation
     */
    public function transformToString()
    {
        if ($this->currentSource === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' => 'No source document loaded'
                ]);
        $impl = $this->impl();
        $result = null;
        if ($this->library == 'saxon') {
            $this->loadSourceImpl($this->currentSource);
            $result = $this->impl->TransformToString();
            $this->checkSaxonErrors();
        } else {
            $handler = self::$errorHandler;
            $source = $this->convertToDoc($this->currentSource);
            $result = $handler->_transformToXML($this->impl, $source);
            if (!$result || $handler->errno())
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            $handler->formatError(
                                'Failed applying XSLT transformation'
                            )
                    ]);
        }
        $this->currentSource = null;
        if ($this->library == 'saxon')
            $this->impl->clearProperties();
        return $result;
    }

    /**
     * Performs an XSLT transformation
     *
     * @return DOMDocument The result of the transformation
     */
    public function transformToDoc()
    {
        if ($this->currentSource === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' => 'No source document loaded'
                ]);
        $impl = $this->impl();
        $result = null;
        if ($this->library == 'saxon') {
            $this->loadSourceImpl($this->currentSource);
            $xml = $this->impl->TransformToString();
            $this->checkSaxonErrors();
            $result = Xml::loadDocumentXml($xml);
        } else {
            $handler = self::$errorHandler;
            $source = $this->convertToDoc($this->currentSource);
            $result = $handler->_transformToDoc($this->impl, $source);
            if (!$result || $handler->errno())
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            $handler->formatError(
                                'Failed applying XSLT transformation'
                            )
                    ]);
        }
        $this->currentSource = null;
        if ($this->library == 'saxon')
            $this->impl->clearProperties();
        return $result;
    }

    /**
     * Enables or disables XSLT processing with Saxon C
     *
     * @param boolean $enabled true to enable Saxon C
     */
    public static function enableSaxon($enabled)
    {
        if (self::$saxonEnabled !== $enabled) {
            if ($enabled && !class_exists('Saxon\\XsltProcessor'))
                throw new
                    Error([
                        'status' => 'UNSUPPORTED_OPERATION',
                        'details' => 'The Saxon C extension is not loaded'
                    ]);
            self::$saxonEnabled = $enabled;
        }
    }

    /**
     * Enables or disables XSLT processing with Libxslt
     *
     * @param boolean $enabled true to enable Libxslt
     */
    public static function enableLibxslt($enabled)
    {
        if (self::$libxsltEnabled !== $enabled) {
            if ($enabled && !class_exists('XSLTProcessor'))
                throw new
                    Error([
                        'status' => 'UNSUPPORTED_OPERATION',
                        'details' => 'The XSL extension is not loaded'
                    ]);
            self::$libxsltEnabled = $enabled;
        }
    }

    /**
     * Loads the given stylesheet
     *
     * @param array $param A pair [$type, $stylesheet]
     */
    private function loadStylesheetImpl($param)
    {
        list($type, $stylesheet) = $param;
        if ($this->library == 'saxon') {
            switch ($type) {
            case 'file':
                $this->impl->compileFromFile($stylesheet);
                break;
            case 'string':
                $this->impl->compileFromString($stylesheet);
                break;
            case 'document':
            default:
                $this->impl->compileFromString($stylesheet->saveXml());
            }
            $this->checkSaxonErrors();
        } else {
            $handler = self::$errorHandler;
            $result = $handler->_importStylesheet(
                $this->impl,
                $this->convertToDoc($param)
            );
            if (!$result || $handler->errno())
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            $handler->formatError('Failed parsing stylesheet')
                    ]);
        }
    }

    /**
     * Loads the given source (saxon C only)
     *
     * @param array $param A pair [$type, $source]
     */
    private function loadSourceImpl($param)
    {
        list($type, $source) = $param;
        switch ($type) {
        case 'file':
            $this->impl->setSourceFromFile($source);
            break;
        case 'string':
            $file = File::temp();
            file_put_contents($file, $source);
            $this->impl->setSourceFromFile($file);
            break;
        case 'document':
        default:
            $file = File::temp();
            $source->save($file);
            $this->impl->setSourceFromFile($file);
        }
        $this->checkSaxonErrors();
    }

    /**
     * Sets the given XSLT parameter
     *
     * @param string $namespace The parameter namespace URI
     * @param string $name The parameter name
     * @param string $value The parameter value
     */
    private function setParameterImpl($namespace, $name, $value)
    {
        if ($this->library == 'saxon') {
            $value = self::$saxonProcessor->createAtomicValue(strval($value));
            $this->impl->setParameter($name, $value);
            $this->checkSaxonErrors();
        } else {
            $handler = self::$errorHandler;
            $result =
                $handler->_setParameter($this->impl, $namespace, $name, $value);
            if (!$result || $handler->errno())
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            $handler->formatError('Failed setting parameter')
                    ]);
        }
    }

    /**
     * Returns a DOMDocument based on the given A pair [$type, $doc] pair,
     * where $type is 'file', 'string', or 'document' and $doc is a file path,
     * a string containing XML data, or an instance of DOMDocument
     *
     * @param array $param A pair [$type, $doc]
     */
    private function convertToDoc($param)
    {
        list($type, $doc) = $param;
        switch ($type) {
        case 'file':
            return Xml::loadDocument($doc);
        case 'string':
            return Xml::loadDocumentXml($doc);
        case 'document':
        default:
            return $doc;
        }
    }

    /**
     * Sets processor and parameters and return the processor. Returns processor
     * if it is not null
     *
     * @return XSLTProcessor|Saxon\XsltProcessor
     * @throws Error
     */
    private function impl()
    {
        if ($this->impl === null) {
            if ($this->library === null) {
                $this->library = self::$saxonEnabled ?
                    'saxon' :
                    ( self::$libxsltEnabled ?
                          'libxslt' :
                          null );
                if ($this->library === null)
                    throw new
                        Error([
                            'status' => 'UNSUPPORTED_OPERATION',
                            'details' =>
                                'No XSLT processing library is available'
                        ]);
            }
            $this->impl = $this->library == 'saxon' ?
                self::$saxonProcessor->newXsltProcessor() :
                new \XSLTProcessor;
            if (!empty($this->parameters)) {
                foreach ($this->parameters as $param => $value) {
                    $pos = strpos($param, '#');
                    $name = substr($param, 0, $pos);
                    $namespace = $pos < strlen($param) - 1 ?
                        substr($param, $pos + 1) :
                        null;
                    $this->setParameterImpl($namespace, $name, $value);
                }
            }
            if ($this->currentStylesheet !== null)
                $this->loadStylesheetImpl($this->currentStylesheet);
            return $this->impl;
        }
        return $this->impl;
    }

    /**
     * Checks if feature requires saxon processor; sets the library to 'saxon'
     * processor if it is enabled else throw the 'UNSUPPORTED_OPERATION'
     * exception
     *
     * @param string $feature
     * @throws Error
     */
    private function requireSaxon($feature)
    {
        if ($this->library == 'libxslt')
            $this->unsupportedFeature($feature);
        if (!self::$saxonEnabled) {
            $reason = class_exists('Saxon\\XsltProcessor') ?
                'Saxon C processing is disabled' :
                'the Saxon C extension is not loaded';
            throw new
                Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'details' => "Only Saxon C supports $feature, but $reason"
                ]);
        }
        $this->library = 'saxon';
    }

    /**
     * Checks if feature requires libxslt processor; sets the library to
     * 'libxslt' processor if it is enabled else throw the
     * 'UNSUPPORTED_OPERATION' exception
     *
     * @param string $feature
     * @throws Error
     */
    private function requireLibxslt($feature)
    {
        if ($this->library == 'saxon')
            $this->unsupportedFeature($feature);
        if (!self::$libxsltEnabled) {
            $reason = class_exists('XSLTProcessor') ?
                'Libxslt processing is disabled' :
                'the XSL extension is not loaded';
            throw new
                Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'details' => "Only Libxslt supports $feature, but $reason"
                ]);
        }
        $this->library = 'libxslt';
    }

    /**
     * Throws 'UNSUPPORTED_OPERATION' exception for the feature not supported
     * by processor
     *
     * @param string $feature
     * @throws Error
     */
    private function unsupportedFeature($feature)
    {
        $library = $this->library == 'saxon' ?
            'Saxon C' :
            'Libxslt';
        throw new
            Error([
                'status' => 'UNSUPPORTED_OPERATION',
                'details' =>
                    "The $library XSLT processor does not support $feature"
            ]);
    }

    /**
     * Check for Saxon erros
     *
     * @throws Error
     */
    private function checkSaxonErrors()
    {
        $n = $this->impl->getExceptionCount();
        switch ($n) {
        case 0:
            break;
        case 1:
            $message = $this->impl->getErrorMessage(0);
            $this->impl->exceptionClear();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' => $message
                ]);
        default:
            $message = "The following Saxon C errors occurred:";
            for ($i = 0; $i < $n; ++$i)
                $message .= "\n" . $this->impl->getErrorMessage($i);
            $this->impl->exceptionClear();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' => $message
                ]);
        }
    }

    /**
     * @var boolean
     */
    private static $saxonEnabled;

    /**
     * @var boolean
     */
    private static $libxsltEnabled;

    /**
     * @var CodeRAge\Util\ErrorHandler
     */
    private static $errorHandler;

    /**
     * @var Saxon\SaxonProcessor
     */
    private static $saxonProcessor;

    /**
     * One of null, 'saxon', or 'libxslt'
     *
     * @var string
     */
    private $library;

    /**
     * An array [$type, $stylesheet] where $type is one of 'file', 'string', or
     * 'document'
     *
     * @var array
     */
    private $currentStylesheet;

    /**
     * An array [$type, $source] where $type is one of 'file' or 'document'
     *
     * @var array
     */
    private $currentSource;

    /**
     * The collection of XSLT parameters, indexed by the concatenation of the
     * name and namespace separated by a '#'
     *
     * @var array
     */
    private $parameters = [];

    /**
     * An instance of XSLTProcessor or Saxon\XsltProcessor
     *
     * @var mixed
     */
    private $impl;
}
