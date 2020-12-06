<?php

/**
 * Defines the class CodeRage\Test\AssertVisitor
 * 
 * File:        CodeRage/Test/AssertVisitor.php
 * Date:        Mon May 21 13:34:26 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use DOMElement;
use Exception;
use Throwable;
use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Helper for CodeRage\Test\Assert::equal()
 */
final class AssertVisitor extends AssertVisitorBase {

    public function visit(&$lhs, &$rhs, PathExpr $path)
    {
        if ((is_scalar($lhs) || is_null($lhs)) && $lhs !== $rhs)
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        'Expected ' . Error::formatValue($rhs) . self::at($path) .
                        '; found ' . Error::formatValue($lhs)
                ]);
    }
}
