<?php

/**
 * Defines the class CodeRage\Db\QueryProcessor
 *
 * File:        CodeRage/Db/QueryProcessor.php
 * Date:        Mon Oct 29 19:17:36 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use CodeRage\Db;
use CodeRage\Error;


/**
 * Implements CodeRage\Db::processQuery()
 */
final class QueryProcessor extends Object_ {

    /**
     * Constructs a CodeRage\Db\QueryProcessor
     *
     * @param CodeRage\Db $db A database connection
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * Processes the given parameterized SQL query, quoting bracketed
     * identifiers and replacing placeholder with values from $args, if
     * supplied.
     *
     * @param string $sql A SQL query with embedded placeholders. Identifiers
     *   can be quoted using  square brackets; placeholders have the form
     *   '%c', where c is one of 'i', 'f', 'd', 's', or 'b', with the following
     *   interpretation:
     *      i - integer
     *      f - float
     *      d - decimal
     *      s - string
     *      b - blob
     *   The expected data types of columns in the result set can be indicated
     *   by inserting an expression of the form '{c}' after the column
     *   expression, where 'c' has the same interpretation as above.
     * @param array $args The values to bind to the query's embedded
     *   placeholders
     * @return array A list whose first component is the modified SQL text,
     *   whose second component is a list of values of the form
     *   CodeRage\Db::TYPE_XXX indicating the expected result types (or null if
     *   type information is not provided), and whose third component is a list
     *   of values of the form CodeRage\Db::TYPE_XXX indicating the expected
     *   parameter types (or null if $args is supplied).
     * @throws CodeRage\Error
     */
    public function process($sql, $args = null)
    {
        if (empty($args) && isset(self::$cache[$sql]))
            return self::$cache[$sql]; // Covers null and an empty array
        $result = $this->processImpl($sql, $args);
        if (empty($args))
            self::$cache[$sql] = $result;
        return $result;
    }

    /**
     * Implements process()
     *
     *
     * @param string $sql A SQL query with embedded placeholders. Identifiers
     *   can be quoted using  square brackets; placeholders have the form
     *   '%c', where c is one of 'i', 'f', 'd', 's', or 'b', with the following
     *   interpretation:
     *      i - integer
     *      f - float
     *      d - decimal
     *      s - string
     *      b - blob
     *   The expected data types of columns in the result set can be indicated
     *   by inserting an expression of the form '{c}' after the column
     *   expression, where 'c' has the same interpretation as above.
     * @param array $args The values to bind to the query's embedded
     *   placeholders
     * @return array A list whose first component is the modified SQL text,
     *   whose second component is a list of values of the form
     *   CodeRage\Db::TYPE_XXX indicating the expected result types (or null if
     *   type information is not provided), and whose third component is a list
     *   of values of the form CodeRage\Db::TYPE_XXX indicating the expected
     *   parameter types (or null if $args is supplied).
     * @throws CodeRage\Error
     */
    private function processImpl($sql, $args)
    {
        $in = $out = null;
        $index = 0;
        $query =
            preg_replace_callback(
                '/(.*?)(\[\[|\[(?:[^]]+\])?|%%|\{\{|\}\}|%[a-zA-Z]?|\{(?:[a-zA-Z]\})?|$)/',
                function($match) use ($sql, $args, &$in, &$out, &$index)
                {
                    list($all, $prefix, $expr) = $match;
                    $length = strlen($expr);
                    switch ($length) {
                    case 0:

                        // We have reached the end of $sql
                        return $prefix;
                    case 1:

                        // We reached the end of $sql, but the matched
                        // embedded expression is incomplete
                        $missing = $expr == '%' ?
                            'placeholder type specifier' :
                            ( $expr == '{' ?
                                  'column type specifier' :
                                  'identifier to escape' );
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' => "Missing $missing in query: $sql"
                            ]);
                    default:
                        // Fall through
                    }

                    // Analyze and rewrite $expr
                    $symb = $expr[0];
                    if ($symb == $expr[1]) {

                        // Handle escape sequences [[, ]], {{, }}, %%
                        $expr = $symb;
                    } else {

                        switch ($symb) {
                        case '%':


                            // Handle typed placeholders
                            $type = self::getType($expr[1]);
                            if ($args === null) {
                                if ($in === null)
                                    $in = [];
                                $in[] = $type;
                                $expr = '?';
                            } elseif ($index >= sizeof($args)) {
                                throw new
                                    Error([
                                        'status' => 'INCONSISTENT_PARAMETERS',
                                        'details' =>
                                            "Too few parameters supplied for " .
                                            "query: $sql"
                                    ]);
                            } else {
                                $expr = $this->formatScalar($args[$index++], $type);
                            }
                            break;
                        case '{':

                            // Handle column type specifiers
                            if ($out === null)
                                $out = [];
                            $out[] = self::getType($expr[1]);
                            $expr = '';
                            break;
                        case '[':

                            // Handle identifiers that need to be quoted
                            $expr =
                                $this->db->quoteIdentifier(
                                    substr($expr, 1, $length - 2)
                                );
                            break;
                        default:
                            break; // Can't occur
                        }
                    }
                    return $prefix . $expr;
                },
                $sql
            );
        if ($args !== null && $index < count($args))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' => "Too many parameters supplied for query: $sql"
                ]);
        return [$query, $out, $in];
    }

    /**
     * Returns a string representation of the given value suitable for inserting
     * into a SQL query
     *
     * @param mixed $value A scalar value
     * @param int A value of the form CodeRage\Db::TYPE_XXX representing a
     *   column type
     */
    private function formatScalar($val, $type)
    {
        if ($val === null)
            return 'NULL';
        $error = false;
        switch ($type) {
        case Db::TYPE_INT:
            if (is_bool($val)) {
                $val = intval($val);
            } else {
                $error =
                    !is_int($val) &&
                    (!is_numeric($val) || intval($val) != $val);
            }
            break;
        case Db::TYPE_FLOAT:
            $error = !is_numeric($val);
            break;
        case Db::TYPE_DECIMAL:
        case Db::TYPE_STRING:
        case Db::TYPE_BLOB:
            try {
                $val = $this->db->quote((string) $val);
            } catch (PDOException $e) {
                throw new
                    Error([
                        'status' => 'DATABASE_ERROR',
                        'details' => $e->getMessage()
                    ]);
            }
            break;
        default:
            break; // Can't happen
        }
        if ($error) {
            $expected = self::translateType($type);
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid query parameters: expected $expected; found " .
                        Error::formatValue($val)
                ]);
        }
        return (string) $val;
    }

    /**
     * Returns a value of the form CodeRage\Db::TYPE_XXX corresponding to the
     * given type specifier.
     *
     * @param string $c One of the characters 'i', 'f', 'd', 's', or 'b', with
     *   the following interpretation:
     *     i - integer
     *     s - string
     *     f - float
     *     b - blob
     * @return int
     */
    static private function getType($c)
    {
        switch ($c) {
        case 'i': return Db::TYPE_INT;
        case 'f': return Db::TYPE_FLOAT;
        case 'd': return Db::TYPE_DECIMAL;
        case 's': return Db::TYPE_STRING;
        case 'b': return Db::TYPE_BLOB;
        default:
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid type specifier: $c"
                ]);
        }
    }

    /**
     * @var CodeRage\Db
     */
    private $db;

    /**
     * @var array
     */
    private static $cache = [];
}
