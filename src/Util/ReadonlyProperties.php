<?php

/**
 * Defines the class CodeRage\Util\ReadonlyProperties
 * 
 * File:        CodeRage/Util/ReadonlyProperties.php
 * Date:        Thu Nov 22 21:07:47 MST 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Represents an immutable collection of properties.
 */
class ReadonlyProperties implements Properties {

    /**
     * An associative array of properties.
     *
     * @var array
     */
    private $properties;

    /**
     * Used by CodeRage\Util\PropertiesSet.
     *
     * @var string
     */
    private $hashKey;

    /**
     * Constructs a CodeRage\Util\ReadonlyProperties.
     *
     * @param array $properties An associative array of properties.
     */
    function __construct($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Returns the keys of this property bundle, as an array of strings.
     *
     * @return array
     */
    function propertyNames()
    {
        return array_keys($this->properties);
    }

    /**
     * Returns true if the named property has been set
     *
     * @param string $name
     * @return mixed
     */
    function hasProperty($name)
    {
        return key_exists($name, $this->properties);
    }

    /**
     * Returns the value of the named property.
     *
     * @param string $name
     * @param boolean $nothrow true if no exception should be thrown if the
     * property has not been set
     * @return mixed
     * @throws CodeRage\Error if $nothrow is false and the named property
     * has not been set
     */
    function getProperty($name, $nothrow = false)
    {
        $value = key_exists($name, $this->properties) ?
            $this->properties[$name] :
            null;
        if (!$nothrow && $value === null)
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "Missing property $name"
                ]);
        return $value;
    }

    /**
     * Returns a reference to the value of the named property.
     *
     * @param string $name
     * @return mixed
     */
    function &getPropertyRef($name)
    {
        if (!key_exists($name, $this->properties))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "Missing property $name"
                ]);
        return $this->properties[$name];
    }

    /**
     * Throws CodeRage\Error.
     *
     * @param string $name
     * @param mixed $value
     */
    function setProperty($name, $value)
    {
        throw new Error(['details' => "Attempt to modidy read-only property bundle"]);
    }

    /**
     * Throws CodeRage\Error.
     *
     * @param string $name
     * @param mixed $value
     */
    function setPropertyRef($name, &$value)
    {
        throw new Error(['details' => "Attempt to modidy read-only property bundle"]);
    }

    /**
     * Returns a string used by CodeRage\Util\PropertiesSet as an index into
     * an associative array.
     *
     * @return string
     */
    function hashKey()
    {
        return $this->hashKey;
    }

    /**
     * Specifices a string used by CodeRage\Util\PropertiesSet as an index into
     * an associative array.
     *
     * @return string
     */
    function setHashKey($hashKey)
    {
        $this->hashKey = $hashKey;
    }
}
