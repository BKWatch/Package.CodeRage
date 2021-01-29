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
use CodeRage\Error;

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
     * Constructs a CodeRage\Build\Config\Basic from a list of properties
     * or an instance of CodeRage\Build\BuildConfig
     *
     * @param mixed $properties An instance of CodeRage\Build\BuildConfig whose
     * properties will be copied or a list of instances of
     * CodeRage\Build\Config\Property.
     */
    function __construct($properties = [])
    {
        if (is_array($properties)) {
            foreach ($properties as $p)
                $this->addProperty($p);
        } elseif ($properties instanceof BuildConfig) {
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

    public final function hasProperty($name): bool
    {
        return $this->lookupProperty($name) !== null;
    }

    public final function getProperty($name, ?string $default = null): ?string
    {
        return ($p = $this->lookupProperty($name)) ? $p->value() : $default;
    }

    public final function getRequiredProperty($name): string
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
    public function lookupProperty($name): ?Property
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
    public function addProperty(Property $property): void
    {
        $name = $property->name();
        if (isset($this->properties[$name]))
            throw new \Exception("The property '$name' already exists");
        $this->properties[$name] = $property;
    }
}
