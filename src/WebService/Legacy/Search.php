<?php

/**
 * Defines the class CodeRage\WebService\Legacy\Search.
 *
 * File:        CodeRage/WebService/Legacy/Search.php
 * Date:        Sun Mar 20 13:25:04 MDT 2011
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Legacy;

use CodeRage\Error;
use CodeRage\WebService\Types;

/**
 * @ignore
 */

/**
 * Helper class for implementing searches with filters and sorting.
 */
class Search {

    /**
     * Associative array mapping field names to pairs ($type, $alias), where
     * $type is one of 'boolean', 'int', 'string', 'date', 'datetime', or 'id'
     *
     * @var array
     */
    private $fields = [];

    /**
     * List of triples ($field, $type, $value), where $type is one of
     * 'equals', 'notEquals', 'containts', 'notContains', 'lessThan',
     * 'greaterThan', 'matches', or 'notMatches' and $value is a scalar
     *
     * @var array
     */
    private $filters = [];

    /**
     * List of pairs ($field, $direction) where direction is one of 'ascending'
     * or 'descending'
     *
     * @var array
     */
    private $sortSpecifiers = [];

    /**
     * The $index of the beginning of the subset of search results to be
     * returned, starting from 1
     *
     * @var int
     */
    private $begin;

    /**
     * The $index of one past the end of the subset of search results to be
     * returned, starting from 1
     *
     * @var int
     */
    private $end;

