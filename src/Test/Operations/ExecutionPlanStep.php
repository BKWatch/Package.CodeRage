<?php

/**
 * Defines the class CodeRage\Test\Operations\ExecutionPlanStep
 *
 * File:        CodeRage/Test/Operations/ExecutionPlanStep.php
 * Date:        Tue March 14 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

/**
 * Represents a step in an execution plan
 */
final class ExecutionPlanStep extends \CodeRage\Util\BasicProperties {

    /**
     * Constructs an instance of CodeRage\Test\Operations\ExecutionPlanStep
     *
     * @param DateTime $time The time at which $operation will be executed,
     *   according to the execution plan
     * @param CodeRage\Test\Operations\Schedulable $operation The operation
     *   to be executed
     */
    public function __construct($time, $operation)
    {
        $this->time = $time;
        $this->operation = $operation;
    }

    /**
     * Returns time at which this instance's operation will be executed,
     * according to the execution plan
     *
     * @return DateTime
     */
    public function time()
    {
        return $this->time;
    }

    /**
     * Returns the operation to be executed
     *
     * @return CodeRage\Test\Operations\Schedulable
     */
    public function operation()
    {
        return $this->operation;
    }

    /**
     * The time at which $operation will be executed, according to the execution
     * plan
     *
     * @var DateTime
     */
    private $time;

    /**
     * The operation to be executed
     *
     * @var CodeRage\Test\Operations\Schedulable
     */
    private $operation;
}
