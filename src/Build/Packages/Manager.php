<?php

/**
 * Defines the class CodeRage\Build\Packages\Manager.
 *
 * File:        CodeRage/Build/Packages/Manager.php
 * Date:        Mon Jan 28 15:47:08 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages;

use CodeRage\Error;
use CodeRage\Log;

/**
 * @ignore
 */
require_once('CodeRage/Util/os.php');
require_once('CodeRage/Util/system.php');

/**
 * Manages instalation and uninstallation of frameworks, packages, and channels.
 */
abstract class Manager {

    /**
     * A log with channel 'build'
     *
     * @var CodeRage\Log
     */
    private $log;

    /**
     * Constructs a CodeRage\Build\Packages\Manager.
     */
    protected function __construct() { }

    /**
     * Returns an instance of CodeRage\Log with channel 'build'
     *
     * @return CodeRage\Log
     */
    function log()
    {
        if (!$this->log) {
            $this->log = new Log('build');
        }
        return $this->log;
    }

    /**
     * Returns the framework name.
     *
     * @return string
     */
    abstract function name();

    /**
     * Returns the primary file extension used for library code. By default
     * returns NULL, indicating that the framework has no special file
     * extension.
     *
     * @return string
     */
    function libraryFileExtension()
    {
        return null;
    }

    /**
     * Returns a list of names of frameworks on which the current framework
     * depends.
     *
     * @return array
     */
    function dependencies() { return []; }

    /**
     * Compares two version strings, returning a value less than zero, equal to
     * zero, or greater than zero depending on whether the first string is
     * less than, equaul to, or greater than the second. If the the versions are
     * incomparable, returns null.
     *
     * By default, returns the result of calling the built-in version_compare()
     * function.
     *
     * @param string $lhs
     * @param string $rhs
     * @return int
     * @throws Exception if either version string is malformed.
     */
    function versionCompare($lhs, $rhs)
    {
        return version_compare($lhs, $rhs);
    }

    /**
     * Returns a list of the framework installations on the local system meeting
     * the given requirements.
     *
     * @param array $properties an associative array of requirements; the
     * type of requirements depends on the framework. Possible
     * keys:
     * <ul>
     * <li>binaryPath</li>
     * <li>minVersion</li>
     * <li>maxVersion</li>
     * </ul>
     * @return array An array of instances of CodeRage\Build\Packages\Configuration
     */
    function lookupConfigurations(array $properties = [])
    {
        return [];
    }

    /**
     * Installs the indicated framework configuration.
     *
     * @param array $properties an associative array of requirements; the
     * type of requirements depends on the framework. Possible
     * keys:
     * <ul>
     * <li>binaryPath</li>
     * <li>minVersion</li>
     * <li>maxVersion</li>
     * </ul>
     * @return CodeRage\Build\Packages\Configuration The installed framework.
     * @throws CodeRage\Error If the given properties do not sufficiently
     * specify a configuration, if they are inconsistent, or if an error
     * occurs during installation
     */
    function installConfiguration(array $properties = [])
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Uninstalls the indicated framework configuration.
     *
     * @param CodeRage\Build\Packages\Configuration $config
     * @return boolean true if the framework was uninstalled.
     * @throws CodeRage\Error If the given properties do not sufficiently
     * specify a configuration or if an error occurs during uninstallation
     */
    function uninstallConfiguration(Configuration $config)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Returns a string providing enough info to uniquely identify the
     * indicated framework configuration in most cases.
     *
     * @param CodeRage\Build\Packages\Configuration $config
     * @return string
     * @throws CodeRage\Error
     */
    function configurationId(Configuration $config)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Returns the list of installed channels.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @return array A list of instances of CodeRage\Build\Packages\Channel.
     */
    function lookupChannels(Configuration $config)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Installs the given channel, if channel installation is supported.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param CodeRage\Build\Packages\Channel $channel
     * @return boolean true if the channel was installed.
     * @throws CodeRage\Error
     */
    function installChannel(
        Configuration $config,
        Channel $channel )
    {
        throw new Error(['message' => 'Not implemented']);
    }


