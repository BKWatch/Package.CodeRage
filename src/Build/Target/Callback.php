<?php

/**
 * Defines the class CodeRage\Build\Target\Callback
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
 * Implementation of CodeRage\Build\Target that delegates to a callable
 */
class Callback extends Basic {

    /**
     * A callable
     *
     * @var mixed
     */
    private $callback;

    /**
     * Constructs a CodeRage\Build\Target\Callback.
     *
     * @param callable $callback The callback
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
     * @param CodeRage\Build\Engine $engine The build engine
     * @param string $event One of "build", "install", or "sync"
     * @throws CodeRage\Error
     */
    function execute(\CodeRage\Build\Engine $engine, $event)
    {
        ($this->callback)($event);
    }
}
