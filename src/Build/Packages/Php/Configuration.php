<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\Configuration.
 *
 * File:        CodeRage/Build/Packages/Php/Configuration.php
 * Date:        Mon Jan 28 15:28:58 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Php;

/**
 * @ignore
 */

/**
 * Represents information about a particular installation of PHP.
 */
class Configuration
    extends \CodeRage\Build\Packages\BasicConfiguration
{
    /**
     * @var CodeRage\Build\Packages\Php\Ini
     */
    private $ini;

    /**
     * Constructs a CodeRage\Build\Packages\Php\Configuration.
     *
     * @param CodeRage\Build\Packages\Php\Manager $manager
     * @param string $version The version string.
     * @param string $binaryPath Path to the PHP command-line executable.
     * @throws CodeRage\Error
     */
    function __construct(
        Manager $manager, $version, $binaryPath)
    {
        parent::__construct($manager, $version, $binaryPath);
        $this->parseIni();
    }

    /**
     * Returns the collection of .ini files.
     *
     * @return CodeRage\Build\Packages\Php\Ini
     */
    function ini() { return $this->ini; }

    /**
     * Initializes the member variable $ini.
     *
     * @throws CodeRage\Error
     */
    private function parseIni()
    {
        // Fetch primary config file
        $iniPath =
            $this->runCommand('-r "echo get_cfg_var(\'cfg_file_path\');"');
        if (!$iniPath)
            return;

        // Fetch config file scan directory.
        $match = null;
        $iniScanDir =
            preg_match( '/additional \.ini files => (.*)/',
                        $this->runCommand('-i'),
                        $match ) ?
                $match[1] :
                null;

        // Construct ini:
        $this->ini = new Ini($iniPath, $iniScanDir);
    }
}
