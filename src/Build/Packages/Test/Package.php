<?php

/**
 * Defines the class CodeRage\Build\Packages\Test\Package.
 *
 * File:        CodeRage/Build/Packages/Test/Package.php
 * Date:        Sat Mar 21 14:45:46 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Test;

/**
 * @ignore
 */

/**
 * Subclass of CodeRage\Build\Packages\Package used for testing.
 */
class Package
    extends \CodeRage\Build\Packages\Package
{
    /**
     * A list of associative arrays with keys among 'name', 'minVersion',
     * 'maxVersion', and 'channel' indicating the packages that this package
     * depends on. Only the key 'name' is required.
     *
     * @var array
     */
    private $dependencies;

    /**
     * Constructs a CodeRage\Build\Packages\Package.
     *
     * @param string $name The fully-qualified package name.
     * @param string $version The version string.
     * @param CodeRage\Build\Packages\Channel $channel The channel, if any, used to
     * retrieve the package under construction.
     * @param array $dependencies A list of associative arrays with keys among
     * 'name', 'minVersion', 'maxVersion', and 'channel' indicating the packages
     * that the package under construction depends on. Only the key 'name' is
     * required.
     */
    function __construct($name, $version, $channel, $dependencies)
    {
        parent::__construct($name, $version, $channel);
        $this->dependencies = $dependencies;
    }

    /**
     * Returns a list of associative arrays with keys among 'name',
     * 'minVersion', 'maxVersion', and 'channel' indicating the packages that
     * this package depends on. Only the key 'name' is required.
     *
     * @return array
     */
    function dependencies()
    {
        return $this->dependencies;
    }
}
