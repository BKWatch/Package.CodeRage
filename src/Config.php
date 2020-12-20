<?php

/**
 * Defines the class CodeRage\Config
 *
 * File:        CodeRage/Config.php
 * Date:        Mon Dec  7 01:47:24 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use Exception;
use CodeRage\Util\Args;

/**
 * Provides access to configuration variables
 */
final class Config {

    /**
     * @var string
     */
    public const PROJECT_CONFIG = 'project.xml';

    /**
     * Consrtructs an instance of CodeRage\Config
     *
     * @param array $properties The associative array of properties; if omitted,
     *   the propertiy values will be copied from the project configuration; may
     *   not specify values for the property "project_root"
     * @param CodeRage\Config $default An instance of CodeRage\Config whose
     *   collection of properties will be used to supply values for properties
     *   not defined in $properties; ignored if $properties is null
     * @throws Exception if $properties or $default is invalid
     */
    public function __construct($properties = null, $default = null)
    {
        if (self::$values === null)
            self::load();
        if ($properties !== null) {
            Args::check($properties, 'map[string]', 'properties');
            if ($default !== null) {
                Args::check($default, 'CodeRage\Config', 'default configuration');
                $this->properties = $properties + $default->properties;
            } else {
                $properties['project_root'] = self::$values['project_root'];
                $this->properties = $properties;

            }
        } else {
            $this->properties = self::$values;
        }
        $this->builtin = $properties === null;
    }

        /*
         * Methods for accessing configuration properties
         */

    /**
     * Returns true if the named configuration variable has been assigned a
     * value
     *
     * @param string $name A configuration variable name
     * @return boolean
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * Returns the value of the named configuration variable, or the given
     * default value is the variable is not set
     *
     * @param string $name A configuration variable name
     * @param string $default The default value
     * @return string
     */
    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ?
            $this->properties[$name] :
            $default;
    }

    /**
     * Returns the value of the named configuration variable, throwing an
     * exception if it is not set
     *
     * @param string $name A configuration variable name
     * @return string
     * @throws Exception if the variable is not set
     */
    public function getRequiredProperty($name)
    {
        if (!array_key_exists($name, $this->properties))
            throw new Exception("The config variable '$name' is not set");
        return $this->properties[$name];
    }

    /**
     * Returns a list of the names of all configuration variables
     *
     * @return array
     */
    public function propertyNames()
    {
        return array_keys($this->properties);
    }

    /**
     * Returns true if this instance is a copy of the project configuration
     *
     * @return boolean
     */
    public function builtin() { return $this->builtin; }

    /**
     * Returns the project root directory
     *
     * @return string
     */
    public static function projectRoot(): string
    {
        if (self::$projectRoot === null) {
            for ($dir = getcwd() ; ; $dir = $parent) {
                if (file_exists(File::join($dir, self::PROJECT_CONFIG))) {
                    self::$projectRoot = $dir;
                    break;
                }
                if (($parent = dirname($dir)) == $dir) {
                    break;
                }
            }
            if (self::$projectRoot === null) {
                throw new \Exception(
                    "Can't determine project root: no project configuration " .
                        "found in current directory or its ancestors"
                );
            }
        }
        return self::$projectRoot;
    }

        /*
         * Methods for accessing the current configuration
         */

    /**
     * Returns the current configuration
     *
     * @return CodeRage\Config
     */
    public static function current()
    {
        if (self::$current === null)
            self::$current = new Config;
        return self::$current;
    }

    /**
     * Replaces the current configuration
     *
     * @param CodeRage\Config $current The new configuration
     * @return CodeRage\Config The previous configuration
     */
    public static function setCurrent(Config $current)
    {
        if (!$current instanceof Config)
            throw new
                Exception(
                    'Invalid configuration: expected CodeRage\Config; found ' .
                    self::getType($current)
                );
        $prev = self::$current;
        self::$current = $current;
        return $prev;
    }

    /**
     * Loads configuration settings
     */
    private static function load()
    {
        $projectRoot = self::projectRoot();
        self::$values = ['project_root' => $projectRoot];
        if ( $projectRoot !== null &&
             file_exists($path = "$projectRoot/.coderage/config.php") )
        {
            $config = include($path);
            foreach ($config as $n => $v)
                self::$values[$n] = $v;
        }
    }

    /**
     * Returns the type of the given value, for use in error messages
     *
     * @param mixed $value The value
     * @return string
     */
    private static function getType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }

    /**
     * The project root directory
     *
     * @var string
     */
    private static $projectRoot;

    /**
     * Associative array mapping configuration variable names to built-in values
     *
     * @var array
     */
    private static $values;

    /**
     * The currently installed configuration
     *
     * @var CodeRage\Config
     */
    private static $current;

    /**
     * Maps property names to values
     *
     * @var array
     */
    private $properties;

    /**
     * true if this instance is a copy of the project configuration
     *
     * @var boolean
     */
    private $builtin;
}
