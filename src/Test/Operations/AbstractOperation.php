<?php

/**
 * Defines the class CodeRage\Test\Operations\AbstractOperation
 *
 * File:        CodeRage/Test/Operations/AbstractOperation.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

/**
 * Interface implemented by CodeRage\Test\Operations\Operation and
 * CodeRage\Test\Operations\OperationBase
 */
interface AbstractOperation {

    /**
     * @var string
     */
    const SCHEMA_PATH = __DIR__ . '/operation.xsd';

    /**
     * Returns a description of this instance
     *
     * @return string
     */
    function description();

    /**
     * Returns the collection of properties
     *
     * @return CodeRage\Util\Properties
     */
    function properties();

    /**
     * Returns the path to the XML description of this operation
     *
     * @return string
     */
    function path();

    /**
     * Sets the path to the XML description of this operation
     *
     * @param string $path The path
     */
    function setPath($path);

    /**
     * Returns the associative array of configuration variables, if any
     *
     * @return array
     */
    function configProperties();

    /**
     * Returns a configuration constructed from the configuration variables
     * returned by configProperties()
     *
     * @return CodeRage\Config
     */
    function config();

    /**
     * Invokes this abstract operation and returns the result, propagating
     * any exception that is thrown
     *
     * @return mixed
     * @throws CodeRage\Error
     */
    function execute();

    /**
     * Returns the result of replacing embedded expressions with their values
     * in the given native data structure
     *
     * @param mixed $value A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @return mixed
     */
    function expandExpressions($value);

    /**
     * Invokes this abstract operation and throws an exception if the behavior
     * is unexpected
     *
     * @throws CodeRage\Error
     */
    function test();

    /**
     * Augments this abstract operation to include detailed information about
     * the result of invoking the operation
     */
    function generate();


    /**
     * Saves this instance as XML to the specified file
     *
     * @param string $path The file pathname
     */
    function save($path);
}