    /**
     * Constructs an instance of CodeRage\WebService\Search
     *
     * @param fields An associative array mapping field names to strings $type
     *   or pairs ($type, $alias), where $type is one of 'boolean', 'int',
     *   'string', 'date', 'datetime', or 'id' and $alias is a column alias
     */
    public function __construct($fields)
    {
        foreach ($fields as $name => $value) {
            if (!is_array($value)) {
                $value = [$value, $name];
            } elseif (sizeof($value) == 0 || sizeof($value) > 2) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid field definition: expected " .
                            "(type, alias); found " . Error::formatValue($value)
                    ]);
            }
            switch ($value[0]) {
            case 'boolean':
            case 'int':
            case 'string':
            case 'date':
            case 'datetime':
            case 'id':
                break;
            default:
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid type for field '$name': expected " .
                            "'boolean', 'int', 'string', 'date', " .
                            "'datetime', or 'id'; found " .
                            Error::formatValue($value[0])
                    ]);
            }
            $this->fields[$name] = $value;
        }
    }

    /**
     * Returns the type of the named field.
     *
     * @param string $field The field name
     * @throws CodeRage\Error if no field with the given name exists
     */
    public function fieldType($field)
    {
        if (!isset($this->fields[$field]))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "No such field: $field"
                ]);
        return $this->fields[$field][0];
    }

    /**
     * Returns the column alias of the named field.
     *
     * @param string $field The field alias
     * @throws CodeRage\Error if no field with the given name exists
     */
    public function fieldAlias($field)
    {
        if (!isset($this->fields[$field]))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "No such field: $field"
                ]);
        return $this->fields[$field][1];
    }


    /**
     * Adds a filter.
     *
     * @param string $field The field name
     * @param string $filterType One of the strings 'equals', 'notEquals',
     *   'contains', 'notContains', 'lessThan', 'greaterThan', 'matches', or
     *   'notMatches'
     * @param string $value The value to which the field is to be compared;
     *   this value should be the raw value passed to the webservice, before
     *   type conversions
     */
    public final function addFilter($field, $filterType, $value)
    {
        if (!isset($this->fields[$field]))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "No such field: $field"
                ]);
        $type = $this->fields[$field][0];
        Types::validate($value, $type, "filter value for field '$field'");
        if ($type == 'boolean') {
            $value = is_bool($value) ?
                $value :
                $value === 'true' || $value === 1;
        } elseif ($type == 'id') {
            $tail = preg_replace('/.*-([0-9a-f]+)$/', '$1', $value);
            $value = \CodeRage\Util\ObjectId::decode($tail);
        } elseif ($type == 'datetime') {
            $value = date_create($value)->format('U');
        }
        switch ($filterType) {
        case 'equals':
        case 'notEquals':
            break;
        case 'lessThan':
        case 'greaterThan':
            if ($type == 'id')
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid filter type '$filterType' for field " .
                            "'$field' of type object ID"
                    ]);
            break;
        case 'contains':
        case 'notContains':
        case 'matches':
        case 'notMatches':
            if ($type != 'string')
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid filter type '$filterType' for field " .
                            "'$field' of type $type"
                    ]);
            if ($filterType == 'contains' || $filterType == 'notContains')
                $value = '%' . addcslashes($value, '%_') . '%';
            else
                $value =
                    str_replace(
                        ['*', '?'],
                        ['%', '_'],
                        addcslashes($value, '%_')
                    );
            break;
        default:
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid filter type '$filterType'"
                ]);
        }
        $this->filters[] = [$field, $filterType, $value];
    }

    /**
     * Adds a sort specifier
     *
     * @param string $field The field name
     * @param string $direction On of the strings 'ascending' or 'descending'
     */
    public final function addSortSpecifier($field, $direction = 'ascending')
    {
        if (!isset($this->fields[$field]))
            throw new Error(['details' => "Invalid field: $field"]);
        Types::validate($direction, 'string', 'sort direction');
        if ($direction != 'ascending' && $direction != 'descending')
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid sort direction: expected 'ascending' or " .
                        "'descending'; found '$direction'"
                ]);
        $this->sortSpecifiers[] = [$field, $direction];
    }

    /**
     * Restricts the search to a subset of the collection of all search results
     *
     * @param int $begin The $index of the beginning of the subset of search
     *   results to be returned, starting at 1
     * @param int $end The $index of one past the end of the subset of search
     *   results to be returned, starting at 1
     */
    public final function setRange($begin, $end)
    {
        Types::validate($begin, 'int', 'search offset');
        Types::validate($end, 'int', 'search offset');
        if ($begin <= 0 || $end <= $begin)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid search range: [$begin, $end)"
                ]);
        $this->begin = $begin;
        $this->end = $end;
    }

    /**
     * If this search contains filters, returns a pair ($sql, $args), where
     * $sql is a SQL condition suitable for adding to a WHERE clause and $args
     * is an array of values to be bound to the placeholders embedded in
     * $where. If this search contains no filters, returns false.
     *
     * @return mixed
     */
    public final function where()
    {
        if (!sizeof($this->filters))
            return false;
        $conditions = $values = [];
        foreach ($this->filters as $spec) {
            list($field, $filterType, $value) = $spec;
            list($type, $alias) = $this->fields[$field];
            $ph = $type == 'int' || $type == 'id' ?
                '%i' :
                '%s';
            switch ($filterType) {
            case 'equals':
                $conditions[] = "$alias = $ph";
                break;
            case 'notEquals':
                $conditions[] = "$alias != $ph";
                break;
            case 'lessThan':
                $conditions[] = "$alias <= $ph";
                break;
            case 'greaterThan':
                $conditions[] = "$alias >= $ph";
                break;
            case 'contains':
            case 'matches':
                $conditions[] = "$alias LIKE $ph";
                break;
            case 'notContains':
            case 'notMatches':
                $conditions[] = "$alias NOT LIKE $ph";
                break;
            }
            $values[] = $value;
        }
        return ['(' . join(' AND ', $conditions) . ')', $values];
    }

    /**
     * If this search contains sort specifiers, returns the column list of an
     * ORDER BY clause; otherwise returns an empty string.
     */
    public final function orderBy()
    {
        if (!sizeof($this->sortSpecifiers))
            return '';
        $columns = [];
        foreach ($this->sortSpecifiers as $spec) {
            list($field, $direction) = $spec;
            $alias = $this->fields[$field][1];
            $columns[] = $alias . ($direction == 'descending' ? ' DESC' : '');
        }
        return join(', ', $columns);
    }

    /**
     * If this search is restricted to a range, returns a MySQL LIMIT clause
     */
    public final function limit()
    {
        return $this->begin !== null ?
            'LIMIT ' . ($this->begin - 1) . ', ' . ($this->end - $this->begin) :
            false;
    }
}
