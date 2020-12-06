<?php

/**
 * Contains the definition of the class CodeRage\Db\DataSource
 *
 * File:        CodeRage/Db/Schema/DataSource.php
 * Date:        Mon Apr 23 20:38:13 MDT 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db\Schema;

use CodeRage\Db\Params;

/**
 * @ignore
 */

/**
 * Represents a database schema together with connection parameters
 */
final class DataSource {

    /**
     * Constructs a CodeRage\Db\DataSource.
     *
     * @param CodeRage\Db\Params $params
     * @param CodeRage\Db\Schema\Database $database
     * @param string $name The data source name, if any.
     */
    public function __construct(Params $params,
        Database $database, $name = null)
    {
        $this->params = $params;
        $this->database = $database;
        $this->name = $name;
    }

    /**
     * Returns the data source name, if any.
     *
     * @return string
     */
    public function name()
    {
      return $this->name;
    }

    /**
     * Returns an instance of CodeRage\Db\Params
     *
     * @return CodeRage\Db\Params
     */
    public function params()
    {
      return $this->params;
    }

    /**
     * Returns an instance of CodeRage\Db\Schema\Database
     *
     * @return CodeRage\Db\Schema\Database
     */
    public function database()
    {
      return $this->database;
    }

    /*
     * Sets the database instance
     *
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @var CodeRage\Db\Params
     */
    private $params;

    /**
     * An instance of CodeRage\Db\Schema\Database
     *
     * @var CodeRage\Db\Schema\Database
     */
    private $database;

    /**
     * The data source name, if any.
     *
     * @var string
     */
    private $name;
}
