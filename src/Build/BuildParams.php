<?php

/**
 * Defines the class CodeRage\Build\BuildParams
 *
 * File:        CodeRage/Build/BuildParams.php
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
final class BuildParams {

    /**
     * Date format for use by __toString(), in the format accepted by the
     * built-in date() function.
     *
     *@var string
     */
    const DATE_FORMAT = 'D M j, H:m:s T Y';

    /**
     * Constructs a CodeRage\Build\BuildParams.
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
     * command line
     *
     * @param CodeRage\Build\BuildConfig $config
     */
    function setCommandLineProperties(BuildConfig $config)
    {
        $properties = [];
        foreach ($config->propertyNames() as $name) {
            $p = $config->lookupProperty($name);
            if ($p->setAt() === '[cli]')
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
     * Loads and returns the stored build parameters associated with the
     * given project; if no build parameters has been stored, returns an
     * instance of CodeRage\Build\BuildParams will empty values.
     *
     * @return CodeRage\Build\BuildParams
     */
    static function load()
    {
        $path = Config::projectRoot() . '/.coderage/history.php';
        if (file_exists($path)) {
            $definition = include($path);
            return new BuildParams(...$definition);
        } else {
            return new BuildParams(0, [], []);
        }
    }

    /**
     * Saves this build parameters
     */
    function save()
    {
        $path = Config::projectRoot() . '/.coderage/history.php';
        File::generate($path, $this->definition(), 'php');
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
            "LAST BUILD:\n\n  " . date(self::DATE_FORMAT) . "\n";
        if (count($this->modules)) {
            $result .= "\nMODULES:\n\n";
            foreach ($this->modules as $m)
                $result .= "  $m\n";
        }
        if (count($this->commandLineProperties)) {
            $result .= "\nCLI CONFIG:\n\n";
            foreach ($this->commandLineProperties as $n => $v)
                $result .= "  $n=" . $this->formatString($v) . "\n";
        }
        if (count($this->commandLineProperties)) {
            $result .= "\nCONFIG COMMAND:\n\ncrush config";
            foreach ($this->commandLineProperties as $n => $v)
                $result .= " --set $n=" . $this->formatString($v);
        }
        $result .= "\n";
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
