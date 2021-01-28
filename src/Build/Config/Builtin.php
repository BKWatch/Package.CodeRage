<?php

/**
 * Defines the class CodeRage\Build\Config\Builtin
 *
 * File:        CodeRage/Build/Config/Builtin.php
 * Date:        Mon Dec  7 01:47:24 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

/**
 * The built-in configuration
 */
final class Builtin extends Array_ {

    /**
     * Consrtructs an instance of CodeRage\Build\Config\Builtin
     */
    public function __construct()
    {
        $path = \CodeRage\Config::projectRoot() . '/.coderage/config.php';
        \CodeRage\File::checkFile($path, 0b0100);
        $config = include($path);
        $properties = [];
        foreach ($config as $n => $v) {
            $properties[$n] = $v;
        }
        parent::__construct($properties);
    }
}
