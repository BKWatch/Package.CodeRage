<?php

/**
 * Defines the class CodeRage\Build\Config\Reader\Environment.
 *
 * File:        CodeRage/Build/Config/Reader/Environment.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Reader;

use const CodeRage\Build\CONFIG_ENVIRONMENT_PREFIX;

/**
 * Reads collections of properties from the environment.
 */
class Environment implements \CodeRage\Build\Config\Reader {

    /**
     * Returns a CodeRage\Build\ExtendedConfig.
     *
     * @return CodeRage\Build\ExtendedConfig
     * @throws CodeRage\Error
     */
    function read()
    {
        $props = new \CodeRage\Build\Config\Basic;
        $prefixLen = strlen(CONFIG_ENVIRONMENT_PREFIX);
        $pattern = '/^[_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*$/';

        // Enumerate server variables
        foreach ($_SERVER as $name => $value) {

            // Skip non-environment variables
            if (getenv($name) !== $value)
                continue;

            // Normalize case
            $name = strtolower($name);

            // Skip variables that don't start with 'CODERAGE_'
            if (strncmp($name, CONFIG_ENVIRONMENT_PREFIX, $prefixLen))
                continue;

            // Strip prefix and replace double underscores with dots
            $dotted = str_replace( '__', '.', substr($name, $prefixLen));
            if (!preg_match($pattern, $dotted))
                throw new \CodeRage\Error("Invalid environment variable name: $name");

            // Add property
            $props->addProperty(
                new \CodeRage\Build\Config\Property(
                        $dotted, \CodeRage\Build\ISSET_, $value, 0, \CodeRage\Build\ENVIRONMENT
                    )
            );
        }
        return $props;
    }
}
