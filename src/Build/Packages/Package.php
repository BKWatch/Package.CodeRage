<?php

/**
 * Defines the class CodeRage\Build\Packages\Package.
 *
 * File:        CodeRage/Build/Packages/Package.php
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
 * @ignore
 */

/**
 * Represents information about a package.
 */
class Package extends \CodeRage\Util\BasicProperties {

    /**
     * The fully-qualified package name.
     *
     * @var string
     */
    private $name;

    /**
     * The version string.
     *
     * @var string
     */
    private $version;

    /**
     * The channel, if any, used to retrieve this package.
     *
     * @var CodeRage\Build\Packages\Channel
     */
    private $channel;

    /**
     * Constructs a CodeRage\Build\Packages\Package.
     *
     * @param string $name The fully-qualified package name.
     * @param string $version The version string.
     * @param CodeRage\Build\Packages\Channel $channel The channel, if any, used to
     * retrieve the package under construction.
     */
    function __construct($name, $version, $channel = null)
    {
        $this->name = $name;
        $this->version = $version;
        $this->channel = $channel;
    }

    /**
     * Returns the fully-qualified package name.
     *
     * @return string
     */
    function name() { return $this->name; }

    /**
     * Returns the version string.
     *
     * @return string
     */
    function version() { return $this->version; }

    /**
     * Returns the channel, if any, used to retrieve this package.
     *
     * @return CodeRage\Build\Packages\Channel
     */
    function channel()  { return $this->channel; }
}
