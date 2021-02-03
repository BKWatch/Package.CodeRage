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

use CodeRage\Build\ProjectConfig;
use CodeRage\Error;
use CodeRage\Text\Regex;
use CodeRage\Util\Args;


/**
 * Represents the information required to establish a database connection
 */
final class Params {

    /**
     * @var array
     */
    public const OPTIONS =
        [ 'dbms' => 1, 'host' => 1, 'port' => 1, 'username' => 1,
          'password' => 1, 'database' => 1, 'options' => 1 ];

    /**
     * @var string
     */
    private const MATCH_OPTION = '/^db\.option\.(.+)/';

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
        Args::checkIntKey($options, 'port');
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
    public function dbms(): string { return $this->dbms; }

    /**
     * Returns the host name or IP address
     *
     * @return string
     */
    public function host(): string  { return $this->host; }

    /**
     * Returns a port number, or null
     *
     * @return int
     */
    public function port(): ?int  { return $this->port; }

    /**
     * Returns the username
     *
     * @return string
     */
    public function username(): string  { return $this->username; }

    /**
     * Returns the password
     *
     * @return string
     */
    public function password(): string  { return $this->password; }

    /**
     * Returns the name of the initial database
     *
     * @return string
     */
    public function database(): ?string  { return $this->database; }

    /**
     * Returns the connection options, as a string-valued associative array
     *
     * @return array
     */
    public function options(): array { return $this->options; }

    /**
     * Returns an identifier with the property that two parameters objects with
     * the same identifier are semantically equivalent
     *
     * @return string
     */
    public function id(): string
    {
        if ($this->id === null) {
            $params = [];
            foreach (self::OPTIONS as $name => $ignore) {
                $params[$name] = $this->$name();
            }
            ksort($params['options']);
            $this->id = json_encode($params);
        }
        return $this->id;
    }

    /**
     * Returns a newly constructed instance of CodeRage\Db\Params with parameter
     * values read from the specified configuration
     *
     * @param CodeRage\Build\ProjectConfig $config
     */
    public static function create(ProjectConfig $config) : self
    {
        $options = [];
        foreach (self::OPTIONS as $n => $ignore) {
            if ($n !== 'options') {
                $options[$n] = $config->getProperty("db.$n");
            }
        }
        foreach ($config->propertyNames() as $name) {
            $opt = Regex::getMatch(self::MATCH_OPTION, $name, 1);
            if ($opt !== null) {
                if (!isset($options['options'])) {
                    $options['options'] = [];
                }
                $options['options'][$opt] = $config->getProperty($name);
            }
        }
        return new self($options);
    }

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

    /**
     * An identifier with the property that two parameters objects with the same
     * identifier are semantically equivalent
     *
     * @var array
     */
    private $id;
}
