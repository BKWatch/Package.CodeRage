<?php

/**
 * Defines the class CodeRage\Sys\Config\Array_
 *
 * File:        CodeRage/Sys/Config/Array_.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     Makeme_Config rights reserved
 */

namespace CodeRage\Sys\Config;

use CodeRage\Sys\ProjectConfig;
use CodeRage\Sys\Property;
use CodeRage\Error;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\ProjectConfig based on an associative array
 */
class Array_ implements ProjectConfig {

    /**
     * Constructs a CodeRage\Sys\Config\Basic from an associative array
     *
     * @param array $properties An associative array of strings
     * @param CodeRage\Sys\ProjectConfig $default An optional fallback
     *   configuration
     */
    function __construct($properties = [], ?ProjectConfig $default = null)
    {
        Args::check($properties, 'map[string]', 'properties');
        if ($default !== null) {
            foreach ($default->propertyNames() as $name) {
                if (!isset($properties[$name])) {
                    $properties[$name] = $default->getProperty($name);
                }
            }
        }
        $this->properties = $properties;
    }

    public final function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public final function getProperty(string $name, ?string $default = null): ?string
    {
        return $this->properties[$name] ?? $default;
    }

    public final function getRequiredProperty(string $name): string
    {
        $value = $this->properties[$name] ?? null;
        if ($value === null) {
            throw new
                \CodeRage\Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'message' => "The config variable '$name' is not set"
                ]);
        }
        return $value;
    }

    public function propertyNames(): array
    {
        return array_keys($this->properties);
    }

    /**
     * Throws an exception
     */
    public function lookupProperty(string $name): ?Property
    {
        throw new
            Error([
                'status' => 'UNSUPPORTED_OPERATION',
                'message' =>
                    "The method lookupProperty() is unavailable at runtime"
            ]);
    }

    /**
     * Throws an exception
     */
    public function addProperty(string $name, Property $property): void
    {
        throw new
            Error([
                'status' => 'UNSUPPORTED_OPERATION',
                'message' =>
                    "The method addProperty() is unavailable at runtime"
            ]);
    }

    /**
     * @var array
     */
    private $properties;
}
