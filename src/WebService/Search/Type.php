<?php

/**
 * Defines the interface CodeRage\WebService\Search\Type
 * 
 * File:        CodeRage/WebService/Search/Type.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search;

use CodeRage\Error;


/**
 * Used with CodeRage\WebService\Search to represent a field type
 */
interface Type {

    /**
     * Indicates that two distinct values of a type can be distinguished from
     * each other
     *
     * @var int
     */
    const FLAG_DISTINGUISHED = 1;

    /**
     * Indicates that a type's values are ordered
     *
     * @var int
     */
    const FLAG_ORDERED = 2;

    /**
     * Indicates that a type's values can be treated as text for use in pattern
     * matching
     *
     * @var int
     */
    const FLAG_TEXTUAL = 4;

    /**
     * Prohibits fields of a type to appear in sort specifiers
     *
     * @var int
     */
    const FLAG_UNSORTABLE = 8;

    /**
     * @var int
     */
    const FLAG_ALL =
        self::FLAG_DISTINGUISHED | self::FLAG_ORDERED | self::FLAG_TEXTUAL;

    /**
     * Returns the name of this type
     *
     * @return string
     */
    function name();

    /**
     * Returns the name of the internal type
     *
     * @return string One of int, float, or string
     */
    function internal();

    /**
     * Returns the name of the external type
     *
     * @return string
     */
    function external();

    /**
     * Returns a bitwise OR of zero or more of the constants FLAG_XXX
     *
     * @return int
     */
    function flags();

    /**
     * Converts the given string to this type's internal type
     *
     * @param string $value The value to be converted
     * @return mixed
     * @throws Exception if $value cannot be converted
     */
    function toInternal($value);

    /**
     * Converts the given value to this type's external type
     *
     * @param mixed $value The value to be converted, as an int, float, or
     *   string
     * @return mixed
     */
    function toExternal($value);

    /**
     * Returns one of the type specifiers 'i', 'f', or 's'
     *
     * @return string
     */
    function specifier();
}
