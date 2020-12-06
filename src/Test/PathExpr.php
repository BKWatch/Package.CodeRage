<?php

/**
 * Defines the class CodeRage\Test\PathExpr
 * 
 * File:        CodeRage/Test/PathExpr.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use stdClass;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\XmlEncoder;


/**
 * A path expression used to address a component of an operation or operation
 * list.
 */
final class PathExpr {

    /**
     * Regular expression matching a path expressions
     *
     * @var string
     */
    const MATCH_EXPRESSION =
        '#^/?([a-zA-Z0-9]|%[0-9A-F]{2})+(\[(\d+|\*)\])?(/([a-zA-Z0-9]|%[0-9A-F]{2})+(\[(\d+|\*)\])?)*$#i';

    /**
     * Regular expression matching path component
     *
     * @var string
     */
    const MATCH_COMPONENT = '/((?:[_a-zA-Z0-9]|%[0-9A-F]{2})+)(?:\[(\d+|\*)\])?/i';

    /**
     * Constructs an instance of CodeRage\Test\PathExpr
     *
     * @param array $components A list of instances of
     *   CodeRage\Test\PathComponent;
     * @param $isAbsolute true if the expression under construction is absolute
     */
    public function __construct($components, $isAbsolute)
    {
        $this->components = $components;
        $this->isAbsolute = $isAbsolute;
    }

    /**
     * Returns true if this path expression is absolute
     *
     * @return boolean
     */
    public function isAbsolute()
    {
        return $this->isAbsolute;
    }

    /**
     * Returns the underlying list of path components
     *
     * @return array An list of instances of CodeRage\Test\PathComponent
     */
    public function components()
    {
        return $this->components;
    }

    /**
     * Returns the number of components in this path expression
     *
     * @return int
     */
    public function length()
    {
        return count($this->components);
    }

