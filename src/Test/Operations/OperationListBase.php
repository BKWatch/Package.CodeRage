<?php

/**
 * Defines the class CodeRage\Test\Operations\OperationListBase
 *
 * File:        CodeRage/Test/Operations/OperationListBase.php
 * Date:        Mon March 13 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use DOMDocument;
use DOMElement;
use CodeRage\Xml;


/**
 * Represents a sequence of operation invocations
 */
class OperationListBase extends OperationBase {

    /**
     * Constructs an instance of CodeRage\Test\Operations\OperationListBase
     *
     * @param string $description The operation list description
     * @param CodeRage\Util\Properties $properties The collection of properties
     * @param array $configProperties An associative array of configuration
     *   variables
     * @param string $path The path to the XML description of this operation
     *   list, if any
     */
    protected function __construct($description, $properties, $configProperties, $path)
    {
        $this->description = $description;
        $this->properties = $properties;
        $this->configProperties = $configProperties;
        $this->path = $path;
    }

        /*
         * Accessor methods
         */

    public function description()
    {
        return $this->description;
    }

    public function properties()
    {
        return $this->properties;
    }

    public function configProperties()
    {
        return $this->configProperties;
    }

    public function path()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function execute()
    {
        // No op
    }

    public function test()
    {
        // No op
    }

    public function generate()
    {
        // No op
    }

    public function save($path)
    {
        // No op
    }

    /**
     * Returns a colelction of properties constructed from the given element
     *
     * @param DOMElement $elt An instance of DOMElement with localName
     *   "properties"
     * @return CodeRage\Util\Properties
     */
    protected static function loadProperties(DOMElement $elt)
    {
        $propertiesElement = Xml::firstChildElement($elt, 'properties');
        $properties = null;
        if ($propertiesElement !== null) {
            $properties = new \CodeRage\Util\BasicProperties;
            foreach (Xml::childElements($propertiesElement, 'property') as $property)
                $properties->setProperty(
                    $property->getAttribute('name'),
                    $property->getAttribute('value')
                );
        }
        return $properties;
    }

    /**
     * Returns an associative array of configuration variables constructed from
     * the given "config" element
     *
     * @param DOMElement $elt An instance of DOMElement
     * @return DOMElement
     */
    protected static function loadConfig(DOMElement $elt)
    {
        $config = null;
        if (($configElt = Xml::firstChildElement($elt, 'config')) !== null) {
            $config = [];
            foreach (Xml::childElements($configElt, 'property') as $k)
                $config[$k->getAttribute('name')] = $k->getAttribute('value');
        }
        return $config;
    }

    /**
     * Returns a newly constructed XML element with localName "properties"
     * constructed from the underlying collection of properties
     *
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @return DOMElement
     */
    protected function createPropertiesElement(DOMDocument $dom)
    {
        $ns = self::NAMESPACE_URI;
        $properties = null;
        if ($this->properties !== null) {
            if (!empty($this->properties->propertyNames())) {
                $properties =
                    $dom->createElementNS($ns, 'properties');
                foreach ($this->properties->propertyNames() as $name) {
                    $property = $dom->createElementNS($ns, 'property');
                    $property->setAttribute('name', $name);
                    $property->setAttribute(
                        'value',
                        $this->properties->getProperty($name)
                    );
                    $properties->appendChild($property);
                }
            }
        }
        return $properties;
    }

    /**
     * Returns a newly constructed XML element with localName "config"
     * constructed from the underlying associative array of configuration
     * variables
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @return DOMElement
     */
    protected function createConfigElement(DOMDocument $dom)
    {
        $ns = self::NAMESPACE_URI;
        $config = null;
        if ($this->configProperties !== null) {
            $config = $dom->createElementNS($ns, 'config');
            foreach ($this->configProperties as $n => $v) {
                $property = $dom->createElementNS($ns, 'property');
                $property->setAttribute('name', $n);
                $property->setAttribute('value', $v);
                $config->appendChild($property);
            }
        }
        return $config;
    }

    /**
     * Returns a callable that expands the placeholder __FILE__
     *
     * @return callable
     */
    protected function expressionEvaluator()
    {
        return
            function($expr)
            {
                if (strpos($expr, '__FILE__') !== false)
                    return str_replace('__FILE__', dirname($this->path), $expr);
                if (strpos($expr, '__DIR__') !== false)
                    return str_replace('__DIR__', dirname($this->path), $expr);
                return $expr;
            };
    }

    /**
     * The operation list description
     *
     * @var string
     */
    private $description;

    /**
     * The collection of properties
     *
     * @var CodeRage\Util\Properties
     */
    private $properties;

    /**
     * The associative array of configuration variables, if any
     *
     * @var array
     */
    private $configProperties;

    /**
     * the path to the XML description of this operation list
     *
     * @var string
     */
    private $path;
}
