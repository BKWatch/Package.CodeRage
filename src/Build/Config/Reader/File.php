<?php

/**
 * Defines the class CodeRage\Build\Config\Reader\File.
 *
 * File:        CodeRage/Build/Config/Reader/File.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Reader;

use DOMElement;
use CodeRage\Build\Config\Basic;
use CodeRage\Build\Config\Property;
use const CodeRage\Build\ISSET_;
use const CodeRage\Build\NAMESPACE_URI;
use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\ErrorHandler;
use CodeRage\Util\Os;
use CodeRage\Xml;

/**
 * Reads collections of properties from an XML document, an .ini file, or a
 * PHP script.
 */
class File implements \CodeRage\Build\Config\Reader {

    /**
     * Regular expression used to validate variable names.
     *
     * @var string
     */
    const VALIDATE_NAME = '/^([_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*)$/i';

    /**
     * The build engine
     *
     * @var CodeRage\Build\Engine
     */
    private $engine;

    /**
     * The pathname of the underlying file.
     *
     * @var string
     */
    private $path;

    /**
     * @var CodeRage\Build\ProjectConfig
     */
    private $properties;

    /**
     * Constructs a CodeRage\Build\Config\Reader\File
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @param CodeRage\Build\ProjectConfig $properties
     */
    function __construct(\CodeRage\Build\Engine $engine, $path)
    {
        $this->engine = $engine;
        $this->path = $path;
        \CodeRage\File::checkReadable($path);
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
        case 'xml':
            $this->readXmlFile($path);
            break;
        default:
            throw new Error(['message' => "Unknown config file type: $path"]);
        }
    }

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Build\ProjectConfig
     */
    function read()
    {
        return $this->properties;
    }

    /**
     * Parses the specified XML config file.
     *
     * @param string $path The pathname of an XML document conforming to the
     * schema schema Makeme/Resource/Files/project.xsd. It is not necessarily
     * the same file passed to the CodeRage\Build\Config\Reader\File constructor, but
     * must be derived from that file.
     * @return CodeRage\Build\ProjectConfig
     */
    private function readXmlFile($path)
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
                Error(['message' =>
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
                $reader = new File($this->engine, $file);
                $props = $reader->read();
                foreach ($props->propertyNames() as $n)
                    $properties->addProperty($props->lookupProperty($n));
            }
        }

        // Process property and group definitions
        $config = $root->localName == 'config' ?
            $root :
            Xml::firstChildElement($root, 'config');
        if ($config)
            foreach (self::readGroup($config, $path) as $p)
                $properties->addProperty($p);
        $this->properties = $properties;
    }

    /**
     * Returns a list of properties constructed from the given XML element of
     * complex type "configGroup" in the namespace
     * http://www.coderage.com/2008/project, conforming to the schema
     * "project.xsd."
     *
     * @param DOMElement $group
     * @param string $baseUri The URI used to resolve relative path references.
     * @param string $prefix A compound identifier, e.g., "theme" or
     * "theme.default.color."
     * @return array A list of instances of CodeRage\Build\Config\Property.
     * @throws Exception
     */
    private function readGroup(DOMElement $group, $baseUri, $prefix = null)
    {
        $result = [];
        foreach (Xml::childElements($group) as $elt) {
            switch ($elt->localName) {
            case 'group':
                $name = Xml::getAttribute($elt, 'name');
                if ($group->localName == 'group' && $name === null)
                    throw new
                        Error(['message' =>
                            "Missing 'name' attribute on 'group' element " .
                            "in " . Xml::documentPath($group)
                        ]);
                $props =
                    self::readGroup(
                        $elt, $baseUri, self::applyPrefix($prefix, $name)
                    );
                foreach ($props as $p)
                    $result[] = $p;
                break;
            case 'property':
                $result[] = self::readProperty($elt, $prefix);
                break;
            default:
                break;
            }
        }
        return $result;
    }

    /**
     * Returns a property constructed from the given XML element of
     * complex type "configProperty" in the namespace
     * http://www.coderage.com/2008/project, conforming to the schema
     * "project.xsd."
     *
     * @param DOMElement $property
     * @param string $prefix A compound identifier, e.g., "theme" or
     * "theme.default.color."
     * @return CodeRage\Build\Config\Property
     * @throws Exception
     */
    private function readProperty(DOMElement $property, $prefix = null)
    {
        // Set name
        $name =
            self::applyPrefix(
                $prefix, Xml::getAttribute($property, 'name')
            );

        // Set flags and value
        $flags = 0;
        $value = null;
        if ($property->hasAttribute('value')) {
            $flags |= ISSET_;
            $value = Xml::getAttribute($property, 'value');
            $encoding = Xml::getAttribute($property, 'encoding');
            if ($encoding == 'base64') {
                $value = base64_decode($value);
            }
        }

        // Set setAt
        $setAt = null;
        if ($flags & ISSET_) {
            $attr = Xml::getAttribute($property, 'setAt', $this->path);
            switch ($attr) {
            case '[command-line]':
                $setAt = \CodeRage\Build\COMMAND_LINE;
                break;
            case '[environment]':
                $setAt = \CodeRage\Build\ENVIRONMENT;
                break;
            case '[console]':
                $setAt = \CodeRage\Build\CONSOLE;
                break;
            default:
                break;
            }
        } elseif ($property->hasAttribute('setAt')) {
            throw new
                Error(['message' =>
                    "Invalid attribute 'setAt' for property '$name': no " .
                    "value specified for property"
                ]);
        }

        return new
            Property(
                $name, $flags, $value, 0, $setAt
            );
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
    private function applyPrefix($prefix, $name)
    {
        return $prefix === null ?
            $name :
            ( $name === null ?
                  $prefix :
                  "$prefix.$name" );
    }
}