    /**
     * Returns the final component of this path expression
     *
     * @return CodeRage\Test\PathComponent
     */
    public function last()
    {
        if (empty($this->components))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => 'Path expression has no components'
                ]);
        return $this->components[sizeof($this->components) - 1];
    }

    /**
     * Returns a path expression consisting of an initial segment of the
     * components of this expression
     *
     * @param int $length The number of components to include
     * @return CodeRage\Test\PathExpr
     */
    public function prefix($length)
    {
        if ($length > sizeof($this->components))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid prefix length: $length"
                ]);
        return
            new PathExpr(
                    array_slice($this->components, 0, $length),
                    $this->isAbsolute
                );
    }

    /**
     * Returns a relative path expression consisting of a final segment of the
     * components of this expression
     *
     * @param int $length The number of components to include
     * @return CodeRage\Test\PathExpr
     */
    public function suffix($length)
    {
        if ($length > sizeof($this->components))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid suffix length: $length"
                ]);
        return
            new PathExpr(
                    array_slice($this->components, -$length),
                    false
                );
    }

    /**
     * Returns a copy of this path expression concatened with the given path
     * expressions or path expression component
     *
     * @param mixed $other An instance of CodeRage\Test\PathExpr or
     *   CodeRage\Test\PathComponent or the string representation of
     *   a path expression; must not be absolute
     * @return CodeRage\Test\PathExpr
     */
    public function append($other)
    {
        Args::check($other, 'string|CodeRage\Test\PathComponent|CodeRage\Test\PathExpr', 'path expression');
        if (is_string($other)) {
            $other = self::parse($other);
        } elseif ($other instanceof PathComponent) {
            $other = new self([$other], false);
        }
        if ($other->isAbsolute())
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Can't join path expression $this with absolute " .
                        "path expression $other"
                ]);
        return new
            PathExpr(
                array_merge($this->components, $other->components),
                $this->isAbsolute
            );
    }

    /**
     * Returns the result of evaluating this expression with respect to the
     * given native data structure
     *
     * @param mixed $context A native data structure, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param CodeRage\Util\XmlEncoder $encoder An XML encoder, used to
     *   determine the names of list items
     * @param $parentName Theproperty name the parent node of $context, if
     *   known; useful when the top-level component is a list item
     * @return string
     */
    public function evaluate($context, XmlEncoder $xmlEncoder,
        $parentName = null)
    {
        $node = $context;
        foreach ($this->components as $i => $c) {
            $name = $c->name();
            if ($c->isListItem()) {
                if ($c->isWildcard())
                    throw new
                        Error([
                            'status' => 'STATE_ERROR',
                            'details' =>
                                "Can't evaluate a path expression containing " .
                                "wildcards"
                        ]);
                if (!is_array($node)) {
                    $prefix = $this->prefix($i);
                    throw new
                        Error([
                            'status' => 'STATE_ERROR',
                            'details' =>
                                "Expected array at $prefix; found " .
                                getType($node)
                        ]);
                }
                $index = $c->index();
                $length = sizeof($node);
                if ($index >= $length) {
                    $prefix = $this->prefix($i);
                    $expected = $index + 1;
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Expected array of length at least " .
                                "$expected at $prefix; found array of length " .
                                $length
                        ]);
                }
                $item = self::itemName($parentName, $xmlEncoder);
                if ($item != $name) {
                    $prefix = $this->prefix($i + 1);
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Expected item named '$name' at $prefix; " .
                                "found item named '$item'"
                        ]);
                }
                $node = $node[$index];
            } else {
                if (!$node instanceof stdClass) {
                    $prefix = $this->prefix($i);
                    throw new
                        Error([
                            'status' => 'STATE_ERROR',
                            'details' =>
                                "Expected instance of stdClass at $prefix; " .
                                "found " . getType($node)
                        ]);
                }
                if (!isset($node->$name)) {
                    $prefix = $this->prefix($i);
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' => "Missing property '$name' at $prefix"
                        ]);
                }
                $node = $node->$name;
            }
            $parentName = $name;
        }
        return $node;
    }

    /**
     * Returns true if this path expression is less specialized than the given
     * path expression, i.e., is the same as this path expression except
     * possibly containing concrete list item indexes at positions where this
     * path expression contains wildcard list items.
     *
     * @param CodeRage\Test\PathExpr $other The path expression to be
     *   matched
     * @return string
     */
    public function matches($other)
    {
        if ($other->isAbsolute != $this->isAbsolute)
            return false;
        $length = sizeof($this->components);
        if (sizeof($other->components) != $length)
            return false;
        for ($i = 0; $i < $length; ++$i) {
            $lComp = $this->components[$i];
            $rComp = $other->components[$i];
            if ( $lComp->name() != $rComp->name() ||
                 $lComp->isListItem() != $rComp->isListItem() ||
                 $lComp->isListItem() &&
                     !$lComp->isWildcard() &&
                     $lComp->index() !== $rComp->index() )
            {
                return false;
            }
        }
        return true;
    }

    /**
     * Parses the given path expression and returns a newly constructed instance
     * of CodeRage\Test\PathExpr
     *
     * @param string $expression
     * @return CodeRage\Test\PathComponent
     */
    public static function parse($expression)
    {
        if ($expression === '/')
            return new PathExpr([], true);
        if (!preg_match(self::MATCH_EXPRESSION, $expression))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid path expression: $expression"
                ]);
        $isAbsolute = null;
        if ($expression[0] == '/') {
            $isAbsolute = true;
            $expression = substr($expression, 1);
        } else {
            $isAbsolute = false;
        }
        $components = [];
        foreach (explode('/', $expression) as $part) {
            $match = null;
            preg_match(self::MATCH_COMPONENT, $part, $match);
            $isList = isset($match[2]);
            $components[] =
                new PathComponent(
                        urldecode($match[1]),
                        $isList,
                        ( $isList && $match[2] != '*' ?
                            (int) $match[2] :
                            null )
                    );
        }
        return new static($components, $isAbsolute);
    }

    public function __toString()
    {
        return ($this->isAbsolute ? '/' : '') . join('/', $this->components);
    }

    /**
     * Returns the result of escaping the characters in the given string
     * using the encoding schema for URIs for all non-alphanumeric chacters
     *
     * @param string $value
     * @return string
     */
    public static function encode($value)
    {
        return preg_replace_callback(
                    '/[^a-zA-Z0-9]/',
                    function($c) { return '%' . printf('%02x', ord($c)); },
                    $value
               );
    }

    private function itemName($listName, $xmlEncoder)
    {
        $listElements = $xmlEncoder->listElements();
        if (isset($listElements[$listName])) {
            return $listElements[$listName];
        } elseif ($listName == 'operations') {
            return 'operation';
        } elseif ($listName == 'input') {
            return 'arg';
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Missing list item name for '$listName' while " .
                        "evaluating $this"
                ]);
        }
    }

    /**
     * Indicates whether this path expression is absolute
     *
     * @var boolean
     */
    private $isAbsolute;


    /**
     * A list of instances of CodeRage\Test\PathComponent
     *
     * @var array
     */
    private $components;
}
