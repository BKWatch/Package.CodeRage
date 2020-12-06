<?php

/**
 * Defines the interface CodeRage\Build\Target.
 *
 * File:        CodeRage/Build/Target.php
 * Date:        Sat Jan 10 11:56:17 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Represents a component of a project that can be built individually and
 * that may depend on other such components.
 *
 */
interface Target {

    /**
     * Returns the string, if any, identifying this target uniquely within its
     * containing project.
     *
     * @return string
     */
    function id();

    /**
     * Specifies the string, if any, identifying this target uniquely within its
     * containing project.
     *
     * @param string $id
     */
    function setId($id);

    /**
     * Returns the list of ids of dependent targets, if any.
     *
     * @return array
     */
    function dependencies();

    /**
     * Specifies the list of ids of dependent targets, if any.
     *
     * @param array $dependencies
     */
    function setDependencies($dependencies);

    /**
     * Returns an instance of CodeRage\Build\Info describing this target.
     *
     * @return CodeRage\Build\Info.
     */
    function info();

    /**
     * Specifies an instance of CodeRage\Build\Info describing this target.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return CodeRage\Build\Info.
     */
    function setInfo(Info $info);

    /**
     * Returns the instance of DOMElement, if any, containing the definition of
     * this target.
     *
     * @return DOMElement
     */
    function definition();

    /**
     * Specifies the instance of DOMElement containing the definition of
     * this target.
     *
     * @param DOMElement $src
     */
    function setDefinition(\DOMElement $src);

    /**
     * Returns the path of the project definition file, if any, containing the
     * definition of this target.
     *
     * @return string
     */
    function source();

    /**
     * Specifies the instance of the project definition file containing the
     * definition of this target.
     *
     * @param string $src
     */
    function setSource($src);

    /**
     * Builds this target.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @throws CodeRage\Error
     */
    function execute(Run $run);
}
