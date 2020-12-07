<?php

/**
 * Defines the class CodeRage\Build\BuildConfigFile
 *
 * File:        CodeRage/Build/BuildConfigFile.php
 * Date:        Thu Jan 01 18:33:48 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Error;

/**
 * Stores the path and time of last modification of a configuration file.
 *
 */
class BuildConfigFile {

    /**
     * The pathname
     *
     * @var string
     */
    private $path;

    /**
     * The time of last modification, as a UNIX timestamp
     *
     * @var int
     */
    private $timestamp;

    /**
     * Constructs a CodeRage\Build\BuildConfigFile.
     *
     * @param string $path The pathname
     * @param int $timestamp The time of last modification, as a UNIX
     * timestamp
     */
    function __construct($path, $timestamp = null)
    {
        if ($timestamp === null) {
            if (!file_exists($path))
                throw new Error(['message' => "No such file: $path"]);
            $timestamp = @filemtime($path);
            if ($timestamp === false)
                throw new
                    Error([
                        'message' =>
                            "Failed querying last modification time: $path"
                    ]);
            $path = realpath($path);
        }
        $this->path = $path;
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the pathname
     *
     * @return string
     */
    function path()
    {
        return $this->path;
    }

    /**
     * Returns the time of last modification, as a UNIX timestamp
     *
     * @return int
     */
    function timestamp()
    {
        return $this->timestamp;
    }

    /**
     * Returns a PHP definition of this instance.
     *
     * @return string
     */
    function definition()
    {
        return '[' . Error::formatValue($this->path) . ',' .
                     Error::formatValue($this->timestamp) . ']';
    }
}
