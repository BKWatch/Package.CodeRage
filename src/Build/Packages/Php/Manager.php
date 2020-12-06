<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\Manager.
 *
 * File:        CodeRage/Build/Packages/Php/Manager.php
 * Date:        Mon Jan 28 15:47:08 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Php;

use CodeRage\Build\Packages\Package;
use CodeRage\Error;
use function CodeRage\File\isAbsolute;
use CodeRage\Log;

/**
 * @ignore
 */
require_once('CodeRage/File/findExecutable.php');
require_once('CodeRage/File/isAbsolute.php');
require_once('CodeRage/Util/escapeExecutable.php');
require_once('CodeRage/Util/escapeShellArg.php');

/**
 * Manages instalation and uninstallation of PHP and PHP extensions.
 */
class Manager extends \CodeRage\Build\Packages\Manager {

    /**
     * Constructs a CodeRage\Build\Packages\Php\Manager.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns "php."
     *
     * @return string
     */
    function name() { return 'php'; }

    /**
     * Returns 'php'.
     *
     * @return string
     */
    function libraryFileExtension()
    {
        return 'php';
    }

    /**
     * Returns a list of the PHP installations on the local system meeting
     * the given requirements.
     *
     * @param array $properties an associative array of requirements; the
     * type of requirements depends on the framework. Possible
     * keys:
     * <ul>
     * <li>binaryPath</li>
     * <li>iniPath</li>
     * <li>minVersion</li>
     * <li>maxVersion</li>
     * </ul>
     * @return array An array of instances of CodeRage\Build\Packages\Configuration
     * @throws CodeRage\Error
     */
    function lookupConfigurations(array $properties = [])
    {
        // Find list of candidate executables
        $search = isset($properties['binaryPath']) ?
            $properties['binaryPath'] :
            null;
        $programs = \CodeRage\File\findExecutable('php', $search);

        // Construct list of configurations
        $iniPath = isset($properties['iniPath']) ?
            $properties['iniPath'] :
            null;
        $minVersion = isset($properties['minVersion']) ?
            $properties['minVersion'] :
            null;
        $maxVersion = isset($properties['maxVersion']) ?
            $properties['maxVersion'] :
            null;
        $configs = [];
        foreach ($programs as $p) {

            // Check version
            $esc =  \CodeRage\Util\escapeExecutable($p);
            $output = $this->runCommand("$esc -qv");
            $match = null;
            if (!preg_match('/^PHP ([0-9.]+)/', $output, $match))
                throw new
                    Error(['message' =>
                        "Failed determining PHP version for '$p'"
                    ]);
            $version = $match[1];
            if ( ($minVersion && version_compare($version, $minVersion)) < 0 ||
                 ($maxVersion && version_compare($version, $maxVersion)) > 0 )
            {
                continue;
            }

            // Check config file path
            $command = "$esc -qr \"echo get_cfg_var('cfg_file_path');\"";
            $path = $this->runCommand($command);
            if (!file_exists($path))
                continue;
            if ($iniPath !== null && realpath($path) != realpath($iniPath))
                continue;

            // Construct configuration
            $configs[] =
                new Configuration(
                        $this, $version, $p
                    );
        }

        return $configs;
    }

    /**
     * Returns a string providing enough info to uniquely identify the
     * indicated framework configuration in most cases.
     *
     * @param CodeRage\Build\Packages\Configuration $config
     * @return string
     * @throws CodeRage\Error
     */
    function configurationId(\CodeRage\Build\Packages\Configuration $config)
    {
        return $config->binaryPath();
    }

    /**
     * Returns an empty array.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @return array An empty array
     */
    function lookupChannels(\CodeRage\Build\Packages\Configuration $config)
    {
            return [];
    }

    /**
     * Returns the list of installed PHP extensions meeting the given
     * requirements.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $properties An associative array of requirements; the
     * type of requirements depends on the framework. Possible
     * keys:
     * <ul>
     * <li>name</li>
     * </ul>
     * @return array A list of instances of CodeRage\Build\Packages\Package
     */
    function lookupPackages(
        \CodeRage\Build\Packages\Configuration $config, array $properties = [])
    {
        $output =
            $config->runCommand(
                '-qr "echo join(\",\", get_loaded_extensions());"'
            );
        $extensions = explode(',', $output);
        $names = !isset($properties['name']) ?
            $extensions :
            ( in_array($properties['name'], $extensions) ?
                  [$properties['name']] :
                  [] );
        $packages = [];
        foreach ($names as $n)
            $packages[] = new Package($n, null, null);
        return $packages;
    }

