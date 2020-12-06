<?php

/**
 * Defines the class CodeRage\Util\Array_
 *
 * File:        CodeRage/Util/Array_.php
 * Date:        Thu Sep 17 12:28:44 UTC 2020
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;

/**
 * Container for static methods for array manipulation
 */
final class Array_ {

    /**
     * Returns the first item in the given array satisfying the given condition
     *
     * @param array $array The array to search
     * @param callable $condition A callable used to test the condition; it will
     *   be called as follows:
     *     $condition($value, $key)
     *   where $value is the array element and $key is the corresponding key
     * @param array $options The options array; supports the following options:
     *     returnKey - true to return the key rather than the value; defaults to
     *       false
     * @return mixed The first item found, or null if no items satisfy the
     *   condition
     */
    public static function find(array $array, callable $condition,
        array $options = [])
    {
        Args::checkKey($options, 'returnKey', 'boolean', [
            'label' => 'return key flag',
            'default' => false
        ]);
        $returnKey = $options['returnKey'];
        foreach ($array as $k => $v)
            if ($condition($v, $k))
                return $returnKey ? $k : $v;
        return null;
    }

    /**
     * Returns true if the keys of this array form an initial segment of the
     * natural numbers
     *
     * @param array $array
     * @return boolean
     */
    public static function isIndexed(array $array) : bool
    {
        $index = 0;
        foreach ($array as $n => $v)
            if ($n !== $index++)
                return false;
        return true;
    }

    /**
     * Version of array_map() that optionally concatenates the results with a
     * provided separator string
     *
     * @param callback $callback The callback
     * @param array $array The array
     * @param string $sep The separator string
     * @return mixed
     */
    public static function map(callable $callback, array $array,
        ?string $sep = null)
    {
        $result = array_map($callback, $array);
        return $sep !== null ? join($sep, $result) : $result;
    }

    /**
     * Sorts the given using A partial ordering defined by the given callback
     *
     * WARNING: This function is not implemented efficiently and should only be
     * used for sorting small arrays.
     *
     * @param array $items
     * @param callback $callback A callback defining a binary relation which can
     *   be extended to a preorder. Given two list items, $first and $second,
     *   $callback returns an integer less than, equal to, or greater than zero
     *   if $first is considered to be respectively less than, equal to, or
     *   greater than $second. If no relation holds between $first and $second,
     *   $callback returns null.
     */
    public static function topologicalSort(array &$items,
        callable $callback) : void
    {
        $size = count($items);
        $pairs = [];       // Defines a <= relation
        $byFirst = [];     // Maps an index i to the list of j with i <= j
        $bySecond = [];    // Maps an index i to the list of j with j <= i

        // Populate $pairs
        for ($a = 0; $a < $size; ++$a) {
            for ($b = 0; $b < $size; ++$b) {
                $cmp = $callback($items[$a], $items[$b]);
                if ($cmp === null)
                    continue;
                if ($cmp < 0) {
                    self::addPair($a, $b, $pairs, $byFirst, $bySecond);
                } elseif ($cmp > 0) {
                    self::addPair($b, $a, $pairs, $byFirst, $bySecond);
                } else {
                    self::addPair($a, $b, $pairs, $byFirst, $bySecond);
                    self::addPair($b, $a, $pairs, $byFirst, $bySecond);
                }
            }
        }

        // Add implied pairs
        $stop = false;
        while (!$stop) {
            $stop = true;
            foreach ($pairs as $p) {
                list($b, $c) = $p;
                if (isset($byFirst[$c])) {
                    foreach ($byFirst[$c] as $d) {
                        if (array_search([$b, $d], $pairs) !== false)
                            continue;
                        $stop = false;
                        self::addPair($b, $d, $pairs, $byFirst, $bySecond);
                    }
                }
                if (isset($bySecond[$b])) {
                    foreach ($bySecond[$b] as $a) {
                        if (array_search([$a, $c], $pairs) !== false)
                            continue;
                        $stop = false;
                        self::addPair( $a, $c, $pairs, $byFirst, $bySecond);
                    }
                }
            }
        }

        // Perform sort
        $order = [];
        for ($z = 0; $z < $size; ++$z) {
            $found = false;
            for ($w = 0, $n = sizeof($order); $w < $n; ++$w) {
                if (array_search([$z, $order[$w]], $pairs) !== false) {
                    array_splice($order, $w, 0, $z);
                    $found = true;
                    break;
                }
            }
            if (!$found)
                $order[] = $z;
        }

        // Update $items
        $temp = [];
        for ($z = 0; $z < $size; ++$z)
            $temp[$z] = $items[$order[$z]];
        for ($z = 0; $z < $size; ++$z)
            $items[$z] = $temp[$z];
    }

    /**
     * Unsets the given key in the given array and returns the original value,
     * if the key was set, or the specified default value, otherwise
     *
     * @param array $array The array
     * @param string $key The list of keys
     * @param mixed $default The default value; defaults to null
     */
    public static function unset(array &$array, string $key, $default = null)
    {
        $value = $array[$key] ?? $default;
        unset($array[$key]);
        return $value;
    }

    /**
     * Returns the list of values with the given keys in the given array
     *
     * @param array $array The array
     * @param mixed $keys The list of keys
     * @param mixed $default The default value; defaults to null
     * @return array
     */
    public static function values(array $array, array $keys, $default = null)
        : array
    {
        Args::check($keys, 'list[scalar]', 'keys');
        $values = [];
        foreach ($keys as $n => $k)
            $values[$n] = isset($array[$k]) ? $array[$k] : $default;
        return $values;
    }

    /**
     * Helper method for topologicalSort()
     */
    private static function addPair($a, $b, &$pairs, &$byFirst, &$bySecond)
    {
        $p = [$a, $b];
        if (array_search($p, $pairs) !== false)
            return;
        $pairs[] = $p;
        if (!isset($byFirst[$a]))
            $byFirst[$a] = [];
        $byFirst[$a][] = $b;
        if (!isset($bySecond[$b]))
            $bySecond[$b] = [];
        $bySecond[$b][] = $a;
    }
}
