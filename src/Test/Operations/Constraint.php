<?php

/**
 * Defines the class CodeRage\Test\Operations\Constraint
 *
 * File:        CodeRage/Test/Operations/Constraint.php
 * Date:        Mon Sep  7 19:30:28 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Error;
use CodeRage\Test\Operations\Constraint\List_;
use CodeRage\Test\Operations\Constraint\Object_;
use CodeRage\Test\Operations\Constraint\Scalar;
use CodeRage\Test\PathExpr;
use CodeRage\Util\XmlEncoder;

/**
 * Represents a regular expression together with a path expression. Patterns
 * are used to specify that the certain components of an operation's input,
 * output or exception don't need to exactly match the exepcted input, output or
 * exception.
 */
abstract class Constraint extends Base {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Constraint
     *
     * @param CodeRage\Test\Operations\PathExpr $address The path expression
     *   restricting the values to which the pattern applies
     * @param string $type One of "scalar", "list", or "object"
     */
    public function __construct(PathExpr $address, string $type)
    {
        $this->address = $address;
        $this->type = $type;
    }

    /**
     * Returns the type of this constraint
     *
     * @return string One of "scalar", "list", or "object"
     */
    public function type() : string
    {
        return $this->type;
    }

    /**
     * Returns the path expression restricting the values to which pattern
     * applies
     *
     * @return CodeRage\Test\Operations\PathExpr
     */
    public function address() : PathExpr
    {
        return $this->address;
    }

    /**
     * Replaces the given data structure with a value suitable for storing
     *
     * @param mixed $data A native data structure of a type compatible with
     *   this constraint
     */
    public abstract function replace(&$data) : void;


    /**
     * Returns true if the given value satisfied this constraint
     *
     * @param mixed $data A value of type compatible with this constraint
     */
    public abstract function matches($data) : bool;

    /**
     * Returns an instance of CodeRage\Test\Operations\Constraint newly
     * constructed from the given "pattern", "list", or "object" element
     *
     * @param DOMElement $elt An element with localName "pattern", "list", or
     *  "object"
     * @param CodeRage\Util\XmlEncoder $encoder The XML encoder
     * @param CodeRage\Test\Operations\PathExpr $prefix The path expression
     *   to prepend to the value of the "address" attribute
     * @return CodeRage\Test\Operations\Constraint
     */
    public static function load(\DOMElement $elt, XmlEncoder $encoder,
        PathExpr $prefix) : Constraint
    {
        switch ($elt->localName) {
        case 'pattern':
            return Scalar::load($elt, $encoder, $prefix);
        case 'list':
            return List_::load($elt, $encoder, $prefix);
        case 'object':
            return Object_::load($elt, $encoder, $prefix);
        default:
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' =>
                        "Unsupported constraint element: $elt->localName"
                ]);
        }
    }

    /**
     * One of "scalar", "list", or "object"
     *
     * @var string
     */
    private $type;

    /**
     * The path expression, restricting the values to which pattern applies
     *
     * @var CodeRage\Test\Operations\PathExpr
     */
    private $address;
}
