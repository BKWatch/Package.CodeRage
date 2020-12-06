<?php

/**
 * Defines the class CodeRage\Build\Packages\Test\Manager.
 *
 * File:        CodeRage/Build/Packages/Test/Manager.php
 * Date:        Sat Mar 21 14:10:46 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Test;

use CodeRage\Build\Run;
use CodeRage\Error;
use function CodeRage\Xml\firstChildElement;

/**
 * @ignore
 */
require_once('CodeRage/Xml/childElements.php');
require_once('CodeRage/Xml/firstChildElement.php');
require_once('CodeRage/Xml/loadDom.php');

/**
 * Implementation of CodeRage\Build\Packages\Manager used for testing.
 */
class Manager extends \CodeRage\Build\Packages\Manager {

    /**
     * A list of instances of CodeRage\Build\Packages\Test\Configuration.
     *
     * @var array
     */
    private static $configurations = [];

    /**
     * Constructs a CodeRage\Build\Packages\Test\Manager.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function __construct(Run $run)
    {
        parent::__construct($run);
    }

    /**
     * Returns 'test'.
     *
     * @return string
     */
    function name() { return 'test'; }

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
        $minVersion = isset($properties['minVersion']) ?
            $properties['minVersion'] :
            null;
        $maxVersion = isset($properties['maxVersion']) ?
            $properties['maxVersion'] :
            null;
        $configurations = [];
        foreach (self::$configurations as $c) {
            if ( ( !$minVersion ||
                   $this->versionCompare($minVersion, $c->version()) <= 0 ) &&
                 ( !$maxVersion ||
                   $this->versionCompare($c->version, $maxVersion) <= 0 ) )
            {
                $configurations[] = $c;
            }
        }
        return $configurations;
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
        $minVersion = isset($properties['minVersion']) ?
            $properties['minVersion'] :
            null;
        $maxVersion = isset($properties['maxVersion']) ?
            $properties['maxVersion'] :
            null;
        foreach (self::$configurations as $c) {
            if ( ( !$minVersion ||
                   $this->versionCompare($minVersion, $c->version()) <= 0 ) &&
                 ( !$maxVersion ||
                   $this->versionCompare($c->version, $maxVersion) <= 0 ) )
            {
                return $c;
            }
        }
        $message =
            'Failed installing test package framework' .
            self::printRequirements($properties);
        if (sizeof(self::$configurations)) {
            $versions =
                array_map(
                    function($c) { return $c->version(); },
                    self::$configurations
                );
            $message .= "; available versions: " . join(', ', $versions);
        }
        throw new Error(['message' => $message]);
    }

    /**
     * Returns the list of installed channels.
     *
     * @param CodeRage\Build\Packages\Configuration $config The current framework
     * configuration.
     * @return array A list of instances of CodeRage\Build\Packages\Channel.
     */
    function lookupChannels(\CodeRage\Build\Packages\Configuration $config)
    {
        return $config->installedChannels();
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
        \CodeRage\Build\Packages\Configuration $config,
        \CodeRage\Build\Packages\Channel $channel )
    {
        foreach ($config->installedChannels() as $ch)
            if ($ch->url() == $channel->url())
                return false; // Channel already installed
        foreach ($config->availableChannels() as $ch)
            if ($ch->url() == $channel->url()) {
                $config->addInstalledChannel($ch);
                return true;
            }
        $message = 'Failed installing channel: ' . $channel->url();
        if (sizeof($config->availableChannels())) {
            $channels =
                array_map(
                    function($c) { return $c->url(); },
                    $config->availableChanels()
                );
            $message .= "; available channels: " . join(', ', $channels);
        } else {
            $message .= "; channel not available";
        }
        throw new Error(['message' => $message]);
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
        \CodeRage\Build\Packages\Configuration $config, array $properties = [])
    {
        return $this->lookupPackagesImpl($config, $properties, true);
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
     * <li>name</li>
     * <li>minVersion</li>
     * <li>maxVersion</li>
     * <li>channel</li>
     * </ul>
     * @return boolean true if the package was installed.
     * @throws CodeRage\Error
     */
    function installPackage(
        \CodeRage\Build\Packages\Configuration $config, $name,
        array $properties = [] )
    {
        $properties['name'] = $name;
        if (sizeof($this->lookupPackages($config, $properties)))
            return false; // Package already installed

        // Recursively install packages and their dependencies
        $stack = [$properties];
        while (sizeof($stack)) {
            $item = array_pop($stack);
            if ($item instanceof \CodeRage\Build\Packages\Package) {

                // Dependencies have already been processed
                $config->addInstalledPackage($item);

            } else {

                // Check if package is available.
                $packages = $this->lookupPackagesImpl($config, $item, false);
                if (!sizeof($packages)) {
                    $name = $item['name'];
                    unset($item['name']);
                    $message =
                        "Failed installing package '$name'" .
                        self::printRequirements($item);
                    $available =
                        $this->lookupPackagesImpl(
                            $config, ['name' => $name], false
                         );
                    if (sizeof($available)) {
                        $versions =
                            array_map(
                                function($p) { return $p->version(); },
                                $available
                            );
                        $message .=
                            "; available versions: " .
                            join(', ', $versions);
                    } else {
                        $message .= ': package not available';
                    }
                    throw new Error(['message' => $message]);
                }
                $package = $packages[0];

                // If package has a channel, attempt to install it
                if ($channel = $package->channel()) {
                    $found = false;
                    foreach ($config->availableChannels() as $ch) {
                        if ($ch->url() == $channel) {
                            $found = true;
                            $config->addInstalledChannel($ch);
                            break;
                        }
                    }
                    if (!$found) {
                        $message =
                            "Failed installing channel $channel: " .
                            "channel not avialable";
                        throw new Error(['message' => $message]);
                    }
                }

                // Push package object onto stack, followed by descriptions
                // of its dependencies
                $stack[] = $package;
                foreach ($package->dependencies() as $dep)
                    $stack[] = $dep;
            }
        }
        return true;
    }

    /**
     * Initializes the underlying collection of configurations.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $path The path to a project definition file containing
     * a 'testData' element.
     */
    static function initialize(Run $run, $path)
    {
        $dom = \CodeRage\Xml\loadDom($path);
        $targets = firstChildElement($dom->documentElement, 'targets');
        if (!$targets)
            throw new Error(['message' => "Missing 'targets' element"]);
        $config = firstChildElement($targets, 'testConfiguration');
        if (!$config)
            throw new Error(['message' => "Missing 'testConfiguration' element"]);
        $manager = new Manager($run);
        self::$configurations = [];
        foreach (\CodeRage\Xml\childElements($config, 'packageConfiguration') as $p)
            self::$configurations[] =
                Configuration::fromXml($manager, $p);
    }

    /**
     * Returns the list of installed or available packages meeting the given
     * requirements.
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
     * @param boolean $installed true if the collection of installed packages
     * should be searched.
     * @return array A list of instances of CodeRage\Build\Packages\Package
     */
    private function lookupPackagesImpl(
        \CodeRage\Build\Packages\Configuration $config,
        array $properties = [],
        $installed )
    {
        $search = $installed ?
            $config->installedPackages() :
            $config->availablePackages();
        $name = isset($properties['name']) ? $properties['name'] : null;
        $minVersion = isset($properties['minVersion']) ?
            $properties['minVersion'] :
            null;
        $maxVersion = isset($properties['maxVersion']) ?
            $properties['maxVersion'] :
            null;
        $channel = isset($properties['channel']) ?
            $properties['channel'] :
            null;
        $packages = [];
        foreach ($search as $p) {
            if ( ( !$name || $p->name() == $name ) &&
                 ( !$minVersion ||
                   $this->versionCompare($minVersion, $p->version()) <= 0 ) &&
                 ( !$maxVersion ||
                   $this->versionCompare($p->version(), $maxVersion) <= 0 ) &&
                 ( !$channel || $p->channel() === $channel ) )
            {
                $packages[] = $p;
            }
        }
        return $packages;
    }

    /**
     * Serializes the given requirements.
     *
     * @param array $properties
     */
    private static function printRequirements($properties)
    {
        if (!sizeof($properties))
            return '';
        $items = [];
        foreach ($properties as $n => $v)
            $items[] = "$n: $v";
        return ' (' . join('; ', $items) . ')';
    }
}
