<?php

/**
 * Defines the functions CodeRage\Db\createDatabase, CodeRage\Db\dropDatabase, and
 * CodeRage\Db\listDatabases
 *
 * File:        CodeRage/Db/Operations.php
 * Date:        Fri Jun 22 18:13:32 MDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use Throwable;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Os;

/**
 * @ignore
 */

/**
 * Container for static methods for viewing and modifying database schemas
 */
final class Operations {

    /**
     * Creates the given database
     *
     * @param string $definition The path to an XML document containing a data
     *   source or database definition
     * @param CodeRage\Db\Params $params The connection parameters;
     *   defaults to the parameters of the default datasource
     * @throws CodeRage\Error If an error occurs
     */
    public static function createDatabase($definition, $params = null)
    {
        if ($params === null) {
            $db = new Db;
            $params = $db->params();
        }
        $command = 'xmltodb --create --non-interactive';
        foreach (
            ['dbms', 'host', 'port', 'username', 'password', 'database']
            as $n )
        {
            $v = $params->$n();
            if ($v !== null)
                $command .= " --$n " . escapeshellarg($v);
        }
        $command .= ' ' . escapeshellarg($definition);
        try {
            Os::run($command);
        } catch (Throwable $e) {
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' => 'Failed creating database',
                    'inner' => $e
                ]);
        }
    }

    /**
     * Drops the named database
     *
     * @param string $name The database name; defaults to the database name of
     *   the default data source
     * @param CodeRage\Db\Params $params The connection parameters; defaults
     *   to the parameters of the default datasource
     * @throws CodeRage\Error If an error occurs
     */
    public static function dropDatabase($name = null, $params = null)
    {
        if ($params === null) {
            $db = new Db;
            $params = $db->params();
        }
        $params =
            new Params([
                    'dbms' => $params->dbms(),
                    'host' => $params->host(),
                    'port' => $params->port(),
                    'username' => $params->username(),
                    'password' => $params->password(),
                    'database' => $name
                ]);
        $db = new Db(['params' => $params]);
        try {
            $db->query("DROP DATABASE [$name]");
        } finally {
            $db->disconnect();
        }
    }

    /**
     * Returns the list of names of existing databases accessible using the
     * given connection parameters
     *
     * @param CodeRage\Db\Params $params The connection parameters; defaults
     *   to the parameters of the default datasource
     * @throws CodeRage\Error
     */
    public static function listDatabases($params = null)
    {
        if ($params === null) {
            $db = new Db;
            $params = $db->params();
        }

        // Define command
        $command = 'xmltodb --list ';
        foreach (['dbms', 'host', 'port', 'username', 'password'] as $n) {
            $v = $params->$n();
            if ($v !== null)
                $command .= " --$n " . escapeshellarg($v);
        }

        // Execute command
        $output = null;
        try {
            $output = Os::run($command);
        } catch (Throwable $e) {
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' => 'Failed listing databases',
                    'inner' => $e
                ]);
        }

        // Parse output
        return explode("\n", rtrim($output));
    }

    /**
     * Returns the list of names of tables in the database accessible using the
     * given connection parameters
     *
     * @param CodeRage\Db\Params $params The connection parameters; defaults
     *   to the parameters of the default datasource
     * @throws CodeRage\Error
     */
    public static function listTables($params = null)
    {
        if ($params === null) {
            $db = new Db;
            $params = $db->params();
        }

        // Define command
        $command = 'xmltodb --list-tables ';
        foreach (['dbms', 'host', 'port', 'username', 'password', 'database'] as $n) {
            $v = $params->$n();
            if ($v !== null)
                $command .= " --$n " . escapeshellarg($v);
        }

        // Execute command
        $output = null;
        try {
            $output = Os::run($command);
        } catch (Throwable $e) {
            throw new
                Error([
                    'status' => 'DATABASE_ERROR',
                    'details' => 'Failed listing databases',
                    'inner' => $e
                ]);
        }

        // Parse output
        return explode("\n", rtrim($output));
    }
}
