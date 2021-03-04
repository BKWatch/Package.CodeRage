<?php

/**
 * Defines the class CodeRage\Sys\Config\Reader\Array_
 *
 * File:        CodeRage/Sys/Config/Reader/Array_.php
 * Date:        Mon Jan 18 00:45:13 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config\Reader;

use CodeRage\Sys\Config\Basic;
use CodeRage\Sys\Property;
use CodeRage\Util\Args;

/**
 * Reads collections of properties from an associative array
 */
final class Array_ implements \CodeRage\Sys\Config\Reader {

    /**
     * Constructs a CodeRage\Sys\Config\Reader\Array_
     *
     * @param array $properties An associative array of string-valued properties
     * @param string $setAt The source of the property value; must be a file
     *   pathname or one of the special values "[cli]" or "[code]"
     */
    public function __construct(array $properties, string $setAt)
    {
        Args::check($properties, 'map[string]', 'properties');
        $this->properties = new Basic;
        foreach ($properties as $name => $value) {
            $this->properties->addProperty($name, Property::decode($value, $setAt));
        }
    }

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Sys\ProjectConfig
     */
    public function read(): \CodeRage\Sys\ProjectConfig
    {
        return $this->properties;
    }

    /**
     * @var CodeRage\Sys\Config\Basic
     */
    private $properties;
}
