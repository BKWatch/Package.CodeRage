<?php

/**
 * Defines the class CodeRage\Test\Operations\TerminatorException
 * 
 * File:        CodeRage/Test/Operations/TerminatorException.php
 * Date:        Thu Jul 11 15:50:40 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Error;
use CodeRage\Test\PathExpr;
use CodeRage\Test\Traversal;
use CodeRage\Util\XmlEncoder;


/**
 * Class of exceptions thrown by CodeRage\Test\Operations\Terminator::check()
 */
final class TerminatorException extends \Exception {

    /**
     * Constructs a CodeRage\Test\Operations\TerminatorException
     *
     * @param CodeRage\Test\Operations\Terminator $terminator The terminator
     *   that threw the exception under construction
     * @param CodeRage\Test\PathExpr $path The path to the operation triggering
     *   test case termination with its operation list
     */
    public function __construct(Terminator $terminator, PathExpr $path)
    {
        $message =
            "Execution terminated at $path: " . lcfirst($terminator->reason());
        parent::__construct($message);
        $this->terminator = $terminator;
    }

    /**
     * Writes this exceptions's message to standard output or rethrows this
     * exception, depending on the "success" property of the underlying
     * terminator
     *
     * @throws CodeRage\Test\Operations\TerminatorException if the "success"
     *   property of the underlying termintor is false
     */
    public function handle()
    {
        if ($this->terminator->success()) {
            echo "\n\n*** " . $this->getMessage() . " ***\n\n";
        } else {
            throw $this;
        }
    }

    /**
     * @var CodeRage\Test\Operations\Terminator
     */
    private $terminator;
}
