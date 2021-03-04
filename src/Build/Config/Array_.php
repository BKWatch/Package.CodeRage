<?php

/**
 * Defines the class CodeRage\Build\Config\Basic
 *
 * File:        CodeRage/Build/Config/Basic.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     Makeme_Config rights reserved
 */

namespace CodeRage\Build\Config;

use CodeRage\Sys\ProjectConfig;

/**
 * Provided for backward compatibility
 */
class Array_ extends \CodeRage\Sys\Config\Array_ {

    /**
     * Constructs a CodeRage\Build\Config\Basic from an associative array
     *
     * @param array $properties An associative array of strings
     * @param CodeRage\Sys\ProjectConfig $default An optional fallback
     *   configuration
     */
    function __construct($properties = [], ?ProjectConfig $default = null)
    {
        parent::__construct($properties, $default);
    }
}
