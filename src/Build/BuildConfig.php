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

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Text;

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
     * An instance of CodeRage\Build\Info
     *
     * @var array
     */
    private $projectInfo;

    /**
     * Constructs a CodeRage\Build\BuildConfig.
     *
     * @param int $timestamp The time this configuration was created or
     *   saved, as a UNIX timestamp
     * @param boolean $status true if the most recently completed build action
     *   was successful
     * @param array $commandLineProperties An associative array of
     *   configuration variables specified on the command line
     * @param array $projectInfo An instance of CodeRage\Build\Info
     */
    public function __construct(
        $timestamp,
        $status,
        $commandLineProperties,
        $projectInfo)
    {
        $this->timestamp = $timestamp;
        $this->status = $status;
        $this->commandLineProperties = $commandLineProperties;
        $this->projectInfo = $projectInfo;
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
     * Returns true if the most recently completed build action was successful.
     *
     * @param boolean
     */
    function status()
    {
        return $this->status;
    }

    /**
     * Sets the status of the most recently completed build action.
     *
     * @param boolean
     */
    function setStatus($status)
    {
        $this->status = $status;
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
     * Returns an instance of CodeRage\Build\Info
     *
     * @return CodeRage\Build\Info
     */
    function projectInfo()
    {
        return $this->projectInfo;
    }

    /**
     * Sets the instance o0f CodeRage\Build\Info, if any, associated with the current
     * run of the build system.
     *
     * @return CodeRage\Build\Info
     */
    function setProjectInfo($info)
    {
        $this->projectInfo = $info;
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
        $file = "$projectRoot/.coderage/history.php";
        if (!file_exists($file))
            return new BuildConfig(0, null, false, [], null);
        File::checkReadable($file);
        global $config;  // set in $file
        include($file);
        if (!isset($config) || !is_array($config) || sizeof($config) != 15)
            throw new
                Error([
                    'message' =>
                        "The file '$file' contains no build configuration"
                ]);
        return new
            BuildConfig($config[0], $config[1], $config[2], null);
    }

    /**
     * Saves this build configuration.
     *
     * @param string $projectRoot The project root directory
     */
    function save($projectRoot)
    {
        $file = "$projectRoot/.coderage/history.php";
        $definition = $this->definition();
        $content = "\$config = $definition;\n";
        File::generate($file, $content, 'php');
    }

    /**
     * Returns a PHP definition of this instance.
     *
     * @return string
     */
    function definition()
    {
        return "[$this->timestamp," .
               Error::formatValue($this->status) . ',' .
               $this->printObject($this->commandLineProperties) . ']';
    }

    function __toString()
    {
        $result =
            "Last build: " . date(self::DATE_FORMAT) . "\n" .
            "Status: " . ($this->status ? 'success' : 'failure') . "\n";
        if (sizeof($this->commandLineProperties)) {
            $result .= "Command-line configuration: \n";
            foreach ($this->commandLineProperties as $n => $v)
                $result .= "  $n=" . Error::formatValue($v) . "\n";
        }
        return $result;
    }

    /**
     * formats the given path for use by __toString().
     *
     * @param string $path
     * @return string
     */
    function printInfo($value)
    {
        return $value !== null ? "$value\n" : "n/a\n";
    }

    /**
     * Returns a PHP definition of the specified value. Arrays must be pure
     * associative or pure indexed; objects must support a 'defintion' method.
     *
     * @param mixed $value A scalar, array, or object.
     * @return string
     */
    private function printObject($value)
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $n => $v)
                $items[] = is_int($n) ?
                    $this->printObject($v) :
                    $this->printObject($n) . '=>' . $this->printObject($v);
            return 'array(' . join(',', $items) . ')';
        } elseif (is_object($value)) {
            return $value->definition();
        } elseif (is_string($value)) {
            return ctype_print($value) ?
                "'" . addcslashes($value, "\\'") . "'" :
                "base64_decode('" . base64_encode($value) . "')";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif ($value === null) {
            return 'null';
        } else {
            return strval($value);
        }
    }
}