    /**
     * Uninstalls the given channel, if channel installation is supported.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param CodeRage\Build\Packages\Channel $channel
     * @return boolean true if the channel was uninstalled.
     * @throws CodeRage\Error
     */
    function uninstallChannel(
        Configuration $config,
        Channel $channel )
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Returns the list of installed packages meeting the given requirements.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $properties An associative array of requirements; the
     * type of requirements depends on the framework. Possible
     * keys:
     * <ul>
     * <li>name</li>
     * <li>minVersion</li>
     * <li>maxVersion</li>
     * <li>channel</li>
     * </ul>
     * @return array A list of instances of CodeRage\Build\Packages\Package
     */
    function lookupPackages(
        Configuration $config, array $properties = [])
    {
        return [];
    }

    /**
     * Installs the named package.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param string $name The package name.
     * @param array $properties an associative array of requirements; the
     * type of requirements depends on the framework. Possible $requirements
     * keys:
     * <ul>
     * <li>minVersion</li>
     * <li>maxVersion</li>
     * <li>channel</li>
     * </ul>
     * @return boolean true if the package was installed.
     * @throws CodeRage\Error
     */
    function installPackage(
        Configuration $config, $name,
        array $properties = [] )
    {
        return false;
    }

    /**
     * Uninstalls the given package.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param CodeRage\Build\Packages\Package $package.
     * @return boolean true if the package was uninstalled.
     * @throws CodeRage\Error
     */
    function uninstallPackage(
        Configuration $config,
        Package $package )
    {
        return false;
    }

    /**
     * Copys a file to the local package directory.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $paths A list of pathnames of file and directories
     * @param boolean $overwrite true if an existing file or directory with the
     * same name as one of the listed file or directory should be replaced.
     * @throws CodeRage\Error
     */
    function installLocalPackage(
        Configuration $config, $paths, $overwrite)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Removes a file from the local package directory.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $paths A list of pathnames of file and directories, relative
     * to the local package directory.
     * @throws CodeRage\Error
     */
    function uninstallLocalPackage(
        Configuration $config, $paths)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Returns true if the specified library file is in the current framework's
     * library search path.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $dir A library file pathname.
     * @return boolean
     *
     * @todo Revise specification so that directories are included.
     */
    function inLibrarySearchPath(
        Configuration $config, $path)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Adds the specified directory to the current framework's search path for
     * library files.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param array $dir A directory pathname.
     * @throws CodeRage\Error If an error occurs or if the operation is not supported.
     */
    function addToLibrarySearchPath(
        Configuration $config, $dir)
    {
        throw new Error(['message' => 'Not implemented']);
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
        Configuration $config, $name)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Sets the value of the named configuration property.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @param string $name The property name
     * @param mixed $value The property value
     * @return boolean true if the property value was updated.
     * @throws CodeRage\Error if an error occurs
     */
    function setConfigurationProperty(
        Configuration $config, $name, $value)
    {
        throw new Error(['message' => 'Not implemented']);
    }

    /**
     * Runs the given command and returns the standard output.
     *
     * @param string $command
     * @param boolean $redirectError true if standard error output should be
     * redirected to /dev/null or the equivalent.
     * @return string
     * @throws CodeRage\Error if an error occurs.
     */
    function runCommand($command, $redirectError = false)
    {
        if ($str = $this->log()->getStream(Log::DEBUG))
            $str->write("Running command $command");
        if ($redirectError)
            $command .= \CodeRage\Util\os() == 'posix' ?
                ' 2>/dev/null' :
                ' 2>NUL';
        return \CodeRage\Util\system($command);
    }

    /**
     * Creates the given diretory and its parent directories, if
     * they do not exist.
     *
     * @param mixed $mode A mode string or int to pass to mkdir; ignored on
     * Windows or if the directory already exists
     * @return boolean true if the directory exists after execution
     */
    protected function mkdirs($path, $mode = 0777)
    {
        return \CodeRage\File\mkdir($path, $mode);
    }

    /**
     * Removes the given file, and all the files it contains, if it is a
     * directory.
     *
     * @param string $path
     * @return boolean true if the file does not exist when execution completes.
     */
    protected function rm($path)
    {
        return \CodeRage\File\rm($path);
    }
}
