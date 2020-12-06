<?php

/**
 * Defines the trait CodeRage\Test\Operations\XmlSupport
 * 
 * File:        CodeRage/Test/Operations/XmlSupport.php
 * Date:        Wed Jul 24 02:17:06 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use DOMDocument;
use DOMNode;

/**
 * Provides static methods for creating XML elements
 */
trait XmlSupport {

    /**
     * Returns an XML element with the given local name and optional text
     * content
     *
     * @param DOMDocument $dom The document used to create elements
     * @param string $localName The local name
     * @param string $content The optional text content
     * @return DOMElement The newly created XML element
     */
    protected final function createElement(
        DOMDocument $dom, $localName, $content = null)
    {
        $result =
            $dom->createElementNS(XmlSupportConstants::NAMESPACE_URI, $localName);
        if ($content !== null)
            $result->appendChild($dom->createTextNode($content));
        return $result;
    }

    /**
     * Creates an XML element with the given local name and optional text
     * content and appends it as a child element of the given node
     *
     * @param DOMDocument $dom The document used to create elements
     * @param DOMNode $parent The parent node
     * @param string $localName The local name
     * @param string $content The optional text content
     * @return DOMElement The newly created XML element
     */
    protected final function appendElement(
        DOMDocument $dom, DOMNode $parent, $localName, $content = null)
    {
        $result = $this->createElement($dom, $localName, $content);
        $parent->appendChild($result);
        return $result;
    }
}
