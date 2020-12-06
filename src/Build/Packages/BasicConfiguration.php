<?php

/**
 * Defines the class CodeRage\Build\Packages\BasicConfiguration.
 *
 * File:        CodeRage/Build/Packages/BasicConfiguration.php
 * Date:        Mon Jan 28 15:28:58 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages;

/**
 * @ignore
 */
require_once('CodeRage/Util/escapeExecutable.php');
require_once('CodeRage/Util/os.php');

/**
 * Represents configuration information for a framework based on a command-line
 * tool.
 */
class BasicConfiguration
    extends Configuration
{
    /**
     * Path to the underlying command-line executable.
     *
     * @var string
     */
    private $binaryPath;

    /**
     * Constructs a CodeRage\Build\Packages\BasicConfiguration.
     *
     * @param CodeRage\Build\Packages\Manager $manager
     * @param string $version The version string.
     * @param string $binaryPath Path to the underlying command-line executable.
     */
    function __construct(
        Manager $manager, $version, $binaryPath)
    {
        parent::__construct($manager, $version);
        $this->binaryPath = $binaryPath;
    }

    /**
     * Returns the path to the underlying command-line executable.
     *
     * @return string
     */
    function binaryPath() { return $this->binaryPath; }

    /**
     * Runs the underlying command-line tool with the given arguments and
     * returns the standard output.
     *
     * @param string $args
     * @param boolean $redirectError true if standard error output should be
     * redirected to /dev/null or the equivalent.
     * @return string
     * @throws CodeRage\Error if an error occurs.
     */
    function runCommand($args, $redirectError = false)
    {
        $redirect = $redirectError ?
            (\CodeRage\Util\os() == 'windows' ? ' 2>NUL' : ' 2>/dev/null') :
            '';
        $binary = \CodeRage\Util\escapeExecutable($this->binaryPath);
        $command = "$binary $args $redirect";
        return $this->manager()->runCommand($command, $redirectError);
    }
}
