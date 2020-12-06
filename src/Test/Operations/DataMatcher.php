<?php

/**
 * Defines the class CodeRage\Test\Operations\DataMatcher
 * 
 * File:        CodeRage/Test/Operations/DataMatcher.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use stdClass;
use CodeRage\Error;
use CodeRage\Test\BiTraversal;
use CodeRage\Test\Diff;
use CodeRage\Test\PathExpr;
use CodeRage\Test\Traversal;
use CodeRage\Util\XmlEncoder;
use CodeRage\Xml;


/**
 * Compares and normalizes data structures using regular-expression based
 * simlarity conditions
 */
final class DataMatcher extends Base {

    /**
     * Constructs an instance of CodeRage\Test\Operations\DataMatcher
     *
     * @param CodeRage\Util\XmlEncoder $xmlEncoder An XML encoder
     * @param string $constraints A list of instances of
     *   CodeRage\Test\Operations\Constraint
     */
    public function __construct($xmlEncoder, array $constraints = [])
    {
        $this->listElements = $xmlEncoder->listElements();
        $this->constraints = $constraints;
        foreach ($constraints as $c)
            $this->byType[$c->type()][] = $c;
    }

    /**
     * Returns the underlying list of instances of
     * CodeRage\Test\Operations\Constraint, optionally filtered by type
     *
     * @param string $type One of "scalar", "list", or "obejct"
     * @return array
     */
    public function constraints(?string $type = null)
    {
        return $type !== null ?
            $this->byType[$type] ?? [] :
            $this->constraints;
    }

    /**
     * Throws an exception if the first native data structure does not match the
     * second. Matching is subject to the underlying list of constraints.
     *
     * @param mixed $actual A native data structure, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param mixed $expected A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param string $message A string for use in error messages
     * @param CodeRage\Test\PathExpr $path The path to $actual and $expected
     *   within their containing data structures; defaults to '/'
     * @throws CodeRage\Error if $actual does not match $expected
     */
    public function assertMatch($actual, $expected, $message, $path = null)
    {
        if ($this->assertEncoder === null)
            $this->assertEncoder =
                new XmlEncoder([
                        'listElements' => $this->listElements + ['*' => 'item']
                    ]);
        if ($path === null)
            $path = PathExpr::parse('/');
        try {
            $traversal =
                new BiTraversal($actual, $expected, [
                        'xmlEncoder' => $this->assertEncoder,
                        'path' => $path
                    ]);
            $visitor = new DataMatcherVisitor($this, $message);
            $traversal->traverse($visitor);
        } catch (Error $error) {
            if ($error->status() == 'ASSERTION_FAILED') {
                try {

                    // Try to format a user-friendly message
                    $this->normalize($actual, $path);
                    $diff =
                        new Diff($expected, $actual, [
                                'xmlEncoder' => $this->assertEncoder,
                                'localName' => 'operation'
                            ]);
                    $format = $diff->format();
                    $error =
                        new Error([
                                'status' => 'ASSERTION_FAILED',
                                'details' =>
                                    $error->details() . " (diff:\n$format)"
                            ]);
                } catch (\Throwable $e2) {
                    $error =
                        new Error([
                                'status' => 'ASSERTION_FAILED',
                                'message' => $error->message(),
                                'details' =>
                                    $error->details() . ' (failed generating ' .
                                    'diff)'
                            ]);
                }
            }
            throw $error;
        }
    }

    /**
     * Normalizes the given operation output, replacing embedded values as
     * with the replacements specified by matching constraints
     *
     * @param mixed $data A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param CodeRage\Test\PathExpr $path The path to $data within its
     *   containing data structures; defaults to '/'
     * @throws CodeRage\Error
     */
    public function normalize(&$data, $path = null)
    {
        if ($this->normalizeEncoder === null)
            $this->normalizeEncoder =
                new XmlEncoder([
                        'listElements' =>
                            $this->listElements +
                                [ 'operations' => 'operation',
                                  'input' => 'arg' ]
                    ]);
        if ($path === null)
            $path = PathExpr::parse('/');
        $traversal =
            new Traversal($data, [
                    'xmlEncoder' => $this->normalizeEncoder,
                    'path' => $path
                ]);
        $traversal->traverse(function(&$data, $type, $path) {
            foreach ($this->constraints($type) as $c) {
                if ($c->address()->matches($path) && $c->matches($data))
                    $c->replace($data);
            }
        });
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\DataMatcher newly
     * constructed from the given "dataMatching" element
     *
     * @param DOMElement $elt An element with localName "dataMatching"
     *   conforming to the schema "operation.xsd"
     * @param CodeRage\Util\XmlEncoder $encoder The XML encoder
     * @param int $index The index of the operation under construction within
     *   its parent's list of child operations, if any
     * @return CodeRage\Test\Operations\DataMatcher
     */
    public static function load(\DOMElement $elt, XmlEncoder $encoder, $index)
    {
        $prefix =
            PathExpr::parse(
                $index !== null ?
                    "/operation[$index]" :
                    "/"
            );
        $patterns = [];
        foreach (Xml::childElements($elt) as $k)
            $patterns[] = Constraint::load($k, $encoder, $prefix);
        return new self($encoder, $patterns);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'dataMatching');
        foreach ($this->constraints() as $c)
            $elt->appendChild($c->save($dom, $parent));
        return $elt;
    }

    /**
     * Returns the associated XML encoder, constructing it if necessary
     *
     * @return CodeRage\Util\XmlEncoder
     */
    private function xmlEncoder()
    {
        if ($this->xmlEncoder === null)
            $this->xmlEncoder =
                new XmlEncoder(null, $this->listElements + ['*' => 'item']);
        return $this->xmlEncoder;
    }

    /**
     * Returns the same result as the built-in function gettype(), except that
     * the type is reported as 'scalar' for scalars and null values
     *
     * @param mixed $value
     */
    private static function getType($value)
    {
        return is_scalar($value) || is_null($value) ?
            'scalar' :
            \gettype($value);
    }

    /**
     * XML encoder for assertMatch()
     *
     * @var CodeRage\Util\XmlEncoder
     */
    private $assertEncoder;

    /**
     * XML encoder for normalize()
     *
     * @var CodeRage\Util\XmlEncoder
     */
    private $normalizeEncoder;

    /**
     * An associative array mapping local names of XML elements that represent
     * lists of items to the local names of the corresponding child elements
     *
     * @var array
     */
    private $listElements;

    /**
     * A list of instance of CodeRage\Test\Operations\Constraint
     *
     * @var array
     */
    private $constraints;

    /**
     * The collection of constraints indexed by type
     *
     * @var array
     */
    private $byType = [];
}
