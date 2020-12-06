<?php

/**
 * Defines the trait CodeRage\Xml\ElementCreator
 *
 * File:        CodeRage/Xml/ElementCreator.php
 * Date:        Sun Mar 15 22:08:56 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Xml;

use DOMDocument;
use DOMElement;
use DOMNode;
use CodeRage\Error;

/**
 * Provides static methods for creating XML elements
 */
trait ElementCreator {

    /**
     * Returns the namespace URI, if any, to use when creating elements with
     * createElement() and appendElement(); returns null by default
     *
     * @return string
     */
    protected function namespaceUri() : ?string
    {
        return null;
    }

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
        DOMDocument $dom, string $localName, ?string $content = null) : DOMElement
    {
        $namespace = $this->namespaceUri();
        $result = $namespace !== null ?
            $dom->createElementNS($namespace, $localName) :
            $dom->createElement($localName);
        if ($content !== null)
            $result->appendChild($dom->createTextNode($content));
        return $result;
    }

    /**
     * Creates an XML element with the given local name and optional text
     * content and appends it as a child element of the given node
     *
     * @param DOMNode $parent The parent node
     * @param string $localName The local name
     * @param string $content The optional text content
     * @return DOMElement The newly created XML element
     */
    protected final function appendElement(
        DOMNode $parent, string $localName, ?string $content = null) : DOMElement
    {
        $dom = $parent instanceof DOMDocument ?
            $parent :
            $parent->ownerDocument;
        if ($dom === null)
            throw new Error(['details' => 'Owner document is null']);
        $result = $this->createElement($dom, $localName, $content);
        $parent->appendChild($result);
        return $result;
    }
}
