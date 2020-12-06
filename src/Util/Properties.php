<?php

/**
 * Defines the interface CodeRage\Util\Properties
 * 
 * File:        CodeRage/Util/Properties.php
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

interface Properties {

    /**
     * Returns the keys of this property bundle, as an array of strings.
     *
     * @return array
     */
    function propertyNames();

    /**
     * Returns true if the named property has been set
     *
     * @param string $name
     * @return boolean
     */
    function hasProperty($name);

    /**
     * Returns the value of the named property
     *
     * @param string $name
     * @param boolean $nothrow true if no exception should be thrown if the
     *   property has not been set
     * @return mixed
     * @throws CodeRage\Error if the $nothrow is false and the named property
     *   has not been set
     */
    function getProperty($name, $nothrow = false);

    /**
     * Returns a reference to the value of the named property
     *
     * @param string $name
     * @return mixed
     */
    function &getPropertyRef($name);

    /**
     * Sets the value of the named property
     *
     * @param string $name
     * @param mixed $value
     */
    function setProperty($name, $value);

    /**
     * Sets the value of the named property by reference
     *
     * @param string $name
     * @param mixed $value
     */
    function setPropertyRef($name, &$value);
}
