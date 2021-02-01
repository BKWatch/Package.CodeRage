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

use CodeRage\Build\BuildConfig;
use CodeRage\Build\Property;
use CodeRage\Error;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Build\BuildConfig based on an associative array
 * of instances of CodeRage\Build\Config\Property.
 */
class Basic implements BuildConfig {

    /**
     * An associative array of instances of CodeRage\Build\Config\Property, indexed by
     * name.
     *
     * @var array
     */
    private $properties = [];

    /**
     * Constructs a CodeRage\Build\Config\Basic from an associative array
     * mapping property names to instances of CodeRage\Build\Property
     *
     * @param array $properties
     */
    function __construct($properties = [])
    {
        Args::check($properties, 'map[CodeRage\Build\Property]', 'properties');
        foreach ($properties as $n => $p)
            $this->addProperty($n, $p);
    }

    public final function hasProperty(string $name): bool
    {
        return $this->lookupProperty($name) !== null;
    }

    public final function getProperty(string $name, ?string $default = null): ?string
    {
        return ($p = $this->lookupProperty($name)) ? $p->value() : $default;
    }

    public final function getRequiredProperty(string $name): string
    {
        $p = $this->lookupProperty($name);
        if ($p === null) {
            throw new
                \CodeRage\Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'message' => "The config variable '$name' is not set"
                ]);
        }
        return $p->value();
    }

    public function propertyNames(): array
    {
        return array_keys($this->properties);
    }

    /**
     * Returns the named property, or null if no such property exists.
     *
     * @param string $name
     * @return CodeRage\Build\Config\Property
     */
    public function lookupProperty(string $name): ?Property
    {
        return isset($this->properties[$name]) ?
            $this->properties[$name] :
            null;
    }

    /**
     * Adds the named property
     *
     * @param string $name The property name
     * @param CodeRage\Build\Property $property
     * @throws Exception if a property with the same name already exists
     */
    public function addProperty(string $name, Property $property): void
    {
        if (isset($this->properties[$name]))
            throw new \Exception("The property '$name' already exists");
        $this->properties[$name] = $property;
    }
}
