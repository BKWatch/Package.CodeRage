<?php

/**
 * Defines the class CodeRage\Db\Result
 *
 * File:        CodeRage/Db/Result.php
 * Date:        Tue Jul 14 20:19:59 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use PDO;
use PDOException;
use PDOStatement;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Encapsulates a query result
 */
final class Result extends Object_ {

    /**
     * Maps legacy MDB2 fetch modes to PDO fetch modes
     *
     * @var array
     */
    const FETCH_MODE =
        [
	        self::FETCHMODE_ORDERED => PDO::FETCH_NUM,
	        self::FETCHMODE_ASSOC => PDO::FETCH_ASSOC,
	        self::FETCHMODE_OBJECT => PDO::FETCH_OBJ
        ];

    /**
     * Maps integral type specifiers to PHP type names
     *
     * @var array
     */
    const TYPE_NAME =
        [
	        self::TYPE_INT => 'int',
	        self::TYPE_FLOAT => 'float',
	        self::TYPE_DECIMAL => 'string',
	        self::TYPE_STRING => 'string',
	        self::TYPE_BLOB => 'string'
        ];

    /**
     * Constructs a instance of CodeRage\Db\Result
     *
     * @param PDOStatment $result
     * @param array $columns An array of type specifiers of the form
     *   CodeRage\Db::TYPE_XXX describng the column types of the result set;
     *   a null value indicates that no type conversion will be performed
     */
    public function __construct(PDOStatement $result, $columns)
    {
        if ($columns !== null) {
            Args::check($columns, 'list[int]', 'column types');
            foreach ($columns as $i => $t)
                self::checkType($t, "column type at position $i");
        }
        $this->result = $result;
        $this->columns = $columns;
    }

    /**
     * Returns the next row of query results, as an indexed array
     *
     * @param int $mode One of the constants CodeRage\Db::FETCHMODE_XXX,
     *   indicating how rows are represented; defaults to FETCHMODE_ORDERED
     * @return array
     * @throws CodeRage\Error
     */
    public function fetchRow($mode = self::FETCHMODE_ORDERED)
    {
        Args::check($mode, 'int', 'fetch mode');
        if (!array_key_exists($mode, self::FETCH_MODE))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid fetch mode: $mode"
                ]);
        if ($this->result === null)
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' => 'Database resource has already been freed'
                ]);
        try {
            $row = $this->result->fetch(self::FETCH_MODE[$mode]);
        } catch (PDOException $e) {
            $this->free();
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'inner' => $e
                ]);
        }
        if ($row === false) {

            // There are no additional rows to fetch
            $this->free();
            return null;
        }
        if ($this->columns !== null)
            $this->processTypes($row);
        return $row;
    }

    /**
     * Returns the next row, as an associative array
     *
     * @return array
     * @throws CodeRage\Error
     */
    public function fetchArray()
    {
        return $this->fetchRow(self::FETCHMODE_ASSOC);
    }

    /**
     * Returns the next row, as an object.
     *
     * @return object
     * @throws CodeRage\Error
     */
    public function fetchObject()
    {
        return $this->fetchRow(self::FETCHMODE_OBJECT);
    }

    /**
     * Returns the collection of all rows or query results, as a list of arrays
     *
     * @param int $mode One of the constants CodeRage\Db::FETCHMODE_XXX,
     *   indicating how rows are represented; defaults to FETCHMODE_ORDERED
     * @return array
     */
    public function fetchAll($mode = self::FETCHMODE_ORDERED)
    {
        Args::check($mode, 'int', 'fetch mode');
        if (!array_key_exists($mode, self::FETCH_MODE))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid fetch mode: $mode"
                ]);
        if ($this->result === null)
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' => 'Database resource has already been freed'
                ]);
        try {
            $rows = $this->result->fetchAll(self::FETCH_MODE[$mode]);
        } catch (PDOException $e) {
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'inner' => $e
                ]);
        } finally {
            $this->free();
        }
        if ($this->columns !== null)
            foreach ($rows as &$row)
                $this->processTypes($row);
        $this->free();
        return $rows;
    }

    /**
     * Frees the underlying result
     *
     * @throws CodeRage\Error
     */
    public function free($throwOnError = false)
    {
        if ($this->result === null)
            return;
        try {
            $this->result->closeCursor();
            $this->result = null;
        } catch (PDOException $e) {
            if ($throwOnError)
                throw new
                    Error([
                        'status' => 'DATABASE_ERROR',
                        'details' => 'Failed freeing query result',
                        'inner' => $e
                    ]);
        }
    }

    /**
     * Converts columns to the requested data type
     *
     * @param array $row A row of query results
     */
    private function processTypes(&$row)
    {
        $index = 0;
        foreach ($row as $k => &$v) {
            if (!isset($this->columns[$index]))
                return;
            if ($v !== null)
                settype($v, self::TYPE_NAME[$this->columns[$index]]);
			++$index;
        }
    }

    /**
     * An PDO query result
     *
     * @var PDOStatement
     */
    private $result;

    /**
     * An array of type specifieds of the for CodeRage\Db::TYPE_XXX
     *
     * @var array
     */
    private $columns;
}
