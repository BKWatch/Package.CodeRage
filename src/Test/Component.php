<?php

/**
 * Contains the definition of the class CodeRage\Test\Component, the base class of
 * CodeRage\Test\Case_, CodeRage\Test\Suite, and CodeRage\Test\ExternalSuite.
 *
 * File:        CodeRage/Test/Component.php
 * Date:        Wed Mar 14 06:38:17 MDT 2007
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

/**
 * Base class of CodeRage\Test\Case_, CodeRage\Test\Suite, and CodeRage\Test\ExternalSuite
 */
abstract class Component {

    /**
     * Path to schema for test suites and cases
     *
     * @var unknown
     */
    const SCHEMA_PATH = __DIR__ . '/testSuite.xsd';

    /**
     * Indicates that a component is a suite
     *
     * @var int
     */
    const TYPE_SUITE = 1;

    /**
     * Indicates that a component is a case
     *
     * @var int
     */
    const TYPE_CASE = 2;

    /**
     * Indicates that a component is a case that outputs XML
     *
     * @var int
     */
    const TYPE_XML_CASE = 3;

    /**
     * The default timeout for execute()
     *
     * @var int
     */
    const DEFAULT_TIMEOUT = 600;

    /**
     * constructs a CodeRage\Test\Component with the given name and description
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     */
    protected function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Returns a descriptive name, unique within the list
     * of the children of this component's parent component
     *
     * @return string
     */
    public final function name() { return $this->name; }

    /**
     * Returns a brief description of this component
     *
     * @return string
     */
    public final function description() { return $this->description; }

    /**
     * Returns one of the TYPE_XXX constants
     *
     * @return int
     */
    public final function type() { return $this->doType(); }

    /**
     * Returns true if the most recent call to execute() timed out
     *
     * @return boolean
     */
    public final function isTimedOut() { return $this->doIsTimedOut(); }

    /**
     * Returns true if this component should be stripped from test
     * reports, if successful.
     *
     * @return boolean
     */
    public final function hidden() { return $this->doHidden(); }

    /**
     * Executes this component, writing the results to standard output
     *
     * @param array $params an associate array of parameters. The following
     *   parameters may be supported.
     *
     * <ul>
     *   <li> timeout: The number of miliseconds to wait before aborting;
     *        defaults to CodeRage\Test\Component::DEFAULT_TIMEOUT
     *   <li> error-level: A value suitable for passing to error_reporting().
     *   <li> cwd: the working directory, for test cases or suites that
     *        run external commands.
     * </ul>
     *
     * If one of the above parameters is not supported for a particular
     *  component, it will be ignored.
     *
     * @return boolean true for success
     */
    public final function execute($params)
    {
        return $this->doExecute($params);
    }

    /**
     * Override to return one of the TYPE_XXX constants
     *
     * @return int
     */
    protected abstract function doType();

    /**
     * Override to return true if the most recent call to execute() timed out
     *
     * @return boolean
     */
    protected function doIsTimedOut() { return false; }

    /**
     * Override to return true if this component should be stripped from test
     * reports, if successful
     *
     * @return boolean
     */
    protected function doHidden() { return false; }

    /**
     * Implement to execute this component, writing the results to standard
     * output
     *
     * @param array $params an associate array of parameters. The following
     *   parameters may be supported.
     *
     * <ul>
     *   <li> timeout: The number of miliseconds to wait before aborting;
     *        defaults to CodeRage\Test\Component::DEFAULT_TIMEOUT
     *   <li> error-level: A value suitable for passing to error_reporting().
     *   <li> cwd: the working directory, for test cases or suites that
     *        run external commands.
     * </ul>
     *
     * If one of the above parameters is not supported for a particular
     * component, it will be ignored.
     *
     * @return boolean true or null for success
     */
    protected abstract function doExecute($params);

    private $name;
    private $description;

}
