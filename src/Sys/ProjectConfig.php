<?php

/**
 * Defines the interface CodeRage\Sys\ProjectConfig
 *
 * File:        CodeRage/Sys/Config.php
 * Date:        Mon Jan 25 18:03:21 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     Makeme_Config rights reserved
 */

namespace CodeRage\Sys;

/**
 * Generic project configuration interface
 */
interface ProjectConfig {

    /**
     * Returns true if the named configuration variable has been assigned a
     * value
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
     * @return string
     */
    public function getProperty(string $name, ?string $default = null): ?string;

    /**
     * Returns the value of the named configuration variable, throwing an
     * exception if it is not set
     *
     * @param string $name A configuration variable name
     * @return string
     * @throws Exception if the variable is not set
     */
    public function getRequiredProperty(string $name): string;
    /**
     * Returns a list of the names of all configuration variables
     *
     * @return array
     */
    public function propertyNames(): array;

    /**
     * Returns the value of the named property
     *
     * @param string $name
     * @return CodeRage\Sys\Config\Property
     */
    public function lookupProperty(string $name): ?Property;

    /**
     * Adds the named property
     *
     * @param string The property name
     * @param CodeRage\Sys\Config\Property $property
     * @throws Exception if a property with the same name already exists
     */
    public function addProperty(string $name, Property $property): void;
}
