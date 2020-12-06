<?php

/**
 * Defines the interface CodeRage\Test\BiTraversalVisitor
 * 
 * File:        CodeRage/Test/BiTraversalVisitor.php
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
 * Interface for use with CodeRage\Test\BiTraversal::traverse()
 */
interface BiTraversalVisitor {

    /**
     * Called when a pair of nodes is visted
     *
     * @param mixed $lhs A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param mixed $rhs A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param CodeRage\Test\PathExpr $path The path to $lhs and $rhs within
     *   their containing data structures
     */
    public function visit(&$lhs, &$rhs, PathExpr $path);

    /**
     * Called when the two nodes being visited are found to have different types
     *
     * @param mixed $lhs A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param mixed $rhs A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param string $lType The type of $lhs; one of 'scalar', 'list', or
     *   'object'
     * @param string $rType The type of $rhs; one of 'scalar', 'list', or
     *   'object'
     * @param CodeRage\Test\PathExpr $path The path to $lhs and $rhs within
     *   their containing data structures
     */
    public function typeMismatch(&$lhs, &$rhs, $lType, $rType, PathExpr $path);

    /**
     * Called when the two nodes being visited are lists of different lengths
     *
     * @param array $lhs An indexed array
     * @param array $rhs An indexed array
     * @param CodeRage\Test\PathExpr $path The path to $lhs and $rhs within
     *   their containing data structures
     */
    public function lengthMismatch(&$lhs, &$rhs, PathExpr $path);

    /**
     * Called when the two nodes being visited are associative data structures
     * and their collections of keys are found to differ
     *
     * @param mixed $lhs An associative array of instances of stdClass
     * @param array $rhs An associative array of instances of stdClass
     * @param string $key A key that exists in one of the two nodes but not the
     *   other
     * @param boolean $left true if $key exists in $lhs
     * @param CodeRage\Test\PathExpr $path The path to $lhs and $rhs within
     *   their containing data structures
     */
    public function propertiesMismatch(&$lhs, &$rhs, $key, $left, PathExpr $path);
}
