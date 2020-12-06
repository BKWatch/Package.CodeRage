<?php

/**
 * Defines the interface CodeRage\WebService\Search\Operation
 * 
 * File:        CodeRage/WebService/Search/Operation.php
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
 * Used with CodeRage\WebService\Search to represent a filter operation
 */
interface Operation {

    /**
     * Indicates that an operation requires that its operands be of a type whose
     * values can be distinguished from each other
     *
     * @var int
     */
    const FLAG_DISTINGUISHED = Type::FLAG_DISTINGUISHED;

    /**
     * Indicates that an operation requires that its operands be of an ordered
     * type
     *
     * @var int
     */
    const FLAG_ORDERED = Type::FLAG_ORDERED;

    /**
     * Indicates that an operation requires that its operands be of type whose
     * values can be treated as text for use in pattern matching
     *
     * @var int
     */
    const FLAG_TEXTUAL = Type::FLAG_TEXTUAL;

    /**
     * @var int
     */
    const FLAG_TYPE =
        self::FLAG_DISTINGUISHED | self::FLAG_ORDERED | self::FLAG_TEXTUAL;

    /**
     * Indicates than an operation is unary, rather than binary
     */
    const FLAG_UNARY = 8;

    /**
     * Returns the operation name
     *
     * @return string
     */
    function name();

    /**
     * Returns a bitwise OR of one or mor of the constants FLAG_XXX
     *
     * @return int
     */
    function flags();

    /**
     * Translates a search filter to a SQL condition
     *
     * @param CodeRage\WebService\Search\Field The field
     * @param string $value The filter value
     * @param CodeRage\Db $db A database connection
     * @return array A pair [$sql, $params] where $sql is a SQL fragment and
     *   $values is a sequence of placeholder replacements
     */
    function translate(Field $field, $value, \CodeRage\Db $db);
}
