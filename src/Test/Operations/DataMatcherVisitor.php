<?php

/**
 * Defines the class CodeRage\Test\Operations\DataMatcherVisitor
 * 
 * File:        CodeRage/Test/Operations/DataMatcherVisitor.php
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


/**
 * Helper for CodeRage\Test\Operations\DataMatcher::assertMatch()
 */
final class DataMatcherVisitor extends \CodeRage\Test\AssertVisitorBase {

    /**
     * Constructs a CodeRage\Test\Operations\DataMatcherVisitor
     *
     * @param CodeRage\Test\Operations\DataMatcher $matcher
     * @param sring $message An error message, if the traversal fails
     */
    public function __construct(DataMatcher $matcher, $message)
    {
        $this->matcher = $matcher;
        $this->message = $message;
    }

    public function visit(&$lhs, &$rhs, PathExpr $path)
    {
        if (!is_scalar($lhs))
            return;
        $actual = self::toString($lhs);
        $expected = self::toString($rhs);
        if ($actual == $expected)
            return;
        foreach ($this->matcher->constraints('scalar') as $c)
            if ($c->address()->matches($path) && $c->matches($actual))
                return;
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'details' =>
                    "$this->message: expected '$expected' at $path; found " .
                    "'$actual'"
            ]);
    }

    public function lengthMismatch(&$lhs, &$rhs, PathExpr $path)
    {
        foreach ($this->matcher->constraints('list') as $c) {
            if ($c->address()->matches($path) && $c->matches($lhs)) {
                $c->replace($lhs);
                return;
            }
        }
        parent::lengthMismatch($lhs, $rhs, $path);
    }

    /**
     * Returns the result of converting the given scalar or null value to a
     * string
     *
     * @param mixed $value
     */
    private static function toString($value)
    {
        return !is_bool($value) ?
            (string) $value :
            ($value ? '1' : '0');
    }

    /**
     * @var CodeRage\Test\Operations\DataMatcher
     */
    private $matcher;

    /**
     * @var string
     */
    private $message;
}
