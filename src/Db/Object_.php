<?php

/**
 * Defines the class CodeRage\Db\Object_
 *
 * File:        CodeRage/Db/Object_.php
 * Date:        Sun Jul  7 18:43:46 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use CodeRage\Db;
use CodeRage\Error;

/**
 * Base class for CodeRage\Db, CodeRage\Db\QueryProcessor CodeRage\Db\Result,
 * and CodeRage\Db\Statement
 */
abstract class Object_ {

    /**
     * Represents SQL the integral types
     *
     * @var integer
     */
    const TYPE_INT = 1;

    /**
     * Represents SQL floating point types
     *
     * @var integer
     */
    const TYPE_FLOAT = 2;

    /**
     * Represents SQL decimal types
     *
     * @var integer
     */
    const TYPE_DECIMAL = 3;

    /**
     * Represents the CHAR or VARCHAR types
     *
     * @var integer
     */
    const TYPE_STRING = 4;

    /**
     * Represents SQL large object types
     *
     * @var integer
     */
    const TYPE_BLOB = 5;

    /**
     * Inticates that rows of query results should be represented as indexed
     * arrays. The value was chosen to coincide with MDB2_FETCHMODE_ORDERED.
     *
     * @var int
     */
    const FETCHMODE_ORDERED = 1;

    /**
     * Inticates that rows of query results should be represented as associative
     * arrays. The value was chosen to coincide with MDB2_FETCHMODE_ASSOC.
     *
     * @var int
     */
    const FETCHMODE_ASSOC = 2;

    /**
     * Inticates that rows of query results should be represented as instances
     * of stdClass. The value was chosen to coincide with MDB2_FETCHMODE_OBJECT.
     *
     * @var int
     */
    const FETCHMODE_OBJECT = 3;

    /**
     * Throws an exception if the given intergral type specifier is not
     * supported
     *
     * @param int $type A value of the form CodeRage\Object_::TYPE_XXX
     * @param string $label Descriptive text, for use in error messages
     * @return string
     */
    protected static function checkType($type, $label = null)
    {
        static $valid =
            [
                self::TYPE_INT => 1,
                self::TYPE_FLOAT => 1,
                self::TYPE_DECIMAL => 1,
                self::TYPE_STRING => 1,
                self::TYPE_BLOB => 1
            ];
        if (!isset($valid[$type])) {
            if ($label === null)
                $label = 'type specifier';
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid $label: $type"
                ]);
        }
    }

    /**
     * Returns a human-readable name for the given integral type specifier
     *
     * @param int $type A value of the form CodeRage\Object_::TYPE_XXX
     * @return string
     */
    protected static function translateType($type)
    {
        switch ($type) {
        case \CodeRage\Db::TYPE_INT:
            return 'integer';
        case \CodeRage\Db::TYPE_FLOAT:
            return 'float';
        case \CodeRage\Db::TYPE_DECIMAL:
            return 'decimal';
        case \CodeRage\Db::TYPE_STRING:
            return 'string';
        case \CodeRage\Db::TYPE_BLOB:
            return 'blob';
        default:
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid type specifier: $type"
                ]);
        }
    }
}
