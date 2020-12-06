<?php

/**
 * Defines the class CodeRage\Build\Packages\Test\Configuration.
 *
 * File:        CodeRage/Build/Packages/Test/Configuration.php
 * Date:        Sat Mar 21 14:20:24 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Test;

use CodeRage\Build\Packages\Channel;
use CodeRage\Error;
use function CodeRage\Xml\childElements;

/**
 * @ignore
 */
require_once('CodeRage/Xml/childElements.php');
require_once('CodeRage/Xml/getAttribute.php');

/**
 * Subclass of CodeRage\Build\Packages\Configuration used for testing.
 */
class Configuration
    extends \CodeRage\Build\Packages\Configuration
{
    /**
     * The list of instances of CodeRage\Build\Packages\Channel that can be
     * successfully installed.
     *
     * @var array
     */
    private $availableChannels;

    /**
     * The list of instances of CodeRage\Build\Packages\Package that can be
     * successfully installed.
     *
     * @var array
     */
    private $availablePackages;

    /**
     * The list of instances of CodeRage\Build\Packages\Channel that have been
     * successfully installed.
     *
     * @var array
     */
    private $installedChannels = [];

    /**
     * The list of instances of CodeRage\Build\Packages\Package that have been
     * successfully installed.
     *
     * @var array
     */
    private $installedPackages = [];


    /**
     * Constructs a CodeRage\Build\Packages\Test\Configuration.
     *
     * @param CodeRage\Build\Packages\Test\Manager $manager The package manager.
     * @param string $version The version string.
     * @param array $availableChannels The list of instances of
     * CodeRage\Build\Packages\Channel that can be successfully installed.
     * @param array $availablePackages The list of instances of
     * CodeRage\Build\Packages\Package that can be successfully installed.
     */
    function __construct($manager, $version, $availableChannels,
        $availablePackages)
    {
        parent::__construct($manager, $version);
        $this->availableChannels = $availableChannels;
        $this->availablePackages = $availablePackages;
    }

    /**
     * Returns the list of instances of CodeRage\Build\Packages\Channel that can be
     * successfully installed.
     *
     * @return array
     */
    function availableChannels()
    {
        return $this->availableChannels;
    }

    /**
     * Returns the list of instances of CodeRage\Build\Packages\Package that can be
     * successfully  installed.
     *
     * @return array
     */
    function availablePackages()
    {
        return $this->availablePackages;
    }

    /**
     * Returns the list of instances of CodeRage\Build\Packages\Channel that have been
     * successfully installed.
     *
     * @return array
     */
    function installedChannels()
    {
        return $this->installedChannels;
    }

    /**
     * Returns the list of instances of CodeRage\Build\Packages\Package that have been
     * successfully installed.
     *
     * @return array
     */
    function installedPackages()
    {
        return $this->installedPackages;
    }

    /**
     * Adds an item to the list of URLs of channels that have been successfully
     * installed.
     *
     * @param CodeRage\Build\Packages\Channel $channel
     */
    function addInstalledChannel(Channel $channel)
    {
        $this->installedChannels[] = $channel;
    }

    /**
     * Adds an item to the list of instances of CodeRage\Build\Packages\Package that
     * have been successfully installed.
     *
     * @param CodeRage\Build\Packages\Test\Package $package
     */
    function addInstalledPackage(Package $package)
    {
        $this->installedPackages[] = $package;
    }

    /**
     * Returns an instance of CodeRage\Build\Packages\Test\Configuration constructed
     * by parsing the given 'packageConfiguration' element.
     *
     * @param CodeRage\Build\Packages\Test\Manager $manager
     * @param DOMElement $config
     */
    static function fromXml($manager, \DOMElement $config)
    {
        if (!$config->hasAttribute('version'))
            throw new Error(['message' => "Missing 'version' attribute"]);
        $version = $config->getAttribute('version');
        $channels = [];
        $packages = [];
        foreach (childElements($config, 'channel') as $ch) {
            if (!$ch->hasAttribute('url'))
                throw new Error(['message' => "Missing 'url' attribute"]);
            $channels[] =
                new Channel($ch->getAttribute('url'));
        }
        foreach (childElements($config, 'package') as $p) {
            if (!$p->hasAttribute('name'))
                throw new Error(['message' => "Missing 'name' attribute"]);
            if (!$p->hasAttribute('version'))
                throw new Error(['message' => "Missing 'version' attribute"]);
            $dependencies = [];
            foreach (childElements($p, 'dependsOn') as $d) {
                if (!$d->hasAttribute('name'))
                    throw new Error(['message' => "Missing 'name' attribute"]);
                $dep = ['name' => $d->getAttribute('name')];
                foreach (['minVersion', 'maxVersion', 'channel'] as $att)
                    if ($d->hasAttribute($att))
                        $dep[$att] = $d->getAttribute($att);
                $dependencies[] = $dep;
            }
            $packages[] =
                new Package(
                        $p->getAttribute('name'),
                        $p->getAttribute('version'),
                        \CodeRage\Xml\getAttribute($p, 'channel'),
                        $dependencies
                    );
        }
        return new
            Configuration(
                $manager, $version, $channels, $packages
            );
    }
}
