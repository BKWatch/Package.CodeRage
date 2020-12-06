<?php

/**
 * Defines the class CodeRage\Config and the function autoload()
 *
 * File:        CodeRage/Build/Resource/CodeRage.php
 * Date:        Wed Dec 31 14:25:59 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use Exception;

/**
 * Provides access to configuration variables
 */
final class Config {

    /**
     * @var string
     */
    const DEFAULT_TOOLS_ROOT = '/usr/share/CodeRage-3.0';

    /**
     * @var string
     */
    const COMPOSER_AUTOLOAD_PATH = '.coderage/composer/autoload.php';

    /**
     * Consrtructs an instance of CodeRage\Config
     *
     * @param array $properties The associative array of properties; if omitted,
     *   the propertiy values will be copied from the project configuration; may
     *   not specify values for the properties "project_root" or "tools_root"
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
            if (!is_array($properties))
                throw new
                    Exception(
                        'Invalid properties array: expected array; found ' .
                        self::getType($properties)
                    );
            if (array_key_exists('project_root', $properties))
                throw new Exception('Invalid property name: project_root');
            if (array_key_exists('tools_root', $properties))
                throw new Exception('Invalid property name: tools_root');
            foreach ($properties as $n => $v)
                if (!is_string($v))
                    throw new
                        Exception(
                            'Invalid property value: expected string; found ' .
                            self::getType($v)
                        );
            if ($default !== null) {
                if (!$default instanceof Config)
                    throw new
                        Exception(
                            'Invalid default configuration: expected ' .
                            'CodeRage\\Config; found ' .
                            self::getType($default)
                        );
                $this->properties = $properties + $default->properties;
            } else {
                $properties['project_root'] = self::$values['project_root'];
                $properties['tools_root'] = self::$values['tools_root'];
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

        /*
         * Method called by the system at initialization
         */

    /**
     * Sets the project root directory
     */
    public static function bootstrap()
    {
        // Bootstrap at most once
        static $bootstrapped = false;
        if ($bootstrapped)
            return;
        $bootstrapped = true;

        // Search for root directory
        if (isset($_ENV['CODERAGE_PROJECT_ROOT'])) {
            self::$projectRoot = $_ENV['CODERAGE_PROJECT_ROOT'];
        } else {
            $cur = '';
            $next = getcwd();
            while ( $cur != $next &&
                    !is_file("$next/project.xml") &&
                    !is_file("$next/project.ini") )
            {
                $cur = $next;
                $next = dirname($next);
            }
            if ($cur != $next)
                self::$projectRoot = realpath($next);
        }

        // Load configuration settings
        self::load();

        // Add project root and tools root to include path
        $inc = explode(PATH_SEPARATOR, ini_get('include_path'));
        $config = Config::current();
        $tools = $config->getRequiredProperty('tools_root');
        if (!in_array($tools, $inc))
            array_unshift($inc, $tools);
        if (self::$projectRoot !== null) {
            if (!in_array(self::$projectRoot, $inc))
                array_unshift($inc, self::$projectRoot);
            $autoload = self::$projectRoot . '/' . self::COMPOSER_AUTOLOAD_PATH;
            if (file_exists($autoload) && is_file($autoload))
                require($autoload);
        }
        ini_set('include_path', join(PATH_SEPARATOR, $inc));

        // Register autoload
        spl_autoload_register('CodeRage\autoload', true, true);
    }

        /*
         * Deprecated interface
         */

    /**
     * Returns true if the named configuration variable has been assigned a
     * value (possibly null)
     *
     * @param string $name A configuration variable name
     * @return boolean
     * @deprecated
     */
    public static function defined($name)
    {
        self::deprecated();
        if (self::$values === null)
            self::load();
        return array_key_exists($name, self::$values);
    }

    /**
     * Returns the value of the named configuration variable
     *
     * @param string $name
     * @return mixed
     * @throws Exception if the variable has not been set
     * @deprecated
     */
    public static function get($name)
    {
        self::deprecated();
        if (self::$values === null)
            self::load();
        if (!array_key_exists($name, self::$values))
            throw new Exception("The config variable '$name' is not set");
        return self::$values[$name];
    }

    /**
     * Returns the value of the named configuration variable, if it has been
     * set, and the specified default value otherwise
     *
     * @param string $name The variable name
     * @param mixed $default The default value
     * @return mixed
     * @deprecated
     */
    public static function getIf($name, $default = null)
    {
        self::deprecated();
        if (self::$values === null)
            self::load();
        return array_key_exists($name, self::$values) ?
            self::$values[$name] :
            $default;
    }

    /**
     * Returns a list of the names of all configuration variables
     *
     * @return array
     * @deprecated
     */
    public static function properties()
    {
        self::deprecated();
        if (self::$values === null)
            self::load();
        return array_keys(self::$values);
    }

    /**
     * Causes configuration settings to be reloaded upon next access
     *
     * @deprecated
     */
    public static function clear()
    {
        self::$values = self::$current = null;
    }

    /**
     * Loads configuration settings
     */
    private static function load()
    {
        self::$values = [];
        self::$values['project_root'] = self::$projectRoot;
        self::$values['tools_root'] = isset($_ENV['CODERAGE_TOOLS_ROOT']) ?
            $_ENV['CODERAGE_TOOLS_ROOT'] :
            self::DEFAULT_TOOLS_ROOT;
        if ( self::$projectRoot !== null &&
             file_exists(self::$projectRoot . '/.coderage/config.php') )
        {
            include(self::$projectRoot . '/.coderage/config.php');
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
     * Triggers an error with error level E_USER_DEPRECATED
     */
    private static function deprecated()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $message = "CodeRage\\Config::{$trace[1]['function']} is deprecated";
        trigger_error($message, E_USER_DEPRECATED);
    }

    /**
     * The project root directory
     *
     * @var string
     */
    private static $projectRoot;

    /**
     * Associative array mapping configuration variable names to values.
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

function autoload($class)
{
    $parts = explode('\\', $class);
    $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
    while (true) {
        foreach ($paths as $p) {
            $file = $p . '/' . join('/', $parts) . '.php';
            if (file_exists($file) && is_readable($file)) {
                require_once($file);
                if (class_exists($class) || interface_exists($class))
                    return true;
            }
        }
        $last = $parts[count($parts) - 1];
        if ($last[strlen($last) - 1] == '_') {
            $parts[count($parts) - 1] = rtrim($last, '_');
        } else {
            break;
        }
    }
    return false;
}

Config::bootstrap();
