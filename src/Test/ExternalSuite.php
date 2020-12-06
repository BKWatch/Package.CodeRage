<?php

/**
 * Contains the definition of the class CodeRage\Test\ExternalSuite, representing a
 * test instance which outputs an XML report. The report typically summarizes
 * a collection of tests run in an external environment, such as in another
 * programming language or on a remote host
 *
 * File:        CodeRage/Test/ExternalSuite.php
 * Date:        Wed Mar 14 06:38:17 MDT 2007
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 * @todo        Handle exception in execute()
 */

namespace CodeRage\Test;

/**
 * @ignore
 */

/**
 * Represents  a test instance which outputs an XML report. The report typically
 * summarizes a collection of tests run in an external environment, such as in
 * another programming language or on a remote host
 */
abstract class ExternalSuite extends Component {

    /**
     * Constructs a CodeRage\Test\ExternalSuite with the given name and description.
     * The given name and description will be used to generated the XML report
     * only if doExecute() terminates abnormally, since otherwise the report
     * output by doExecute() will contain its own name and description.
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     */
    protected function __construct($name, $description)
    {
        parent::__construct($name, $description);
    }

    /**
     * @return boolean true if the most recent call to execute() timed out.
     */
    public final function setTimedOut($value = null)
    {
        $this->isTimedOut = $value;
    }

    /*
     * Returns the wall clock time in seconds of the running time for
     * the last execute call.
     */
    public final function getElapsed()
    {
        return $this->elapsed;
    }

    /*
     * Sets the wall clock time in seconds of the running time for
     * the last execute call.
     */
    public final function setElapsed($value = null)
    {
        $this->elapsed = ceil($value);
    }

    /**
     * Returns TYPE_SUITE
     *
     * @return int
     */
    protected final function doType() { return self::TYPE_SUITE; }

    /**
     * Returns true if the most recent call to execute() timed out
     *
     * @return boolean
     */
    protected final function doIsTimedOut()
    {
        return $this->isTimedOut;
    }

    /**
     * Executes this suite, writing an XML report to standard output.
     *
     * @param array $params an associate array of parameters. The following
     * values are supported
     *
     * <ul>
     *   <li> timeout: the number of miliseconds to wait before aborting;
     *        defaults to CodeRage\Test\Component::DEFAULT_TIMEOUT
     * </ul>
     *
     * @return boolean true if all the test cases described in the XML report
     * executed successfully
     * @throws Exception if an unusual error occurs; should not
     *   throw an exception to indicate an ordinary test failures
     */
    //abstract protected function doExecute($params);

    private $isTimedOut = false;
    private $elapsed = 0;
}
