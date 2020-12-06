<?php

/**
 * Defines the interface CodeRage\Sys\ConfigInterface
 *
 * File:        CodeRage/Sys/ConfigInterface.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

/**
 * Represents a collection of configuration variables
 */
interface ConfigInterface
{
    /**
     * Returns true if the named configuration variable is set
     *
     * @param string $name A configuration variable name
     * @return boolean
     */
    public function hasProperty(string $name): bool;

    /**
     * Returns the value of the named configuration variable, or the given
     * default value is the variable is not set
     *
     * @param string $name A configuration variable name
     * @param string $default The default value
     * @return mixed A scalar value
     */
    public function getProperty(string $name, $default = null);

    /**
     * Returns the value of the named configuration variable, throwing an
     * exception if it is not set
     *
     * @param string $name A configuration variable name
     * @return mixed
     * @throws Exception if the variable is not set
     */
    public function getRequiredProperty(string $name);
}
