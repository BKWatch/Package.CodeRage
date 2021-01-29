<?php

/**
 * Defines the class CodeRage\Build\Config\Writer\Xml.
 *
 * File:        CodeRage/Build/Config/Writer/Xml.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Writer;

use DOMDocument;
use DOMElement;
use CodeRage\Build\Config\Property;
use CodeRage\File;

/**
 * Generates an XML configuration file conforming to the schema "project.xsd"
 * and having document element "config."
 */
class Xml implements \CodeRage\Build\Config\Writer {
    use \CodeRage\Xml\ElementCreator;

    /**
     * Writes the given property bundle to the specified file, as an XML
     * document conforming to the schema "project.xsd" and having document
     * element "config."
     *
     * @param CodeRage\Build\ProjectConfig $properties
     * @param string $path
     * @throws Exception
     */
    public function write(\CodeRage\Build\BuildConfig $properties, string $path): void
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $config = $this->createElement($dom, 'config');
        foreach ($properties->propertyNames() as $n) {
            $p = $properties->lookupProperty($n);
            $config->appendChild($this->formatProperty($dom, $p));
        }
        File::generate($path, $dom->saveXml($config), 'xml');
    }

    protected function namespaceUri() : ?string
    {
        return \CodeRage\Build\NAMESPACE_URI;
    }

    /**
     * Returns a "property" element
     *
     * @param CodeRage\Build\Config\Property $property
     * @return DOMElement
     */
    private function formatProperty(DOMDocument $dom, Property $property): DOMElement
    {
        $elt = $this->createElement($dom, 'property');
        $elt->setAttribute('name', $property->name());
        $elt->setAttribute('type', $property->type());
        $value = $property->value();
        if (mb_check_encoding($value, 'UTF-8') && ctype_print($value)) {
            $elt->setAttribute('value', $value);
        } else {
            $elt->setAttribute('value', base64_encode($value));
            $elt->setAttribute('encoding', 'base64');
        }
        if ($setAt = $property->setAt()) {
            $elt->setAttribute(
                'setAt',
                Property::translateLocation($setAt)
            );
        }
        return $elt;
    }
}
