<?php

/**
 * Defines the class CodeRage\Build\BuildConfig
 *
 * File:        CodeRage/Build/BuildConfig.php
 * Date:        Thu Jan 01 18:33:48 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Array_;

/**
 * Stores information about past or current invocations of makeme
 */
class BuildConfig {

    /**
     * Date format for use by __toString(), in the format accepted by the
     * built-in date() function.
     *
     *@var string
     */
    const DATE_FORMAT = 'D M j, H:m:s T Y';

    /**
     * Constructs a CodeRage\Build\BuildConfig.
     *
     * @param int $timestamp The time this configuration was created or
     *   saved, as a UNIX timestamp
     * @param boolean $status true if the most recently completed build action
     *   was successful
     * @param array $commandLineProperties An associative array of
     *   configuration variables specified on the command line
     * @param array $modules The list of module names
     */
    public function __construct(
        $timestamp,
        $commandLineProperties,
        $modules)
    {
        $this->timestamp = $timestamp;
        $this->commandLineProperties = $commandLineProperties;
        $this->modules = $modules;
    }

    /**
     * Returns the time this configuration was created or saved, as a UNIX
     * timestamp
     *
     * @return int
     */
    function timestamp()
    {
        return $this->timestamp;
    }

    /**
     * Returns an associative array of configuration variables specified on
     * the command line
     *
     * @return array
     */
    function commandLineProperties()
    {
        return $this->commandLineProperties;
    }

    /**
     * Sets the associative array of configuration variables specified on the
     * command line.
     *
     * @param CodeRage\Build\ProjectConfig $config
     */
    function setCommandLineProperties(ProjectConfig $config)
    {
        $properties = [];
        foreach ($config->propertyNames() as $name) {
            $p = $config->lookupProperty($name);
            if ($p->setAt() == COMMAND_LINE)
                $properties[$name] = $p->value();
        }
        $this->commandLineProperties = $properties;
    }

    /**
     * Returns the list of modules names
     *
     * @return array
     */
    function modules()
    {
        return $this->modules;
    }

    /**
     * Sets the list of module names
     *
     * @param array $modules
     */
    function setModules(array $modules)
    {
         $this->modules = $modules;
    }

    /**
     * Returns the path to the project configuration file
     */
    function projectConfigFile()
    {
        return Config::projectRoot() . '/' . Config::PROJECT_CONFIG;
    }

    /**
     * Loads and returns the stored build configuration associated with the
     * given project; if no build configuration has been stored, returns an
     * instance of CodeRage\Build\BuildConfig will empty values.
     *
     * @param string $projectRoot The project root directory
     * @return CodeRage\Build\BuildConfig
     */
    static function load($projectRoot)
    {
        $path = "$projectRoot/.coderage/history.php";
        if (file_exists($path)) {
            $definition = include($path);
            return new BuildConfig(...$definition);
        } else {
            return new BuildConfig(0, [], []);
        }
    }

    /**
     * Saves this build configuration
     *
     * @param string $projectRoot The project root directory
     */
    function save($projectRoot)
    {
        $file = "$projectRoot/.coderage/history.php";
        File::generate($file, $this->definition(), 'php');
    }

    /**
     * Returns a PHP definition of this instance.
     *
     * @return string
     */
    function definition()
    {
        return
            "return\n" .
            "    [\n" .
            "        $this->timestamp,\n" .
            $this->formatArray($this->commandLineProperties, '        ') . ",\n" .
            $this->formatArray($this->modules, '        ') . "\n" .
            "    ];\n";
    }

    function __toString()
    {
        $result =
            "Last build: " . date(self::DATE_FORMAT) . "\n" .
            "Status: " . ($this->status ? 'success' : 'failure') . "\n";
        if (count($this->commandLineProperties)) {
            $result .= "Command-line configuration: \n";
            foreach ($this->commandLineProperties as $n => $v)
                $result .= "  $n=" . Error::formatValue($v) . "\n";
        }
        if (count($this->modules)) {
            $result .= "Modules: \n";
            foreach ($this->modules as $m)
                $result .= "  $m\n";
        }
        return $result;
    }

    /**
     * Returns the given array of strings formatted as a PHP expression
     *
     * @param array $values
     * @param string $indent
     */
    private function formatArray(array $values, string $indent)
    {
        $indexed = Array_::isIndexed($values);
        $items = [];
        foreach ($values as $n => $v) {
            $items[] = $indexed ?
                $this->formatString($v) :
                $this->formatString($n) . ' => ' . $this->formatString($v);
        }
        return "{$indent}[\n$indent    " . join(",\n$indent    ", $items) .
               "\n$indent]";
    }

    /**
     * Returns a PHP expression evaluating to the given string
     *
     * @param string $value
     * @return string
     */
    private function formatString(string $value)
    {
        return strlen($value) == 0 || ctype_print($value) ?
            "'" . addcslashes($value, "\\'") . "'" :
            "base64_decode('" . base64_encode($value) . "')";
    }

    /**
     * The time this configuration was created or saved, as a UNIX timestamp
     *
     * @var int
     */
    private $timestamp;

    /**
     * An associative array of configuration variables specified on the
     * command line
     *
     * @var array
     */
    private $commandLineProperties;

    /**
     * The list of module names
     *
     * @var array
     */
    private $modules;
}
