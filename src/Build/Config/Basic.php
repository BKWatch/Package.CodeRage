<?php

/**
 * Defines the class CodeRage\Build\Config\Basic
 *
 * File:        CodeRage/Build/Config/Basic.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     Makeme_Config rights reserved
 */

namespace CodeRage\Build\Config;

use CodeRage\Build\ExtendedConfig;
use CodeRage\Error;

/**
 * Implementation of CodeRage\Build\ExtendedConfig based on an associative array
 * of instances of CodeRage\Build\Config\Property.
 */
class Basic implements ExtendedConfig {

    /**
     * An associative array of instances of CodeRage\Build\Config\Property, indexed by
     * name.
     *
     * @var array
     */
    private $properties = [];

    /**
     * Constructs a CodeRage\Build\Config\Basic from a list of properties
     * or an instance of CodeRage\Build\ExtendedConfig.
     *
     * @param mixed $properties An instance of CodeRage\Build\ExtendedConfig whose
     * properties will be copied or a list of instances of
     * CodeRage\Build\Config\Property.
     */
    function __construct($properties = [])
    {
        if (is_array($properties)) {
            foreach ($properties as $p)
                $this->addProperty($p);
        } elseif ($properties instanceof ExtendedConfig) {
            foreach ($properties->propertyNames() as $n)
                $this->addProperty(clone $properties->lookupProperty($n));
        } else {
            throw new
                \CodeRage\Error(
                   'Invalid argument to CodeRage\Build\Config\Basic:: ' .
                   '__construct(): ' . Error::formatValue($properties)
                );
        }
    }

    /**
     * Returns the keys of this property bundle, as an array of strings.
     *
     * @return array
     */
    function propertyNames() { return array_keys($this->properties); }

    /**
     * Returns the named property, or null if no such property exists.
     *
     * @param string $name
     * @return CodeRage\Build\Config\Property
     */
    function lookupProperty($name)
    {
        return isset($this->properties[$name]) ?
            $this->properties[$name] :
            null;
    }

    /**
     * Adds the named property
     *
     * @param CodeRage\Build\Config\Property $property
     * @throws Exception if a property with the same name already exists
     */
    function addProperty(Property $property)
    {
        $name = $property->name();
        if (isset($this->properties[$name]))
            throw new \Exception("The property '$name' already exists");
        $this->properties[$name] = $property;
    }

    static function validate(ExtendedConfig $config)
    {
        foreach ($config->propertyNames() as $name) {
            $property = $config->lookupProperty($name);
            if ($property->required() && !$property->isSet())
                throw new
                    \CodeRage\Error(
                        "Missing value for required property '$name' " .
                        "specified at " . $property->specifiedAt()
                    );
        }
    }
}
