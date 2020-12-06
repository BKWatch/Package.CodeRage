<?php

/**
 * Contains the definition of the class CodeRage\Db\Schema\Database
 *
 * File:        CodeRage/Db/Schema/Database.php
 * Date:        Mon Apr 23 20:38:13 MDT 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db\Schema;

/**
 * Represents a database
 */
final class Database {

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this database
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * The simple name of this database, as used by the DBMS
     *
     * @var string
     */
    private $name;
}
