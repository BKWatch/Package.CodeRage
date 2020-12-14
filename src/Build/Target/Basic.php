<?php

/**
 * Defines the abstract class Basic.
 *
 * File:        CodeRage/Build/Target/Basic.php
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

/**
 * Partial implementation of CodeRage\Build\Target definition methods for managing
 * dependencies and metadata.
 */
abstract class Basic implements \CodeRage\Build\Target {

    /**
     * The string, if any, identifying this target.
     *
     * @var string
     */
    private $id;

    /**
     * The list of IDs dependent targets, if any.
     *
     * @var array
     */
    private $dependencies;

    /**
     * An instance of CodeRage\Build\Info describing this target, or null.
     *
     * @var CodeRage\Build\Info
     */
    private $info;

    /**
     * The instance of DOMElement, if any, containing the definition of this
     * target.
     *
     * @var DOMElement
     */
    private $definition;

    /**
     * The path of the project definition file, if any, containing the
     * definition of this target.
     *
     * @var string
     */
    private $src;

    /**
     * Constructs a CodeRage\Build\Target\Basic.
     *
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
    function __construct($id = null, $dependencies = [], $info = null,
        $definition = null, $src = null)
    {
        $this->id = $id;
        $this->dependencies = $dependencies;
        $this->info = $info;
        $this->definition = $definition;
        $this->src = $src;
    }

    /**
     * Returns the string, if any, identifying this target.
     *
     * @return string
     */
    function id() { return $this->id; }

    /**
     * Specifies the string, if any, identifying this target.
     *
     * @param string $id
     */
    function setId($id) { $this->id = $id; }

    /**
     * Returns the list of ids of dependent targets, if any.
     *
     * @return array
     */
    function dependencies() { return $this->dependencies; }

    /**
     * Specifies the list of ids of dependent targets, if any.
     *
     * @param array $dependencies
     */
    function setDependencies($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Returns an instance of CodeRage\Build\Info describing this target.
     *
     * @return CodeRage\Build\Info.
     */
    function info()
    {
        return $this->info;
    }

    /**
     * Specifies an instance of CodeRage\Build\Info describing this target.
     *
     * @return CodeRage\Build\Info.
     */
    function setInfo(\CodeRage\Build\Info $info)
    {
        $this->info = $info;
    }

    /**
     * Returns the instance of DOMElement, if any, containing the definition of
     * this target.
     *
     * @return DOMElement
     */
    function definition()
    {
        return $this->definition;
    }

    /**
     * Specifies the instance of DOMElement containing the definition of
     * this target.
     *
     * @param DOMElement $definition
     */
    function setDefinition(\DOMElement $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Returns the path of the project definition file, if any, containing the
     * definition of this target.
     *
     * @return DOMElement
     */
    function source()
    {
        return $this->src;
    }

    /**
     * Specifies the instance of the project definition file containing the
     * definition of this target.
     *
     * @param string $src
     */
    function setSource($src)
    {
        $this->src = $src;
    }
}
