<?php

/**
 * Defines the class CodeRage\Build\Config\Builtin
 *
 * File:        CodeRage/Build/Config/Builtin.php
 * Date:        Mon Dec  7 01:47:24 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

use CodeRage\Build\Property;

/**
 * The built-in configuration
 */
final class Builtin implements \CodeRage\Build\ProjectConfig {

    /**
     * Consrtructs an instance of CodeRage\Build\Config\Builtin
     */
    public function __construct()
    {
        $path = \CodeRage\Config::projectRoot() . '/.coderage/config.php';
        \CodeRage\File::checkFile($path, 0b0100);
        $this->properties = include($path);
    }

    public final function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public final function getProperty(string $name, ?string $default = null): ?string
    {
        $value = $this->properties[$name] ?? null;
        if ($value !== null) {
            [$storage, $value] = $value;
            if ($storage == Property::LITERAL) {
                return $value;
            } elseif ($storage == Property::ENVIRONMENT) {
                return ($v = getenv($value)) !== false ? $v : '';
            } else {
                \CodeRage\File::checKFile($value, 0b0100);
                return file_get_contents($value);
            }
        } else {
            return $default;
        }
    }

    public final function getRequiredProperty(string $name): string
    {
        $value = $this->getProperty($name);
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
