<?php

/**
 * Defines the class CodeRage\Db\QueryProcessor
 *
 * File:        CodeRage/Db/Deleter.php
 * Date:        Sun Dec 16 13:52:58 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db;

use DOMElement;
use Throwable;
use CodeRage\Db\Operations;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Xml;


/**
 * Allow recently added records in a collection of tables to be deleted, leaving
 * only those records that existed at a specific time in the past
 */
final class Deleter extends Schema\ParserBase {

    /**
     * @var string
     */
    const SCHEMA_PATH = 'Schema/dataSource.xsd';

    /**
     * Constructs a CodeRage\Db\Deleter
     *
     * @param array $options The options array; supports the following options:
     *     path - The path to a file conforming to the schema
     *       CodeRage/Db/Schema/datsSource.xsd
     *     tables - The list of names of tables from which records are to be
     *       deleted
     *     db - A database connection (optional)
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'path', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'tables', 'list[string]', [
            'required' => true
        ]);
        Args::checkKey($options, 'db', 'CodeRage\\Db');

        // Definite foreign key relationships
        $foreignKeys = self::parseForeignKeys($options['path']);

        // Collect names of tables that exist in the current database;
        // some tables in the schema may not have been created
        $exists = [];
        foreach (Operations::listTables() as $t)
            $exists[$t] = 1;

        // Form closure of tables under foreign key relationships
        $tables = [];
        $stack = $options['tables'];
        while (!empty($stack)) {
            $t = array_pop($stack);
            $tables[$t] = 1;
            if (isset($foreignKeys[$t])) {
                foreach (array_keys($foreignKeys[$t]) as $t2) {
                    if (isset($exists[$t2]))
                        $stack[] = $t2;
                }
            }
        }
        $tables = array_keys($tables);

        // Sort tables
        Array_::topologicalSort($tables, function ($a, $b) use($foreignKeys)
        {
            if (isset($foreignKeys[$a][$b])) {
                return 1;
            } elseif (isset($foreignKeys[$b][$a])) {
                return -1;
            } else {
                return null;
            }
        });

        // Initialize instance
        $this->db = isset($options['db']) ? $options['db'] : new \CodeRage\Db;
        $this->tables = $tables;
    }

    /**
     * Records the maximum primary key values in the underlying list of tables,
     * so that delete() can remove all newer records
     */
    public function snapshot()
    {
        $this->maxIds = [];
        foreach ($this->tables as $t) {
            $max = $this->db->fetchValue("SELECT MAX(RecordID) {i} FROM [$t]");
            $this->maxIds[$t] = $max !== null ? $max : 0;
        }
    }

    /**
     * Deletes records in the underlying list of tables whose primary key values
     * exceed the maximum value at the time that snapshot() was last invoked
     */
    public function restore()
    {
        if ($this->maxIds === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' => 'No snapshot available'
                ]);
        $this->db->beginTransaction();
        try {
            foreach ($this->maxIds as $table => $max) {
                if ($max !== null) {
                    $this->db->query(
                        "DELETE FROM [$table]
                         WHERE RecordID > $max"
                    );
                }
            }
        } catch (Throwable $e) {
            $this->db->rollback();
            throw new
                Error([
                    'details' => 'Failed deleting new records in table list',
                    'inner' => Error::wrap($e)
                ]);
        }
        $this->db->commit();
    }

    /**
     * Parses the given data source, database, or table set definition and
     * returns an associative array containing the forign key relationship
     * between the described tables
     *
     * @param string $path The path to a file conforming to the schema
     *   CodeRage/Db/Schema/datsSource.xsd
     * @return array An associative array mapping names of tables to
     *   associative arrays whose keys are the names of tables that reference
     *   the table in foreign key constraints
     */
    public static function parseForeignKeys($path)
    {
        $dom = self::loadDom($path);
        $foreignKeys = [];
        self::parseForeignKeysImpl($dom->documentElement, $foreignKeys);
        return $foreignKeys;
    }

    /**
     * Helper for parseForeignKeys()
     *
     * @param DOMElement $elt An XML element with local name "dataSource",
     *   "database", "include", or "tables" in an XML document conforming to the
     *   schema CodeRage/Db/Schema/datsSource.xsd
     * @param array $foreignKeys An associative array mapping names of tables to
     *   associative arrays whose keys are the names of tables that reference
     *   the table in foreign key constraints
     */
    private static function parseForeignKeysImpl(DOMElement $elt, array &$foreignKeys)
    {
        self::processTables($elt, $foreignKeys);
        foreach (Xml::childElements($elt) as $kid) {
            $localName = $kid->localName;
            if ( $localName == 'database' ||
                 $localName == 'include' ||
                 $localName == 'tables' )
            {
                $kid = self::processSrcAttribute($kid);
                self::parseForeignKeysImpl($kid, $foreignKeys);
            }
        }
    }

    /**
     * Helper for parseForeignKeys()
     *
     * @param DOMElement $elt An XML element with local name "dataSource",
     *   "database", "include", or "tables" in an XML document conforming to the
     *   schema CodeRage/Db/Schema/datsSource.xsd
     * @param array $foreignKeys An associative array mapping names of tables to
     *   associative arrays whose keys are the names of tables that reference
     *   the table in foreign key constraints
     * @return DOMElement The original element, or the document element of the
     *   file referenced by the "src" attribute
     */
    private static function processTables(DOMElement $elt, array &$foreignKeys)
    {
        foreach (Xml::childElements($elt, 'table') as $table) {
            $name = $table->getAttribute('name');
            foreach (Xml::childElements($table, 'foreignKey') as $key) {
                $refTable = $key->getAttribute('refTable');
                $foreignKeys[$refTable][$name] = 1;
            }
        }
    }

    /**
     * Helper for parseForeignKeys()
     *
     * @param DOMElement $elt An XML element that may have a "src" element
     * @return DOMElement The original element, or the document element of the
     *   file referenced by the "src" attribute
     */
    private static function processSrcAttribute(DOMElement $elt)
    {
        if (!$elt->hasAttribute('src'))
            return $elt;
        $path = self::resolveSrcAttribute($elt);
        $dom = self::loadDom($path);
        return $dom->documentElement;
    }

    /**
     * Wrapper for CodeRage\Xml\loadDom() that validates $path against the
     * schema CodeRage/Db/Schema/dataSource.xsd and ensures that the file
     * has a document element
     *
     * @param string $path The path to an XML document
     */
    private static function loadDom($path)
    {
        $dom = Xml::loadDocument($path, __DIR__ . '/' . self::SCHEMA_PATH);
        if ($dom->documentElement === null)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' => "Missing document element at $path"
                ]);
        return $dom;
    }

    /**
     * The database connection
     *
     * @var CodeRage\Db
     */
    private $db;

    /**
     * The list of tables from which records are to be deleted by delete(), in
     * order of deletion
     *
     * @var array
     */
    private $tables;

    /**
     * Maps table names to the maximum values of the primary key columns at the
     * time of construction
     *
     * @var array
     */
    private $maxIds;
}
