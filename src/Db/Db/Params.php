<?php

/**
 * Contains the definition of the class CodeRage\Db\Params
 *
 * File:        CodeRage/Db/Params.php
 * Date:        Mon Apr 23 20:38:13 MDT 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Represents the information required to establish a database connection
 */
final class Params {

    /**
     * @var array
     */
    const OPTIONS =
        [ 'dbms' => 1, 'host' => 1, 'port' => 1, 'username' => 1,
          'password' => 1, 'database' => 1, 'options' => 1 ];

    /**
     * Constructs a CodeRage\Db\Params.
     *
     * @param mixed $options An associative array of parameters with keys
     *   among the following:
     *     dmbs - The database engine, e.g., 'mysql', 'mssql, 'pgsql'
     *     host - The host name or IP addresss of the server
     *     port - The port
     *     username - The username
     *     password - The password
     *     database - The initial database
     *     options - An associative array of string-valued options
     */
    public function __construct(array $options)
    {
        Args::check($options, 'map', 'options');
        foreach ($options as $n => $v) {
            if (!array_key_exists($n, self::OPTIONS))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Unsupported option: $n"
                    ]);
        }
        Args::checkKey($options, 'dbms', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'host', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'port', 'string');
        Args::checkKey($options, 'username', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'password', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'options', 'map[string]', [
            'default' => []
        ]);
        foreach (array_keys(self::OPTIONS) as $n)
            $this->$n = $options[$n] ?? null;
    }

    /**
     * Returns the type of DBMS, e.g., 'mssql' or 'mysql'
     *
     * @return string
     */
    function dbms() { return $this->dbms; }

    /**
     * Returns the host name or IP address
     *
     * @return string
     */
    function host() { return $this->host; }

    /**
     * Returns a port number, or null
     *
     * @return int
     */
    function port() { return $this->port; }

    /**
     * Returns the username
     *
     * @return string
     */
    function username() { return $this->username; }

    /**
     * Returns the password
     *
     * @return string
     */
    function password() { return $this->password; }

    /**
     * Returns the name of the initial database
     *
     * @return string
     */
    function database() { return $this->database; }

    /**
     * Returns the connection options, as a string-valued associative array
     *
     * @return array
     */
    function options() { return $this->options; }

    /**
     * The type of DBMS, e.g., 'mssql' or 'mysql'
     *
     * @var string
     */
    private $dbms;

    /**
     * A host name or IP address
     *
     * @var string
     */
    private $host;

    /**
     * A port number, or null
     *
     * @var int
     */
    private $port;

    /**
     * The username
     *
     * @var string
     */
    private $username;

    /**
     * The password
     *
     * @var string
     */
    private $password;

    /**
     * The name of the initial database
     *
     * @var string
     */
    private $database;

    /**
     * The connection options
     *
     * @var array
     */
    private $options;
}