    /**
     * Returns false.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param string $name The package name.
     * @param array $properties an associative array of requirements; the
     * type of requirements depends on the framework. Possible $requirements
     * keys:
     * <ul>
     * <li>name</li>
     * </ul>
     * @return boolean true if the named extension is present in the
     * extension directory, the extension was not previously enabled, and the
     * .ini file has been updated successfully.
     * @throws CodeRage\Error
     */
    function installPackage(
        \CodeRage\Build\Packages\Configuration $config, $name,
        array $properties = [] )
    {
        // Check whether extension is already enabled
        if (sizeof($this->lookupPackages($config, ['name' => $name])))
            return false;

        // Check whether binary is available
        $dir = $this->getConfigurationProperty($config, 'extension_dir');
        if (!isAbsolute($dir))
            $dir = dirname($config->binaryPath()) . '/' . $dir;
        $file =
            (PHP_SHLIB_SUFFIX == 'dll' ? 'php_' : '') .
            $name . '.' . PHP_SHLIB_SUFFIX;
        $path = "$dir/$file";
        if (!file_exists($path))
            throw new
                Error(['message' =>
                    "Failed installing extension '$name': " .
                    "file '$file' not found"
                ]);

        // Enable extension:
        $config->ini()->insertExtension($name);
        $config->ini()->save();
        return true;
    }

    /**
     * Uninstalls the given package.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param CodeRage\Build\Packages\Package $package
     * @return boolean true if the package was uninstalled.
     * @throws CodeRage\Error
     */
    function uninstallPackage(
        \CodeRage\Build\Packages\Configuration $config,
        Package $package )
    {
        // Check whether extension is already disabled
        $name = $package->name();
        if (sizeof($this->lookupPackages($config, ['name' => $name])) == 0)
            return false;

        // Enable extension:
        $config->ini()->disableExtension($name);
        $config->ini()->save();
    }

    /**
     * Returns true if the specified library file is in the current framework's
     * library search path.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $dir A library file pathname.
     * @return boolean
     * @throws CodeRage\Error If an error occurs or if the operation is not supported.
     */
    function inLibrarySearchPath(
        \CodeRage\Build\Packages\Configuration $config, $path)
    {
        $path = addcslashes($path, "'");
        $code =
            "function e(\$a,\$b) { echo 0; exit; } " .
            "set_error_handler('e', E_WARNING); " .
            "if (include('$path')) { echo 1; }";
        $command = "-r " . \CodeRage\Util\escapeShellArg($code);
        return (bool) $config->runCommand($command);
    }

    /**
     * Adds the specified directory to the include_path directive in php.ini.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $dir A directory pathname.
     * @throws CodeRage\Error If an error occurs or if the operation is not supported.
     */
    function addToLibrarySearchPath(
        \CodeRage\Build\Packages\Configuration $config, $dir)
    {
        try {
            $dir = realpath($dir);
            $ini = $config->ini();
            if (!$ini) {
                if ($str = $this->log()->getStream(Log::ERROR))
                    $str->write(
                        "Failed updating PHP include_path to include the " .
                        "directory '$dir': no php.ini loaded. Please " .
                        "update php.ini manually."
                    );
                    return;
            }
            if ($includePath = $ini->lookupDirective('include_path')) {
                $paths = [$dir];
                foreach ($includePath->value() as $v) {
                    if (!isAbsolute($v)) {
                        $paths[] = $v;
                    } elseif (file_exists($v)) {
                        $canon = realpath($v);
                        if ($canon != $dir)
                            $paths[] = $canon;
                    } else {
                          $paths[] = $v;
                    }
                }
                $ini->insertDirective('include_path', $paths);
            } else {
                $ini->insertDirective('include_path', [$dir]);
            }
            $ini->save();
        } catch (\Throwable $e) {
            if ($str = $this->log()->getStream(Log::ERROR)) {
                $handler = new \CodeRage\Util\ErrorHandler;
                $str->write(
                    $handler->formatError(
                        "Failed updating PHP include_path to include the " .
                        "directory '$dir': $e. Please " .
                        "update php.ini manually."
                    )
                );
            }
        }
    }

    /**
     * Returns the value of the named configuration property.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param string $name The property name
     * @return mixed
     * @throws CodeRage\Error if the value cannot be retrieved.
     */
    function getConfigurationProperty(
        \CodeRage\Build\Packages\Configuration $config, $name)
    {
        if (!preg_match('/^[_a-z][_a-z0-9]$/i', $name))
            throw new Error(['message' => "Invalid configuration property: $name"]);
        $output = $config->runCommand("-qr \"echo ini_get(\"$name\");\"");
        $d = IniDirective::create($name);
        return $d->fromString($output);
    }

    /**
     * Sets the value of the named configuration property.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param string $name The property name
     * @param mixed $value The property value
     * @throws CodeRage\Error if the value cannot be set.
     */
    function setConfigurationProperty(
        \CodeRage\Build\Packages\Configuration $config, $name, $value)
    {
        if ($this->getConfigurationProperty($config, $name) !== $value) {
            $config->ini()->insertDirective($name, $value);
            $config->ini()->save();
        }
    }
}
