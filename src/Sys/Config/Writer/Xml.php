<?php

/**
 * Defines the class CodeRage\Sys\Config\Writer\Xml.
 *
 * File:        CodeRage/Sys/Config/Writer/Xml.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config\Writer;

use DOMDocument;
use DOMElement;
use CodeRage\Sys\Property;
use CodeRage\File;

/**
 * Generates an XML configuration file conforming to the schema "project.xsd"
 * and having document element "config."
 */
class Xml implements \CodeRage\Sys\Config\Writer {
    use \CodeRage\Xml\ElementCreator;

    /**
     * Writes the given property bundle to the specified file, as an XML
     * document conforming to the schema "project.xsd" and having document
     * element "config."
     *
     * @param CodeRage\Sys\ProjectConfig $properties
     * @param string $path
     * @throws Exception
     */
    public function write(\CodeRage\Sys\ProjectConfig $properties, string $path): void
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $config = $this->createElement($dom, 'config');
        foreach ($properties->propertyNames() as $n) {
            $p = $properties->lookupProperty($n);
            $config->appendChild($this->formatProperty($dom, $n, $p));
        }
        File::generate($path, $dom->saveXml($config), 'xml');
    }

    protected function namespaceUri() : ?string
    {
        return \CodeRage\Sys\NAMESPACE_URI;
    }

    /**
     * Returns a "property" element
     *
     * @param string $name The property name
     * @param CodeRage\Sys\Config\Property $property
     * @return DOMElement
     */
    private function formatProperty(
        DOMDocument $dom,
        string $name,
        Property $property
    ): DOMElement {
        $elt = $this->createElement($dom, 'property');
        $elt->setAttribute('name', $name);
        $elt->setAttribute('storage', $this->translateStorage($property->storage()));
        $value = $property->value();
        if (mb_check_encoding($value, 'UTF-8') && ctype_print($value)) {
            $elt->setAttribute('value', $value);
        } else {
            $elt->setAttribute('value', base64_encode($value));
            $elt->setAttribute('encoding', 'base64');
        }
        if ($setAt = $property->setAt()) {
            $elt->setAttribute('setAt', $setAt);
        }
        return $elt;
    }

    private function translateStorage(int $storage): string
    {
        switch ($storage) {
        case Property::LITERAL: return 'literal';
        case Property::ENVIRONMENT: return 'environment';
        case Property::FILE: return 'file';
        default:
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Invalid storage: $storage"
                ]);
        }
    }
}
