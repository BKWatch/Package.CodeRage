<?php

/**
 * Defines the class CodeRage\Build\Test\Target\Bar.
 *
 * File:        CodeRage/Build/Test/Target/Bar.php
 * Date:        Sat Mar 21 13:08:15 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test\Target;

/**
 * @ignore
 */

/**
 * Target type used for testing.
 */
class Bar extends Basic {

    /**
     * Constructs a CodeRage\Build\Test\Target\Bar.
     *
     * @param boolean $status true if execute() should succeed.
     * @param string $id The string, if any, identifying the target under
     * construction.
     * @param array $dependencies The list of IDs of dependent targets, if any.
     * @param CodeRage\Build\Info $info An instance of CodeRage\Build\Info describing the
     * target under construction.
     */
    function __construct($status, $id = null, $dependencies = [],
        $info = null)
    {
        parent::__construct('bar', $status, $id, $dependencies, $info);
    }
}
