<?php

/**
 * Defines the class CodeRage\Util\Test\ProcessOptionErrorCase
 * 
 * File:        CodeRage/Util/Test/ProcessOptionErrorCase.php
 * Date:        Mon Aug 15 13:34:26 EDT 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use Exception;
use stdClass;
use Throwable;
use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test case that invokes processOption() with a sequence of arguments that
 * do not conform to the documentation
 */
class ProcessOptionErrorCase extends \CodeRage\Test\Case_ {

    /**
     * Constructs an instance of CodeRage\Util\Test\ProcessOptionCase
     *
     * @param string $name The test case name
     * @param string $descrption The test case description
     * @param array $arguments The list of arguments to pass to processOptions()
     */
    public function __construct($name, $description, array $arguments)
    {
        parent::__construct($name, $description);
        $this->arguments = $arguments;
    }

    protected function doExecute($params)
    {
        $options = isset($params['error-level']) ?
            ['level' => $params['error-level']] :
            [];
        $handler = new \CodeRage\Util\ErrorHandler($options);
        try {
            $args = $this->arguments;
            $options = array_shift($args);
            Args::checkKey($options, ...$args);
        } catch (Error $e) {
            if ($e->status() == 'INVALID_PARAMETER')
                return;
        }

        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    "Expected exception with status code 'INVALID_PARAMETER'; " .
                    "none caught"
            ]);
    }

    /**
     * The list of arguments to pass to processOptions()
     *
     * @var array
     */
    private $arguments;
}
