<?php

/**
 * Defines the class CodeRage\Test\Operations\Exception
 *
 * File:        CodeRage/Test/Operations/Exception.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Error;
use CodeRage\Xml;


/**
 * Represents an exception thrown by an operation
 */
final class Exception extends Base {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Exception
     *
     * @param string $class The class name
     * @param string $status The status code, if any
     * @param string $message The error message
     * @param string $details The detailed error message, if any
     */
    public function __construct($class, $status, $message, $details)
    {
        if ( ($status !== null || $details !== null) &&
             $class !== 'CodeRage\Error' &&
             !is_subclass_of($class, 'CodeRage\Error') )
        {
            $property = $status !== null ?
                'status' :
                'details';
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        "Exception $property may be specified only for " .
                        "CodeRage\Error and derived exception classes"
                ]);
        }
        $this->class = $class;
        $this->status = $status;
        $this->message = $message;
        $this->details = $details;
    }

    /**
     * Returns the class name
     *
     * @return string
     */
    public function _class()
    {
        return $this->class;
    }

    /**
     * Returns the status code, if any
     *
     * @return string
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Returns the error message
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Returns the detailed error message, if any
     *
     * @return string
     */
    public function details()
    {
        return $this->details;
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\Exception newly
     * constructed from the given "exception" element
     *
     * @param DOMElement $elt An element with localName "exception"
     *   conforming to the schema "operation.xsd"
     * @return CodeRage\Test\Operations\Exception
     */
    public static function load(\DOMElement $elt)
    {
        $class = $status = $message = $details = null;
        foreach (Xml::childElements($elt) as $k) {
            switch ($k->localName) {
                case 'class':
                    $class = Xml::textContent($k);
                    break;
                case 'status':
                    $status = Xml::textContent($k);
                    break;
                case 'message':
                    $message = Xml::textContent($k);
                    break;
                case 'details':
                    $details = Xml::textContent($k);
                    break;
                default:
                    break;
            }
        }
        if ($class === null)
            $class = $status !== null ?
                'CodeRage\Error' :
                'Exception';
        return new self($class, $status, $message, $details);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'exception');
        foreach (['class', 'status', 'message', 'details'] as $name) {
            $value = $this->$name();
            if ($value !== null) {
                $child =
                    $elt->appendChild(
                        $dom->createElementNS(self::NAMESPACE_URI, $name)
                    );
                $child->appendChild($dom->createTextNode($value));
            }
        }
        return $elt;
    }

    public function __call($method, $arguments)
    {
        if ($method == 'class') {
            return call_user_func_array([$this, '_class'], $arguments);
        } else {
            throw new
                Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'details' => "No such method: $method"
                ]);
        }
    }

    /**
     * The class name
     *
     * @var string
     */
    private $class;

    /**
     * The status code, if any
     *
     * @var string
     */
    private $status;

    /**
     * The error message
     *
     * @var string
     */
    private $message;

    /**
     * The error message, if any
     *
     * @var string
     */
    private $details;
}
