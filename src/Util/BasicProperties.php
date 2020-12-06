<?php

/**
 * Defines the class CodeRage\Util\BasicProperties
 * 
 * File:        CodeRage/Util/BasicProperties.php
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

class BasicProperties implements Properties {

    /**
     * The associative array of properties
     *
     * @var array
     */
    private $properties = [];

    /**
     * The property bundle to search when queried property is not found in
     * $this->properties
     *
     * @var CodeRage\Util\Properties
     */
    private $parent;

    /**
     * Constructs a CodeRage\Util\BasicProperties that stores properties in
     * an associative array and delegates to the given property bundle when
     * a queried property is not found in the array.
     *
     * @param CodeRage\Util\Properties $parent
     */
    function __construct($parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Specifies the property bundle to search when queried property is not
     * found in the underlying associative array.
     *
     * @param CodeRage\Util\Properties $parent
     */
    function setParentBundle(Properties $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the keys of this property bundle, as an array of strings.
     *
     * @return array
     */
    function propertyNames()
    {
        return $this->parent ?
            array_merge($this->properties, $this->parent->propertyNames()) :
            array_keys($this->properties);
    }

    /**
     * Returns true if the named property has been set
     *
     * @param string $name
     * @return mixed
     */
    function hasProperty($name)
    {
        return key_exists($name, $this->properties) ||
               $this->parent && $this->parent->hasProperty($name);
    }

    /**
     * Returns the value of the named property
     *
     * @param string $name
     * @param boolean $nothrow true if no exception should be thrown if the
     * property has not been set
     * @return mixed
     * @throws Exception if $nothrow is false and the named property
     * has not been set
     */
    function getProperty($name, $nothrow = false)
    {
        $value = null;
        $exists = false;
        if (key_exists($name, $this->properties)) {
            $exists = true;
            $value = $this->properties[$name];
        } else {
            $exists =
                $this->parent !== null &&
                $this->parent->hasProperty($name);
            if ($exists)
                $value = $this->parent->getProperty($name);
        }
        if (!$nothrow && !$exists)
            throw new Error(['details' => "Missing property $name"]);
        return $value;
    }

    /**
     * Returns a reference to the value of the named property
     *
     * @param string $name
     * @return mixed
     * @throws Exception if the named property has not been set
     */
    function &getPropertyRef($name)
    {
        if (key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } elseif ($this->parent && $this->parent->hasProperty($name)) {
            return $this->parent->getPropertyRef($name);
        } else {
            throw new Error(['details' => "Missing property $name"]);
        }
    }

    /**
     * Sets the value of the named property
     *
     * @param string $name
     * @param mixed $value
     */
    function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Sets the value of the named property by reference
     *
     * @param string $name
     * @param mixed $value
     */
    function setPropertyRef($name, &$value)
    {
        $this->properties[$name] =& $value;
    }
}
