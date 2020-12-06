<?php

/**
 * Defines the class CodeRage\Build\Target\Callback.
 *
 * File:        CodeRage/Build/Target/Callback.php
 * Date:        Sat Jan 10 11:56:17 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Target;

/**
 * @ignore
 */
require_once('CodeRage/Util/Callback.php');

/**
 * Implementation of CodeRage\Build\Target that delegates to a callback or to an
 * instance of CodeRage\Util\Callback.
 */
class Callback extends Basic {

    /**
     * A callback or instance of CodeRage\Util\Callback.
     *
     * @var mixed
     */
    private $callback;

    /**
     * Constructs a CodeRage\Build\Target\Callback.
     *
     * @param mixed $callback A callback or instance of CodeRage\Util\Callback.
     * @param string $id The string, if any, identifying the target under
     * construction.
     * @param array $dependencies The list of IDs of dependent targets, if any.
     * @param CodeRage\Build\Info $info An instance of CodeRage\Build\Info describing the
     * target under construction.
     * @param DOMElement $definition The instance of DOMElement, if any,
     * containing the definition of the target under construction.
     * @param string $src The path of the project definition file, if any,
     * containing the definition of the target under construction.
     */
    function __construct($callback, $id = null, $dependencies = [],
        $info = null, $definition = null, $src = null)
    {
        parent::__construct($id, $dependencies, $info, $definition, $src);
        $this->callback = $callback;
    }

    /**
     * Builds this target.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @throws CodeRage\Error
     */
    function execute(\CodeRage\Build\Run $run)
    {
        \CodeRage\Util\executeCallback($this->callback);
    }
}
