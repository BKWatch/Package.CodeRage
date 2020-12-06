<?php

/**
 * Defines the class CodeRage\Sys\Config\EnvironmentAware
 *
 * File:        CodeRage/Sys/Config/EnvironmentAware.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\SysConfig;

use stdClass;
use CodeRage\Error;
use CodeRage\Sys\ConfigInterface;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;

/**
 * Implementation of CodeRage\Sys\ConfigInterface sentistive to the values of
 * environment variables
 */
final class EnvironmentAware implements ConfigInterface, \JsonSerializable
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\Basic
     *
     * @param array $values An associative collection mapping configuration
     *   variable names to native data structures, with embedded strings having
     *   leading "%" characters treated as environment variable placeholders.
     *   An initial "%" character can be escaped by doubling it.
     */
    public function __construct($values)
    {
        if ($values instanceof stdClass) {
            $values = (array) $values;
        }
        Args::check($values, 'map', 'values');
        $this->values = self::parse($values);
    }

    public function hasProperty(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    public function getProperty(string $name, $default = null)
    {
        return array_key_exists($name, $this->values) ?
            self::eval($this->values[$name]) :
            $default;
    }

    public function getRequiredProperty(string $name)
    {
        if (array_key_exists($name, $this->values)) {
            return self::eval($this->values[$name]);
        } else {
            throw new Error([
                'status' => 'CONFIGURATION_ERROR',
                'details' => "The configuration variable '$name' is not set"
            ]);
        }
    }

    /**
     * Returns the underlying associative data structure with each environment
     * variable placeholder replaced by the result of invoking the given
     * callable
     *
     * @param callable $func A callable taking a single string argument,
     *   representing an environment variable name
     * @return array
     */
    public function apply(callable $func): array
    {
        return self::applyImpl($this->values, $func);
    }

    /**
     * Helper for apply
     */
    private static function applyImpl($property, callable $func)
    {
        $value = null;
        if (is_scalar($property) || $property === null) {
            $value = $property;
        } elseif ($property instanceof stdClass) {
            $value = new stdClass;
            foreach ($property as $n => $v) {
                $value->$n = self::applyImpl($v, $func);
            }
        } elseif (is_array($property)) {
            $value = [];
            foreach ($property as $n => $v) {
                $value[$n] = self::applyImpl($v, $func);
            }
        } else {
            $value = $func($property->var);
        }
        return $value;
    }

    private static function eval($property): array
    {
        return self::applyImpl($property, 'getenv');
    }

    /**
     * Helper method for the constructor
     */
    private static function parse($value)
    {
        $property = null;
        if (is_string($value)) {
            $len = strlen($value);
            if ($len == 0 || $value[0] != '%') {
                $property = $value;
            } elseif ($len > 1 && $value[1] == '%') {
                $property = substr($value, 1);
            } else {
                $property = self::newProperty(substr($value, 1));
            }
        } elseif (is_scalar($value) || $value === null) {
            $property = $value;
        } elseif ($value instanceof stdClass) {
            $property = new stdClass;
            foreach ($value as $n => $v) {
                $property->$n = self::parse($v);
            }
        } elseif (is_array($value)) {
            $property = [];
            foreach ($value as $n => $v) {
                $property[$n] = self::parse($v);
            }
        } else {
            throw new Error([
                'status' => 'UNEXPECTED_CONTENT',
                'details' =>
                    'Failed parsings configruation: expected native data ' .
                    'structure; found element with type ' . get_class($value)
            ]);
        }
        return $property;
    }

    private static function newProperty(string $var): object
    {
        return new class($var) {
            public function __construct(string $var)
            {
                $this->var = $var;
            }
            public $var;
        };
    }

    /**
     * @var array
     */
    private $values;
}
