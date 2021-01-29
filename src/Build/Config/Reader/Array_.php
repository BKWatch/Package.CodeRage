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

/**
 * Reads collections of properties from an associative array
 */
final class Array_ implements \CodeRage\Build\Config\Reader {

    /**
     * Constructs a CodeRage\Build\Config\Reader\Array_
     *
     * @param array $properties An associative array of string-valued properties
     * @param string $path The path of the file containing the coonfiguration
     */
    public function __construct(array $properties, string $path)
    {
        Args::check($properties, 'map[string]', 'properties');
        $this->properties = new Basic;
        foreach ($properties as $name => $value) {
            $this->properties->addProperty(new Property([
                'name' => $name,
                'value' => $value,
                'setAt' => $path
            ]));
        }
    }

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Build\ProjectConfig
     */
    public function read(): \CodeRage\Build\BuildConfig
    {
        return $this->properties;
    }

    /**
     * @var CodeRage\Build\Config\Basic
     */
    private $properties;
}
