<?php

/**
 * Defines the interface CodeRage\Build\Tool
 *
 * File:        CodeRage/Build/Tool.php
 * Date:        Thu Dec 25 15:05:08 MST 2008
 * Notice:      This document contains confidential information and
 *              trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Represents a manner of generating build targets from XML elements
 */
interface Tool {

    /**
     * Returns an instance of CodeRage\Build\Info describing this target.
     *
     * @return CodeRage\Build\Info
     */
    function info();

    /**
     * Specifies an instance of CodeRage\Build\Info describing this target.
     *
     * @param CodeRage\Build\Info $info
     */
    function setInfo(Info $info);

    /**
     * Returns true if this tool can create a build target from an XML
     * element with the given local name and namespace.
     *
     * @param string $localName
     * @param string $namespace
     * @return boolean
     */
    function canParse($localName, $namespace);

    /**
     * Returns an instance of CodeRage\Build\Target newly created from the given
     * XML element.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @param DOMElement $element
     * @param string $baseUri The URI for resolving relative paths referenced by
     * $elt
     * @return CodeRage\Build\Target
     * @throws CodeRage\Error
     */
    function parseTarget(Run $run, \DOMElement $elt, $baseUri);
}
