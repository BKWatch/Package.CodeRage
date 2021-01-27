<?php

/**
 * Defines the interface CodeRage\Build\BuildConfig
 *
 * File:        CodeRage/Build/BuildConfig.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     Makeme_Config rights reserved
 */

namespace CodeRage\Build;

/**
 * Extension of CodeRage\Build\ProjectConfig providing access to property
 * objects
 */
interface BuildConfig extends ProjectConfig {

    /**
     * Returns the value of the named property
     *
     * @param string $name
     * @return CodeRage\Build\Config\Property
     */
    function lookupProperty($name): ?Config\Property;

    /**
     * Adds the named property
     *
     * @param CodeRage\Build\Config\Property $property
     * @throws Exception if a property with the same name already exists
     */
    function addProperty(Config\Property $property): void;
}
