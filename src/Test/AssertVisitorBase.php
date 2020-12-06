<?php

/**
 * Defines the class CodeRage\Test\AssertVisitorBase
 * 
 * File:        CodeRage/Test/AssertVisitorBase.php
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
 * Helper class for implementing CodeRage\Test\BiTraversalVisitor
 */
abstract class AssertVisitorBase implements BiTraversalVisitor {

    public abstract function visit(&$lhs, &$rhs, PathExpr $path);

    public function typeMismatch(&$lhs, &$rhs, $lType, $rType, PathExpr $path)
    {
        $type = $rType == 'object' ?
            'associative array or instance of stdClass' :
            $rType;
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    "Expected $type" . self::at($path) . "; found " .
                    Error::formatValue($lhs)
            ]);
    }

    public function lengthMismatch(&$lhs, &$rhs, PathExpr $path)
    {
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    'Expected list of length ' . count($rhs) . self::at($path) .
                    '; found list of length ' . count($lhs)
            ]);
    }

    public function propertiesMismatch(&$lhs, &$rhs, $key, $left, PathExpr $path)
    {
        $adjective = $left ? 'Unexpected' : 'Missing';
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' => "$adjective property '$key'" . self::at($path)
            ]);
    }

    /**
     * Helper for constructing error messages involving path expressions
     *
     * @param CodeRage\Test\PathExpr $path A path expression
     * @return string A string of the form " at PATH" if the given path
     *   expression has at least one path component, and an empty string
     *   otherwise
     */
    protected static function at(PathExpr $path)
    {
        return $path->length() > 0 ? " at $path" : '';
    }
}
