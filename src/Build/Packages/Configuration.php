<?php

/**
 * Defines the class CodeRage\Build\Packages\Configuration.
 *
 * File:        CodeRage/Build/Packages/Configuration.php
 * Date:        Mon Jan 28 15:28:58 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages;

/**
 * Represents information about a particular installation of a framework.
 */
class Configuration {

    /**
     * The manager that loaded this configuration
     *
     * @var CodeRage\Build\Packages\Manager
     */
    private $manager;

    /**
     * The version string.
     *
     * @var string
     */
    private $version;

    /**
     * Constructs a CodeRage\Build\Packages\Configuration. Called only by instances
     * of CodeRage\Build\Packages\Manager
     *
     * @param CodeRage\Build\Packages\Manager $manager The framework manager.
     * @param string $version The version string.
     */
    function __construct(Manager $manager, $version)
    {
        $this->manager = $manager;
        $this->version = $version;
    }

    /**
     * Returns a version string.
     *
     * @return string
     */
    function version() { return $this->version; }

                    /*
                     * Convenience methods
                     */

    /**
     * Returns a string providing enough info to uniquely identify this
     * framework configuration in most cases.
     *
     * @return string
     * @throws CodeRage\Error
     */
    function configurationId()
    {
        return $this->manager->configurationId($this);
    }

    /**
     * Returns the list of installed channels.
     *
     * @return array A list of instances of CodeRage\Build\Packages\Channel.
     */
    function lookupChannels()
    {
        return $this->manager->lookupChannels($this);
    }

    /**
     * Installs the given channel, if channel installation is supported.
     *
     * @param CodeRage\Build\Packages\Channel $channel
     * @return boolean true if the channel was installed.
     * @throws CodeRage\Error
     */
    function installChannel(Channel $channel)
    {
        return $this->manager->installChannel($this, $channel);
    }


    /**
     * Uninstalls the given channel, if channel installation is supported.
     *
     * @param CodeRage\Build\Packages\Channel $channel
     * @return boolean true if the channel was uninstalled.
     * @throws CodeRage\Error
     */
    function uninstallChannel(Channel $channel)
    {
        return $this->manager->uninstallChannel($this, $channel);
    }

    /**
     * Returns the list of installed packages meeting the given requirements.
     *
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
    function lookupPackages(array $properties = [])
    {
        return $this->manager->lookupPackages($this, $properties);
    }

    /**
     * Installs the named package.
     *
     * @param string $name The package name.
     * @param array $properties An associative array of requirements; the
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
    function installPackage($name, array $properties = [])
    {
        return $this->manager->installPackage($this, $name, $properties);
    }

    /**
     * Uninstalls the given package.
     *
     * @param CodeRage\Build\Packages\Package $package.
     * @return boolean true if the package was uninstalled.
     * @throws CodeRage\Error
     */
    function uninstallPackage(Package $package)
    {
        return $this->manager->uninstallPackage($this, $package);
    }

    /**
     * Copys a file to the local package directory.
     *
     * @param array $paths A list of pathnames of file and directories
     * @param boolean $overwrite true if an existing file or directory with the
     * same name as one of the listed file or directory should be replaced.
     * @return int The number or files and directories removed.
     * @throws CodeRage\Error
     */
    function installLocalPackage($paths, $overwrite = true)
    {
        return $this->manager->installLocalPackage($this, $paths, $overwrite);
    }

    /**
     * Removes a file from the local package directory.
     *
     * @param array $paths A list of pathnames of file and directories, relative
     * to the local package directory.
     * @return int The number or files and directories removed.
     * @throws CodeRage\Error
     */
    function uninstallLocalPackage($paths)
    {
        return $this->manager->uninstallLocalPackage($this, $paths);
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
    function inLibrarySearchPath($path)
    {
        return $this->manager->inLibrarySearchPath($this, $path);
    }

    /**
     * Adds the specified directory to the current framework's search path for
     * library files.
     *
     * @param array $dir A directory pathname.
     * @throws CodeRage\Error If an error occurs or if the operation is not supported.
     */
    function addToLibrarySearchPath($dir)
    {
        $this->manager->addToLibrarySearchPath($this, $dir);
    }

    /**
     * Returns the value of the named configuration property.
     *
     * @param string $name The property name
     * @return mixed
     * @throws CodeRage\Error if the value cannot be retrieved.
     */
    function getConfigurationProperty($name)
    {
        return $this->manager->getConfigurationProperty($this, $name);
    }

    /**
     * Sets the value of the named configuration property.
     *
     * @param string $name The property name
     * @param mixed $value The property value
     * @return boolean true if the property value was updated.
     * @throws CodeRage\Error if an error occurs
     */
    function setConfigurationProperty($name, $value)
    {
        $this->manager->setConfigurationProperty($this, $name, $value);
    }

    /**
     * Returns the underlying framework manager.
     *
     * @return CodeRage\Build\Packages\Manager
     */
    function manager()
    {
        return $this->manager;
    }
}
