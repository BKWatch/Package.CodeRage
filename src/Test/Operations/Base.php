<?php

/**
 * Defines the class CodeRage\Test\Operations\Object_
 *
 * File:        CodeRage/Test/Operations/Base.php
 * Date:        Mon Sep  7 18:07:32 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

/**
 * Base class for operation components
 */
abstract class Base implements XmlSupportConstants {
    use XmlSupport;

    /**
     * Encodes this instance as an XML element
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @param int $index The index of the operation under construction within
     *   its parent's list of child operations, if any
     * @return DOMElement
     */
    abstract public function save(\DOMDocument $dom, ?AbstractOperation $parent);
}
