<?php

/**
 * Defines the class CodeRage\Build\Test\Target\Basic, the base class for the
 * targets CodeRage\Build\Test\Target\Foo and CodeRage\Build\Test\Target\Bar.
 *
 * File:        CodeRage/Build/Test/Target/Basic.php
 * Date:        Sat Mar 21 13:08:15 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test\Target;

use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Base class for targets CodeRage\Build\Test\Target\Foo and CodeRage\Build\Test\Target\Bar.
 */
class Basic extends \CodeRage\Build\Target\Basic {

    /**
     * The simple name of the target type, e.g., "foo" or "bar"
     *
     * @var string
     */
    private $type;

    /**
     * true if execute() should succeed.
     *
     * @var boolean
     */
    private $status;

    /**
     * Associative array whose keys are strings of the form "type#id", where
     * "type" is the simple name of a target type, e.g., "foo" and "id" is a
     * target identifier.
     *
     * @var array
     */
    private static $builtTargets = [];

    /**
     * Constructs a CodeRage\Build\Test\Target\Basic.
     *
     * @param string $type The simple name of the target type, e.g., "foo" or
     * "bar".
     * @param boolean $status true if execute() should succeed.
     * @param string $id The string, if any, identifying the target under
     * construction.
     * @param array $dependencies The list of IDs of dependent targets, if any.
     * @param CodeRage\Build\Info $info An instance of CodeRage\Build\Info describing the
     * target under construction.
     */
    function __construct($type, $status, $id = null, $dependencies = [],
        $info = null)
    {
        parent::__construct($id, $dependencies, $info);
        $this->type = $type;
        $this->status = $status;
    }

    /**
     * Builds this target.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @throws CodeRage\Error
     */
    function execute(\CodeRage\Build\Run $run)
    {
        $label =
            "target " .($this->id() ? "'" . $this->id() . "'" : "<unknown>") .
            " of type '$this->type'";
        foreach ($this->dependencies() as $id)
            if (!isset(self::$builtTargets["$this->type#$id"]))
                throw new Error(['message' => "Missing dependency $id"]);
        if (!$this->status)
            throw new Error(['message' => "An error occurred"]);
        self::$builtTargets[$this->type . '#' . $this->id()] = 1;
    }
}
