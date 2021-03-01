<?php

/**
 * Defines the class CodeRage\Sys\Config\Reader\File.
 *
 * File:        CodeRage/Sys/Config/Reader/File.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config\Reader;

use DOMElement;
use CodeRage\Sys\ProjectConfig;
use CodeRage\Sys\Config\Basic;
use CodeRage\Sys\Property;
use const CodeRage\Sys\NAMESPACE_URI;
use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\ErrorHandler;
use CodeRage\Util\Os;
use CodeRage\Xml;

/**
 * Reads collections of properties from an XML document, an .ini file, or a
 * PHP script.
 */
class File implements \CodeRage\Sys\Config\Reader {

    /**
     * Regular expression used to validate variable names.
     *
     * @var string
     */
    private const VALIDATE_NAME = '/^([_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*)$/i';

    /**
     * Constructs a CodeRage\Sys\Config\Reader\File
     *
     * @param string $path The file pathname
     * @param string $setAt The source of the property value; must be a file
     *   pathname or one of the special values "[cli]" or "[XXX]" where XXX is a
     *   module names
     */
    public function __construct(string $path, ?string $setAt = null)
    {
        $this->setAt = $setAt ?? $path;
        $this->readXmlFile($path);
    }

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Sys\ProjectConfig
     */
    public function read(): ProjectConfig
    {
        return $this->properties;
    }

    /**
     * Parses the specified XML config file.
     *
     * @param string $path The pathname of an XML document conforming to the
     *   schema schema Makeme/Resource/Files/project.xsd. It is not necessarily
     *   the same file passed to the CodeRage\Sys\Config\Reader\File
     *   constructor, but must be derived from that file.
     */
    private function readXmlFile($path): void
    {
        $schema = __DIR__ . '/../../project.xsd';
        $dom = Xml::loadDocument($path, $schema);
        $root = $dom->documentElement;
        if ( $root->localName != 'project' &&
                 $root->localName != 'config' ||
             $root->namespaceURI != NAMESPACE_URI )
        {
            $element =
                ($root->namespaceURI ? "$root->namespaceURI:" : '') .
                $root->localName;
            throw new
                Error([
                    'message' =>
                        "Invalid configuration file '$path': expected '" .
                        NAMESPACE_URI . ":project' or '" .
                        NAMESPACE_URI . ":config'; found " .
                        "'$element'"
                ]);
        }

        // Process included files
        $properties = new Basic;
        if ($root->localName == 'project') {
            foreach (Xml::childElements($root, 'include') as $inc) {
                $src = $inc->getAttribute('src');
                $file = \CodeRage\File::resolve($src, $path);
                if (!$file)
                    throw new
                        Error([
                            'message' =>
                                "Failed parsing config file '$path': no such " .
                                "file: '$src'"
                        ]);
                $reader = new File($file);
                $props = $reader->read();
                foreach ($props->propertyNames() as $n)
                    $properties->addProperty($n, $props->lookupProperty($n));
            }
        }

        // Process property and group definitions
        $config = $root->localName == 'config' ?
            $root :
            Xml::firstChildElement($root, 'config');
        if ($config) {
            foreach (self::readGroup($config, $path) as $n => $p) {
                $properties->addProperty($n,  $p);
            }
        }
        $this->properties = $properties;
    }

    /**
     * Returns an associative array of properties constructed from the given XML
     * element of complex type "configGroup" in the namespace
     * http://www.coderage.com/2008/project, conforming to the schema
     * "project.xsd"
     *
     * @param DOMElement $group
     * @param string $baseUri The URI used to resolve relative path references.
     * @param string $prefix A compound identifier, e.g., "theme" or
     *    "theme.default.color"
     * @return array An associative array mapping property names to instances
     *   of CodeRage\Sys\Property
     * @throws Exception
     */
    private function readGroup(DOMElement $group, $baseUri, $prefix = null): array
    {
        $properties = [];
        foreach (Xml::childElements($group) as $elt) {
            switch ($elt->localName) {
            case 'group':
                $name = Xml::getAttribute($elt, 'name');
                if ($group->localName == 'group' && $name === null)
                    throw new
                        Error([
                            'message' =>
                                "Missing 'name' attribute on 'group' element " .
                                "in " . Xml::documentPath($group)
                        ]);
                $props =
                    self::readGroup(
                        $elt,
                        $baseUri,
                        self::applyPrefix($prefix, $name)
                    );
                foreach ($props as $n => $p)
                    $properties[$n] = $p;
                break;
            case 'property':
                [$n, $p] = self::readProperty($elt, $prefix);
                $properties[$n] = $p;
                break;
            default:
                break;
            }
        }
        return $properties;
    }

    /**
     * Returns a named property constructed from the given XML element of
     * complex type "configProperty" in the namespace
     * http://www.coderage.com/2008/project, conforming to the schema
     * "project.xsd."
     *
     * @param DOMElement $property
     * @param string $prefix A compound identifier, e.g., "theme" or
     *   "theme.default.color."
     * @return array A pair of the form [$n, $p], where $n is a string an $p is
     *   an instance of CodeRage\Sys\Property
     * @throws Exception
     */
    private function readProperty(DOMElement $property, $prefix = null): array
    {
        // Set name
        $name =
            self::applyPrefix(
                $prefix,
                Xml::getAttribute($property, 'name')
            );
        $storage = Xml::getAttribute($property, 'storage', 'literal');
        $value = $value = Xml::getAttribute($property, 'value');
        $encoding = Xml::getAttribute($property, 'encoding');
        if ($encoding == 'base64') {
            $value = base64_decode($value);
        }
        $attr = Xml::getAttribute($property, 'setAt', $this->setAt);
        $setAt = $attr !== '[command-line]' ? $attr : null;
        $property =
            new Property([
                    'storage' => $this->translateStorage($storage),
                    'value' => $value,
                    'setAt' => $setAt
                ]);
        return [$name, $property];
    }

    /**
     * Returns the result of prepending the given prefix to the given property
     * or group name.
     *
     * @param string $prefix a compound identifier, e.g., "theme" or
     * "theme.default.color", or null.
     * @param string $name a compound identifier, e.g., "theme" or
     * "theme.default.color", or null.
     */
    private function applyPrefix($prefix, $name): string
    {
        return $prefix === null ?
            $name :
            ( $name === null ?
                  $prefix :
                  "$prefix.$name" );
    }

    /**
     * Helper for readProperty()
     *
     * @param int $storage
     * @return string
     */
    private function translateStorage(string $storage): int
    {
        switch ($storage) {
        case 'literal': return Property::LITERAL;
        case 'environment': return Property::ENVIRONMENT;
        case 'file': return Property::FILE;
        default:
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Invalid storage: $storage"
                ]);
        }
    }

    /**
     * @var string
     */
    private $setAt;

    /**
     * @var CodeRage\Sys\ProjectConfig
     */
    private $properties;
}
