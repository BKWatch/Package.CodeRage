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
        \CodeRage\Util\Args::check(
            $properties,
            'CodeRage\Build\Config\ExtendedConfig|list[CodeRage\Build\Config\Property]',
            'properties'
        );
        if (is_array($properties)) {
            foreach ($properties as $p)
                $this->addProperty($p);
        } else {
            foreach ($properties->propertyNames() as $n)
                $this->addProperty(clone $properties->lookupProperty($n));
        }
    }

    public final function hasProperty($name): bool
    {
        return ($p = $this->lookupProperty($name)) && $p->isSet_();
    }

    /**
     * Returns the value of the named configuration variable, or the given
     * default value is the variable is not set
     *
     * @param string $name A configuration variable name
     * @param string $default The default value
     * @return string
     */
    public final function getProperty($name, ?string $default = null): ?string
    {
        return ($p = $this->lookupProperty($name)) ? $p->value() : $default;
    }

    /**
     * Returns the value of the named configuration variable, throwing an
     * exception if it is not set
     *
     * @param string $name A configuration variable name
     * @return string
     * @throws CodeRage\Error if the variable is not set
     */
    public final function getRequiredProperty($name): string
    {
        $p = $this->lookupProperty($name);
        if ($p === null || !$p->isSet_()) {
            throw new
                \CodeRage\Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'message' => "The config variable '$name' is not set"
                ]);
        }
        return $p->value();
    }

    /**
     * Returns the keys of this property bundle, as an array of strings.
     *
     * @return array
     */
    function propertyNames(): array { return array_keys($this->properties); }

    /**
     * Returns the named property, or null if no such property exists.
     *
     * @param string $name
     * @return CodeRage\Build\Config\Property
     */
    function lookupProperty($name): ?Property
    {
        return isset($this->properties[$name]) ?
            $this->properties[$name] :
            null;
    }

    /**
     * Adds the named property
     *
     * @param CodeRage\Build\Config\Property $property
     * @throws CodeRage\Error if a property with the same name already exists
     */
    function addProperty(Property $property): void
    {
        $name = $property->name();
        if (isset($this->properties[$name]))
            throw new
                Error([
                    'status' => 'OBJECT_EXISTS',
                    'message' => "The property '$name' already exists"
                ]);
        $this->properties[$name] = $property;
    }

    static function validate(ExtendedConfig $config): void
    {
        foreach ($config->propertyNames() as $name) {
            $property = $config->lookupProperty($name);
            if ($property->required() && !$property->isSet())
                throw new
                    \CodeRage\Error([
                        'status' => 'CONFIGURATION_ERROR',
                        'message' =>
                            "Missing value for required property '$name' " .
                            "specified at " . $property->specifiedAt()
                    ]);
        }
    }
}
