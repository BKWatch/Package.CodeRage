<?php

/**
 * Contains the definition of the class CodeRage\Test\CommandLineCase, representing a
 * test case which invokes an external command
 *
 * File:        CodeRage/Test/CommandLineCase.php
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
 * Represents a test case which invokes an external command
 */
class CommandLineCase extends Case_ {
    private $command;
    private $environment = [];
    private $isTimedOut = false;
    private $elapsed = 0;

    /**
     * Constructs a CodeRage\Test\CommandLineCase which invokes the given command
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     * @param string $command A command line to be passed to a command-line
     * interpreter
     * @param array $environment An associative array of environment variable
     * values, or null to use the environment of the current process
     */
    public function __construct(
        $name, $description, $command, $environment = [])
    {
        parent::__construct($name, $description);
        $this->command = $command;
        $this->environment = $environment;
    }

    /**
     * @return float The wall clock time in seconds of the running time for
     * the last execute call.
     */
    public final function getElapsed()
    {
        return ceil($this->elapsed);
    }

    /**
     * Returns true if the most recent call to execute() timed out
     *
     * @return boolean
     */
    protected final function doIsTimedOut() { return $this->isTimedOut; }

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
        $this->isTimedOut = $timedOut;
        $this->elapsed = $elapsed;
        return $status === 0 && !$timedOut;
    }
}
