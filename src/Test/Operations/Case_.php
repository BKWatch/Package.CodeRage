<?php

/**
 * Defines the class CodeRage\Test\Operations\Case_
 *
 * File:        CodeRage/Test/Operations/Case_.php
 * Date:        Tue May 8 23:56:33 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test case whose doExecute() method tests, generates or outputs the path of an
 * instance of CodeRage\Test\Operations\AbstractOperation
 */
class Case_ extends \CodeRage\Test\Case_ {

    /**
     * @var string
     */
    const MATCH_MODE = '/^(test|generate|list)$/';

    /**
     * @var string
     */
    const DEFAULT_MODE = 'test';

    /**
     * Constructs an instance of CodeRage\Test\Operations\Case_
     *
     * @param CodeRage\Test\Operations\AbstractOperation $operation The
     *   operation
     * @param string $mode One of the values "test", "generate" or "list",
     *   indicating whether execute() should test, generate or output the
     *   path of $operation
     * @param string $name The operation case name; defaults to the path of
     *   operation
     */
    public function __construct(AbstractOperation $operation,
        $mode = self::DEFAULT_MODE)
    {
        Args::check($mode, 'string', 'mode');
        if (!preg_match(self::MATCH_MODE, $mode))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid mode: $mode"
                ]);
        $name = pathinfo($operation->path(), PATHINFO_FILENAME);
        parent::__construct($name, $operation->description());
        $this->operation = $operation;
        $this->mode = $mode;
    }

    /**
     * Tests, generates, or outputs the path of the the underlying operation,
     * depending on whether the underlying mode is "test", "generate", or "list"
     *
     * @param array $params an associate array of parameters
     * @return boolean true for success
     * @throws Exception if an error occurs
     */
    protected final function doExecute($params)
    {
        if ($this->mode == 'test') {
            $this->operation->test();
        } elseif ($this->mode == 'generate') {
            $this->operation->generate();
            $this->operation->save($this->operation->path());
        } elseif ($this->mode == 'list') {
            echo "[[PATH:{$this->operation->path()}]]";
        }
        return true;
    }

    /**
     * Returns the underlying operation
     *
     * @return CodeRage\Test\Operations\AbstractOperation
     */
    public function operation()
    {
        return $this->operation;
    }

    /**
     * The underlying operation
     *
     * @var CodeRage\Test\Operations\AbstractOperation
     */
    private $operation;
}
