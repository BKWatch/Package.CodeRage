<?php

/**
 * Contains the definition of the class CodeRage\Test\CommandLineSuite, representing a
 * test suite which generates an XML report by invoking an external command
 *
 * File:        CodeRage/Test/CommandLineSuite.php
 * Date:        Wed Mar 14 06:38:17 MDT 2007
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use CodeRage\Util\os;

/**
 * @ignore
 */

/**
 * Represents a test suite which generates an XML report by invoking an external
 * command
 */
class CommandLineSuite extends ExternalSuite {
    private $command;
    private $environment;

    /**
     * Constructs a CodeRage\Test\CommandLineSuite which invokes the given command
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     * @param string $command A command line to be passed to a command-line
     * interpreter
     * @param array $environment An associative array of environment variable
     * values, or null to use the environment of the current process
     * @param boolean $replaceEnvironment true if the values in $environment
     * should replace the command-line interpreter's default environment
     * rather than supplementing it
     */
    public function __construct(
        $name, $description, $command, $environment = [])
    {
        parent::__construct($name, $description);
        $this->command = $command;
        $this->environment = $environment;
    }

    /**
     * Executes the underlying command, writing the command's standard output
     * and error output to PHP's standard output. Does not throw exceptions.
     *
     * @param array $params an associate array of parameters. The following
     * values are supported
     *
     * <ul>
     *   <li> timeout: the number of miliseconds to wait before aborting;
     *        defaults to CodeRage\Test\Component::DEFAULT_TIMEOUT
     *   <li> cwd: the working directory, for test cases or suites that
     *        run external commands.
     * </ul>
     *
     * @return boolean true for success
     */
    protected final function doExecute($params)
    {
        // Apply default values
        $cwd = isset($params['cwd']) ?
            $params['cwd'] :
            getcwd();
        $timeout = isset($params['timeout']) ?
            $params['timeout'] :
            self::DEFAULT_TIMEOUT;

        // Run command
        list($status, $elapsed, $timedOut) =
            os::run($this->command, $timeout, $cwd, $this->environment);

        // Process results
        $this->setTimedOut($timedOut);
        $this->setElapsed($elapsed);
        return $status == 0 && !$timedOut;
    }
}
