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

use CodeRage\Build\ProjectConfig;
use CodeRage\Error;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Build\ProjectConfig based on an associative array
 */
class Array_ implements ProjectConfig {

    /**
     * Constructs a CodeRage\Build\Config\Basic from a list of properties
     * or an instance of CodeRage\Build\Config\Array_
     *
     * @param array $properties An associative array
     * @param CodeRage\Build\ProjectConfig $default An optional fallback
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

    public final function hasProperty($name): bool
    {
        return isset($this->properties[$name]);
    }

    public final function getProperty($name, ?string $default = null): ?string
    {
        return $this->properties[$name] ?? $default;
    }

    public final function getRequiredProperty($name): string
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
     * @var array
     */
    private $properties;
}
