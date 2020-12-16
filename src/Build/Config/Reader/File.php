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
use const CodeRage\Build\BOOLEAN;
use CodeRage\Build\Config\Basic;
use CodeRage\Build\Config\Converter;
use CodeRage\Build\Config\Property;
use const CodeRage\Build\FLOAT;
use const CodeRage\Build\INT;
use const CodeRage\Build\ISSET_;
use const CodeRage\Build\LIST_;
use const CodeRage\Build\NAMESPACE_URI;
use const CodeRage\Build\STRING;
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
    use Converter;

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
        case 'ini':
            $this->readIniFile();
            break;
        case '':
        case 'php':
            $this->readPhpFile();
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
        $schema = __DIR__ . '/../../Resource/project.xsd';
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
     * Parses the underlying config file an an ini file.
     *
     * @return CodeRage\Build\ProjectConfig
     */
    private function readIniFile()
    {
        $path = $this->path;
        $handler = new ErrorHandler;
        $values = $handler->_parse_ini_file($path);
        if ($values === false || $handler->errno())
            throw new
                Error(['message' =>
                    $handler->formatError("Failed parsing config file '$path'")
                ]);
        if (sizeof($values) == 0) {
            $content = @file_get_contents($path);
            if (preg_match('/^\s*[^;[]/', $content))
                throw new Error(['message' => "Failed parsing config file '$path'"]);
        }
        $props = new Basic;
        foreach ($values as $n => $v) {
            if (!preg_match(self::VALIDATE_NAME, $n))
                throw new Error(['message' => "Invalid variable name: $n"]);
            $flags = null;
            switch (gettype($v)) {
            case 'boolean':
                $flags = BOOLEAN;
                break;
            case 'int':
                $flags = INT;
                break;
            case 'double':
                $flags = FLOAT;
                break;
            case 'string':
                $flags = STRING;
                break;
            default:
                throw new
                    Error(['message' =>
                        "Invalid value for property '$n': " .
                        Error::formatValue($v)
                    ]);
            }
            $flags |= ISSET_;
            $props->addProperty(
                new Property($n, $flags, $v, $path, $path)
            );
        }
        $this->properties = $props;
    }

    /**
     * Parses the underlying config file as a PHP file containing definitions of
     * global variables of the form $CFG_xxx.
     *
     * @param string $path
     * @return CodeRage\Build\ProjectConfig
     */
    private function readPhpFile()
    {
        // Define command
        $path = $this->path;
        $namespace = NAMESPACE_URI;
        $file = addcslashes($path, "'\\");
        $command =
            'php -nr "function f($f){if (!@include($f)) exit(1); echo\'' .
            '<config xmlns=\"' . $namespace . '\">\';foreach(get_defined_vars' .
            '()as$p=>$v){if(substr($p,0,4)==\'CFG_\')echo\'<property name=' .
            '\"\'.substr($p,4).\'\" value=\"\'.(is_bool($v)?intval($v):(' .
            'is_null($v)?\'null\':htmlentities($v))).\'\" type=\"\'.(((' .
            '$t=gettype($v))==\'integer\')?\'int\':$t).\'\"/>\';}echo\'' .
            '</config>\';}f(\'' . $file . '\');"';
        if (Os::type() == 'posix')
            $command = str_replace('$', '\\$', $command);

        // Execute command
        ob_start();
        $status = null;
        @system($command, $status);
        $xml = ob_get_contents();
        ob_end_clean();
        if ($status != 0)
            throw new Error(['message' => "Failed parsing config file '$path'"]);

        // Parse output as XML
        $temp = \CodeRage\File::temp('', 'xml');
        $handler = new ErrorHandler;
        $handler->_file_put_contents($temp, $xml);
        if ($handler->errno())
            throw new Error(['message' => 'Failed writing temporary XML configuration']);
        $this->readXmlFile($temp);
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
        if (Xml::getBooleanAttribute($property, 'list', false))
            $flags |= LIST_;
        if ($property->hasAttribute('value')) {
            $flags |= ISSET_;
            $value = Xml::getAttribute($property, 'value');
            $encoding = Xml::getAttribute($property, 'encoding');
            if ($flags & LIST_) {
                if ($sep = Xml::getAttribute($property, 'separator')) {
                    $value = explode($sep, $value);
                } else {
                    $value = Text::split($value);
                }
                if ($encoding == 'base64')
                    $value = array_map('base64_decode', $value);
            } elseif ($encoding == 'base64') {
                $value = base64_decode($value);
            }
        }
        if ($type = Xml::getAttribute($property, 'type')) {
            switch ($type) {
            case 'boolean':
                $flags |= BOOLEAN;
                break;
            case 'int':
                $flags |= INT;
                break;
            case 'float':
                $flags |= FLOAT;
                break;
            case 'string':
                $flags |= STRING;
                break;
            default:
                throw new \Exception("Unknown property type: $type");
            }
            $target = $flags & \CodeRage\Build\TYPE_MASK;
            if ($flags & LIST_) {
                for ($z = 0, $n = sizeof($value); $z < $n; ++$z)
                    $value[$z] = $this->convert($value[$z], $target);
            } else {
                $value = $this->convert($value, $target);
            }
        }
        if (Xml::getBooleanAttribute($property, 'required', false))
            $flags |= \CodeRage\Build\REQUIRED;
        if (Xml::getBooleanAttribute($property, 'sticky', false))
            $flags |= \CodeRage\Build\STICKY;
        if (Xml::getBooleanAttribute($property, 'obfuscate', false))
            $flags |= \CodeRage\Build\OBFUSCATE;

        // Set specified at and setAt
        $specifiedAt =
            Xml::getAttribute($property, 'specifiedAt', $this->path);
        $setAt = null;
        if ($flags & ISSET_) {
            $attr = Xml::getAttribute($property, 'setAt', $this->path);
            switch ($attr) {
            case '<command-line>':
                $setAt = \CodeRage\Build\COMMAND_LINE;
                break;
            case '<environment>':
                $setAt = \CodeRage\Build\ENVIRONMENT;
                break;
            case '<console>':
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
                $name, $flags, $value, $specifiedAt, $setAt
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
