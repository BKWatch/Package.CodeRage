<?php

/**
 * Defines the class CodeRage\Test\BiTraversal
 * 
 * File:        CodeRage/Test/BiTraversal.php
 * Date:        Tue Jul  9 15:14:14 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use CodeRage\Error;
use CodeRage\Util\XmlEncoder;


/**
 * Perfomes a simultaneous depth-first traveral of two native data structures
 */
final class BiTraversal extends TraversalBase {

    /**
     * constructs a CodeRage\Test\BiTraversal
     *
     * @param mixed $lhs A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param mixed $rhs A native data structure with tree-structure identical
     *   to $lhs
     * @param array $options The options array; supports the following options:
     *     xmlEncoder - An instance of CodeRage\Util\XmlEncoder
     *       (optional)
     *     path - The path to $lhs and $rhs within their containing data
     *       structures, if any (optional)
     *     preOrder - true to visit parent nodes before their children; defaults
     *       to true
     */
    public function __construct($lhs, $rhs, array $options = [])
    {
        parent::__construct($options);
        $this->lhs = $lhs;
        $this->rhs = $rhs;
    }

    /**
     * Traverses the underlying data structures, methods of the given visitor
     * as appropriate
     *
     * @param CodeRage\Test\BiTraversalVisitor $visitor The visitor
     */
    public function traverse(BiTraversalVisitor $visitor)
    {
        $this->traverseImpl($visitor, $this->lhs, $this->rhs, $this->path);
    }

    /**
     * Helper to traverse()
     *
     * @param callable $callable
     * @param mixed $lhs
     * @param mixed $rhs
     * @param CodeRage\Test\PathExpr $path
     * @throws CodeRage\Error if $lhs and $rhs are discovered to have diffrerent
     *   tree structures
     */
    private function traverseImpl(BiTraversalVisitor $visitor, $lhs, $rhs,
        PathExpr $path)
    {
        // Determine node types
        $lType = self::getType($lhs, $rhs);
        $rType = self::getType($rhs, true);

        // Handle type mismatch
        if ($lType != $rType) {
            $visitor->typeMismatch($lhs, $rhs, $lType, $rType, $path);
            return;
        }

        // Handle length mismatch
        if ($lType == 'list' && count($lhs) !== count($rhs)) {
            $visitor->lengthMismatch($lhs, $rhs, $path);
            return;
        }

        // Handle properties mismatch
        if ($lType == 'object') {
            $isArray = is_array($rhs);
            foreach ($lhs as $n => &$v) {
                if ( $isArray && !array_key_exists($n, $rhs) ||
                     !$isArray && !property_exists($rhs, $n) )
                {
                    $visitor->propertiesMismatch($lhs, $rhs, $n, true, $path);
                }
            }
            $isArray = is_array($lhs);
            foreach ($rhs as $n => &$v) {
                if ( $isArray && !array_key_exists($n, $lhs) ||
                     !$isArray && !property_exists($lhs, $n) )
                {
                    $visitor->propertiesMismatch($lhs, $rhs, $n, false, $path);
                }
            }
        }

        if ($this->preorder)
            $visitor->visit($lhs, $rhs, $path);

        // Visit children
        if ($lType == 'list') {
            $itemName = self::itemName($path);
            for ($i = 0, $n = count($lhs); $i < $n; ++$i) {
                $comp = new PathComponent($itemName, true, $i);
                $this->traverseImpl(
                    $visitor,
                    $lhs[$i],
                    $rhs[$i],
                    $path->append($comp)
                );
            }
        } elseif ($lType == 'object') {
            $isArray = is_array($rhs);
            foreach ($lhs as $n => &$v) {
                $comp = new PathComponent($n);
                $p = $path->append($comp);
                if ($isArray) {
                    $this->traverseImpl($visitor, $v, $rhs[$n], $p);
                } else {
                    $this->traverseImpl($visitor, $v, $rhs->$n, $p);
                }
            }
        }

        if (!$this->preorder)
            $visitor->visit($lhs, $rhs, $path);
    }

    /**
     * @var mixed
     */
    private $lhs;

    /**
     * @var mixed
     */
    private $rhs;
}
