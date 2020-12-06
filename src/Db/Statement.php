<?php

/**
 * Defines the class CodeRage\Db\Statement
 *
 * File:        CodeRage/Db/Statement.php
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
use PDOStatement;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Encapsulates a prepared statement
 */
class Statement extends Object_ {

    /**
     * Maps integral CodeRage type sapecifiers to values of the form
     *   PDO:PARAM_XXX
     *
     * @var array
     */
    const PARAM_TYPE =
        [
	        self::TYPE_INT => PDO::PARAM_INT,
	        self::TYPE_FLOAT => PDO::PARAM_STR,
	        self::TYPE_DECIMAL => PDO::PARAM_STR,
	        self::TYPE_STRING => PDO::PARAM_STR,
	        self::TYPE_BLOB => PDO::PARAM_LOB
        ];

    /**
     * Constructs a CodeRage\Db\Statement
     *
     * @param PDOStatment $statement
     * @param array $params An array of type specifiers of the form
     *   CodeRage\Db::TYPE_XXX describng the parameter types
     * @param array $columns An array of type specifiers of the form
     *   CodeRage\Db::TYPE_XXX describng the column types of the result set;
     *   a null value indicates that no type conversion will be performed
     */
    public function __construct(PDOStatement $statement, $params, $columns)
    {
        Args::check($params, 'list[int]', 'parameter types');
        foreach ($params as $i => $t)
            self::checkType($t, "parameter type at position $i");
        if ($columns !== null) {
            Args::check($columns, 'list[int]', 'column types');
            foreach ($columns as $t)
                self::checkType($t, "column type at position $i");
        }
        $this->statement = $statement;
        $this->params = $params;
        $this->columns = $columns;
    }

    /**
     * Executes the underlying prepared statement
     *
     * @param array $args The values to bind to the placeholders in the
     *   underlying statement
     * @return CodeRage\Db\Result
     * @throws CodeRage\Error
     */
    public function execute(array $args)
    {
        Args::check($args, 'list', 'parameter values');
        if (count($args) !== count($this->params))
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' =>
                        'Incorrect number of parameter values; expected ' .
                        count($this->params) . '; found ' . count($args)
                ]);
        if ($this->statement == null)
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' => 'Database resource has already been freed'
                ]);
        try {
            foreach ($args as $i => &$arg) {
                $type = $arg !== null ?
                    self::PARAM_TYPE[$this->params[$i]] :
                    PDO::PARAM_NULL;
                $this->statement->bindParam($i + 1, $arg, $type);
            }
            $this->statement->execute();
        } catch (PDOException $e) {
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'inner' => $e
                ]);
        }
        return new Result($this->statement, $this->columns);
    }

    /**
     * Frees the underlying statement.
     *
     * @throws CodeRage\Error
     */
    public function free($throwOnError = false)
    {
        if ($this->statement == null)
            return;
        try {
            $this->statement->closeCursor();
            $this->statement = null;
        } catch (PDOException $e) {
            if ($throwOnError)
                throw new
                    Error([
                        'status' => 'DATABASE_ERROR',
                        'details' => 'Failed freeing prepared statement',
                        'inner' => $e
                    ]);
        }
    }

    /**
     * A PDO statement
     *
     * @var PDOStatement
     */
    private $statement;

    /**
     * An array of type specifieds of the for CodeRage\Db::TYPE_XXX
     *
     * @var array
     */
    private $params;

    /**
     * An array of type specifieds of the for CodeRage\Db::TYPE_XXX
     *
     * @var array
     */
    private $columns;
}
