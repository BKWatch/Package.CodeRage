<?php

/**
 * Defines the class CodeRage\Build\Config\Reader\Array_
 *
 * File:        CodeRage/Build/Config/Reader/Array_.php
 * Date:        Mon Jan 18 00:45:13 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Reader;

use CodeRage\Build\Config\Basic;
use CodeRage\Build\Config\Property;
use CodeRage\Util\Args;

use const CodeRage\Build\ISSET_;
use const CodeRage\Build\STRING;

/**
 * Reads collections of properties from an associative array
 */
class Array_ implements \CodeRage\Build\Config\Reader {

    /**
     * Constructs a CodeRage\Build\Config\Reader\Array_
     *
     * @param array $properties An associative array of string-valued properties
     */
    function __construct(array $properties)
    {
        Args::check($properties, 'map[string]', 'properties');
        $this->properties = new Basic;
        foreach ($properties as $n => $v) {
            $this->properties->addProperty(new Property(
                $n, ISSET_ | STRING, $v, null, null
            ));
        }
    }

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Build\ExtendedConfig
     */
    function read()
    {
        return $this->properties;
    }

    /**
     * @var CodeRage\Build\Config\Basic
     */
    private $properties;
}
