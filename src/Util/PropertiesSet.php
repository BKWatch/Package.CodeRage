<?php

/**
 * Defines the class CodeRage\Util\PropertiesSet
 * 
 * File:        CodeRage/Util/PropertiesSet.php
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
 * Represents a collection of read-only property bundles.
 */
class PropertiesSet {

    /**
     * Associative array mapping hashes to set members.
     *
     * @var array
     */
    private $members = [];

    /**
     * Maps strings to integer ID's.
     *
     * @var array
     */
    private $stringCodes = [];

    /**
     * The next ID for use with $valuesCache.
     *
     * @var int
     */
    private $nextId = 0;

    /**
     * Returns true if this collection contains a property bundle equivalent to
     * the specified collection of properties.
     *
     * If $properties is an instance of CodeRage\Util\Properties, its hashKey
     * property will be set; therefore a property bundle that is a member of one
     * CodeRage\Util\PropertiesSet must not be tested for membership in another
     * CodeRage\Util\PropertiesSet.
     *
     * @param mixed $properties An associative array or instance of
     * CodeRage\Util\Properties.
     * @return boolean
     */
    function contains($properties)
    {
        $hashKey = $this->getHashKey($properties);
        return key_exists($hashKey, $this->members);
    }

    /**
     * Returns the stored property bundle, if any, equivalent to the specified
     * collection of properties.
     *
     * If $properties is an instance of CodeRage\Util\Properties, its hashKey
     * property will be set; therefore a property bundle that is a member of one
     * CodeRage\Util\PropertiesSet must not be used as a lookup index in another
     * CodeRage\Util\PropertiesSet.
     *
     * @param mixed $properties An associative array or instance of
     * CodeRage\Util\Properties.
     * @return CodeRage\Util\ReadonlyProperties
     */
    function lookup($properties)
    {
        $hashKey = $this->getHashKey($properties);
        return key_exists($hashKey, $this->members) ?
            $this->members[$hashKey] :
            null;
    }

    /**
     * Returns the number of objects in this set.
     *
     * @return int
     */
    function size() { return sizeof($this->members); }

    /**
     * Returns a list of the objects in this set.
     *
     * @return array
     */
    function members() { return array_values($this->members); }

    /**
     * Adds a property bundle to this set. Only property bundles whose values
     * are strings may be added to a CodeRage\Util\PropertiesSet. A property
     * bundle may only be a member of one CodeRage\Util\PropertiesSet at a time.
     *
     * If an equivalent property bundle is already a member of this set, it will
     * be replaced.
     *
     * @param CodeRage\Util\ReadonlyProperties $properties
     */
    function add(ReadonlyProperties $properties)
    {
        $hashKey = $this->getHashKey($properties);
        if (key_exists($hashKey, $this->members))
            $this->members[$hashKey]->setHashKey(null);
        $this->members[$hashKey] = $properties;
    }

    /**
     * Removes a property bundle from this set.
     *
     * @param CodeRage\Util\ReadonlyProperties $properties
     */
    function remove(ReadonlyProperties $properties)
    {
        $hashKey = $properties->hashKey();
        $properties->setHashKey(null);
        unset($this->members[$hashKey]);
    }

    /**
     * Returns the hash key for the given collection of properties. If
     * $properties is an instance of CodeRage\Util\Properties whose hashKey
     * property is not set, its hashKey property will be set.
     *
     * @param mixed $properties An associative array or instance of
     * CodeRage\Util\Properties.
     */
    private function getHashKey($properties)
    {
        $hashKey = null;
        if (is_object($properties) && $properties->hashKey !== null) {
            $hashKey = $properties->hashKey();
        } else {
            $hashKey =  $this->calculateHashKey($properties);
            if (is_object($properties))
                $properties->setHashKey($hashKey);
        }
        return $hashKey;
    }

    /**
     * Returns the hash key associated twith the given collection of properties.
     *
     * @param mixed $properties An associative array or instance of
     * CodeRage\Util\Properties.
     */
    private function calculateHashKey($properties)
    {
        // Construct a copy of $properties with each key and value replaced by
        // its unique identifier
        $map = [];
        foreach ($properties as $n => $v) {
            if (!is_string($n))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            'Invalid property name: ' . Error::formatValue($n)
                    ]);
            if (!is_string($v))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            'Invalid property value: ' . Error::formatValue($v)
                    ]);
            $nid = $vid = null;
            if (isset($this->stringCodes[$n])) {
                $nid = $this->stringCodes[$n];
            } else {
                $this->stringCodes[$n] = $nid = ++$this->nextId;
            }
            if (isset($this->stringCodes[$v])) {
                $vid = $this->stringCodes[$v];
            } else {
                $this->stringCodes[$v] = $vid = ++$this->nextId;
            }
            $map[$nid] = $vid;
        }
        ksort($map);

        // Concatenate collection of keys and IDs.
        $hashKey = '';
        foreach ($map as $nid => $vid)
            $hashKey .= "$nid:$vid;";

        return $hashKey;
    }
}
