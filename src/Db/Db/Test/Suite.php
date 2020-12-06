<?php

/**
 * Defines the class CodeRage\Db\Test\Suite
 *
 * File:        CodeRage/Db/Test/Suite.php
 * Date:        Mon Jul 10 16:08:55 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CodeRage
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Db\Test;

use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Db\Operations;
use CodeRage\Db\QueryProcessor;
use CodeRage\Test\Assert;
use CodeRage\Util\Random;


/**
 * Test suite for the class CodeRage\Db
 */
final class Suite extends \CodeRage\Test\ReflectionSuite {

    /**
     * The path to the XML file defining the test database
     *
     * @var string
     */
    const SCHEMA = __DIR__ . '/database.tbx';

    /**
     * The list of table names used for testing
     *
     * @var array
     */
    const TABLES = ['TestTable1'];

    /**
     * @var int
     */
    const RANDOM_STRING_LENGTH = 30;

    /**
     * Constructs an instance of \CodeRage\Db\Test\Suite
     */
    public function __construct()
    {
        parent::__construct('CodeRage.Db', 'Tests the class CodeRage\Db');
    }

    /**
     * Tests query with type specifier {i} and {s}
     */
    public function testQuery1()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Blue')");
        $sql =
            'SELECT CreationDate {i}, RecordId {i}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->query($sql, 1)->fetchRow();
        Assert::equal(
            $result,
            [0, 1, 'James', 'Blue']
        );
    }

    /**
     * Test query with type specifier {s}
     */
    public function testQuery2()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Blue')");
        $sql =
            'SELECT CreationDate {s}, RecordId {s}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->query($sql, 1)->fetchRow();
        Assert::equal(
            $result,
            ['0', '1', 'James', 'Blue']
        );
    }

    /**
     * Test query with type specifier {f}
     */
    public function testQuery3()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Blue')");
        $sql =
            'SELECT RecordId {f}
             FROM TestTable1';
        $result = $db->query($sql)->fetchRow();
        Assert::equal(
            $result,
            [1.0]
        );
    }

    /**
     * Test query with type specifier for first column only
     */
    public function testQuery4()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Blue')");
        $sql =
            'SELECT CreationDate {i}, RecordId, name, color
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->query($sql, 1)->fetchRow();
        Assert::equal(
            $result,
            [0, '1', 'James', 'Blue']
        );
    }

    /**
     * Test query with multiple parameters
     */
    public function testQuery5()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Blue')");
        $sql =
            'SELECT RecordId {i}
             FROM TestTable1
             WHERE name = %s AND
                   color = %s';
        $result = $db->query($sql, ['James', 'Blue'])->fetchRow();
        Assert::equal(
            $result,
            [1]
        );
    }

    /**
     * Test query with invalid type specifiers
     */
    public function testQueryFailure4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Blue')");
        $sql =
            'SELECT CreationDate {x}
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->query($sql, 1)->fetchRow();
    }

    public function testRunQueryWithNonExistingTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $sql = 'SELECT * FROM XXX WHERE RecordId = %i';
        $result = $db->query($sql, 1);
    }

    public function testInsert()
    {
        $db = $this->db();
        $id =
            $db->insert(
                'TestTable1',
                [
                    'name' => 'John',
                    'color' => 'Blue'
                ]
            );
        $sql =
            'SELECT name {s}, color {s}
             FROM TestTable1';
         $result = $db->fetchFirstRow($sql, []);
         Assert::equal($result, ['John', 'Blue']);
         $result = $db->fetchValue('SELECT RecordID {i} FROM TestTable1');
         Assert::equal($id, $result);
    }

    public function testInsertWithNonExistingTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $db->insert(
            'XXX',
            [
                'RecordId' => 1,
                'name' => 'John',
                'color' => 'Blue'
            ]
        );
    }

    public function testUpdateUsingRecordId()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'John', 'Orange')");
        $db->update(
            'TestTable1',
            [
                'name' => 'Robert',
                'color' => 'Blue'
            ],
            1
        );
        $sql =
            'SELECT name, color
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->fetchFirstRow($sql, 1);
        Assert::equal($result, ['Robert', 'Blue']);
    }

    public function testUpdateUsingMultipleArguments()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'John', 'Orange')");
        $db->update(
            'TestTable1',
            [
                'RecordId' => 2
            ],
            [
                'name' => 'John',
                'color' => 'Orange'
            ]
        );
        $sql =
            'SELECT RecordId {i}
             FROM TestTable1
             WHERE name = %s
               AND color = %s';
        $result = $db->fetchValue($sql, ['John', 'Orange']);
        Assert::equal($result, 2);
    }

    public function testInsertOrUpdate1()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'John', 'Orange')");

        // Updates the name and color of record with record ID 1
        $db->insertOrUpdate(
            'TestTable1',
            [
                'name' => 'Robert',
                'color' => 'Blue'
            ],
            1
        );
        $sql =
            'SELECT name, color
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->fetchFirstRow($sql, 1);
        Assert::equal($result, ['Robert', 'Blue']);
    }

    public function testInsertOrUpdate2()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'John', 'Orange')");

        // Updates the ID of record with name 'John' and color 'Orange'
        $db->insertOrUpdate(
            'TestTable1',
            ['RecordId' => 2],
            [
                'name' => 'John',
                'color' => 'Orange'
            ]
        );
        $sql =
            'SELECT RecordId {i}
             FROM TestTable1
             WHERE name = %s
               AND color = %s';
        $result = $db->fetchValue($sql, ['John', 'Orange']);
        Assert::equal($result, 2);
    }

    public function testInsertOrUpdate3()
    {
        $db = $this->db();

        // Inserts a new record with name 'John'
        $db->insertOrUpdate(
            'TestTable1',
            [
                'name' => 'John',
                'color' => 'Orange'
            ],
            [
                'name' => 'John'
            ]
        );
        $sql =
            'SELECT color
             FROM TestTable1
             WHERE name = %s';
        $result = $db->fetchValue($sql, ['John']);
        Assert::equal($result, 'Orange');
    }

    public function testInsertOrUpdate4()
    {
        $db = $this->db();

        // Inserts a new record with ID 1
        $db->insertOrUpdate(
            'TestTable1',
            [
                'name' => 'John',
                'color' => 'Orange'
            ],
            1
        );
        $sql =
            'SELECT name, color
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->fetchFirstRow($sql, 1);
        Assert::equal($result, ['John', 'Orange']);
    }

    public function testInsertOrUpdateWithNonExistingTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $db->insertOrUpdate(
            'XXX',
            [
                'RecordId' => 1,
                'name' => 'John',
                'color' => 'Blue'
            ],
            1
        );
    }

    public function testDeleteWithName()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->delete('TestTable1', [ 'name' => 'Michael' ]);
        $sql =
            'SELECT name {s}
             FROM TestTable1
             WHERE RecordId = %s';
        $result = $db->fetchValue($sql, 1);
        Assert::equal($result, null);
    }

    public function testDeleteWithRecordId()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->delete('TestTable1', 1);
        $sql =
            'SELECT name {s}
             FROM TestTable1
             WHERE RecordId = %s';
        $result = $db->fetchValue($sql, 1);
        Assert::equal($result, null);
    }

    public function testDeleteNonExistentRecord()
    {
        $db = $this->db();
        $db->delete('TestTable1', 1);
    }

    public function testDeleteWithNonExistentTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $db->delete('XXX', 1);
    }

    public function testDeleteWithInvalidColumnNameFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $db = $this->db();
        $db->delete('TestTable1', ['foo#$bar' => 1]);
    }

    public function testFetchValue1()
    {
        $db = $this->db();
        $sql =
            'SELECT name {s}
             FROM TestTable1
             WHERE RecordId = %s';
        $result = $db->fetchValue($sql, 1);
        Assert::equal($result, null);
    }

    public function testFetchValue2()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $sql =
            'SELECT name {s}
             FROM TestTable1
             WHERE RecordId = %s';
        $result = $db->fetchValue($sql, 1);
        Assert::equal($result, 'Michael');
    }

    public function testFetchValue3()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $sql =
            'SELECT RecordID {i}
             FROM TestTable1
             WHERE name = %s';
        $result = $db->fetchValue($sql, 'Michael');
        Assert::equal($result, 1);
    }

    public function testFetchValue4()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $sql =
            'SELECT RecordID {f}
             FROM TestTable1
             WHERE name = %s';
        $result = $db->fetchValue($sql, 'Michael');
        Assert::equal($result, 1.0);
    }

    public function testFetchFirstRow()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i
             ORDER BY RecordId';
        $result = $db->fetchFirstRow($sql, 1);
        Assert::equal(
            $result,
            [0, 1.0, 'Michael', 'Orange']
        );
    }

    public function testFetchFirstRowWithFetchModeObject()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i
             ORDER BY RecordId';
        $result = $db->fetchFirstRow($sql, [1], Db::FETCHMODE_OBJECT);
        Assert::equal(
            $result,
            (object)[
                'CreationDate' => 0,
                'RecordId' => 1.0,
                'name' => 'Michael',
                'color' => 'Orange'
            ]
        );
    }

    public function testFetchFirstRowWithFetchModeAssoc()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i
             ORDER BY RecordId';
        $result = $db->fetchFirstRow($sql, [1], Db::FETCHMODE_ASSOC);
        Assert::equal(
            $result,
            [
                'CreationDate' => 0,
                'RecordId' => 1.0,
                'name' => 'Michael',
                'color' => 'Orange'
            ]
        );
    }

    public function testFetchFirstRowWithNonExistentTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $sql =
            'SELECT *
             FROM XXX
             WHERE RecordId = %i';
        $result = $db->fetchFirstRow($sql, 1);
    }

    public function testFetchFirstArray()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->fetchFirstArray($sql, 1);
        Assert::equal(
            $result,
            [
                'CreationDate' => 0,
                'RecordId' => 1.0,
                'name' => 'Michael',
                'color' => 'Orange'
            ]
        );
    }

    public function testFetchFirstArrayWithNonExistentTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $sql =
            'SELECT *
             FROM XXX
             WHERE RecordId = %i';
        $result = $db->fetchFirstArray($sql, 1);
    }

    public function testFetchFirstObject()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE RecordId = %i';
        $result = $db->fetchFirstObject($sql, 1);
        Assert::equal(
            $result,
            (object)[
                'CreationDate' => 0,
                'RecordId' => 1.0,
                'name' => 'Michael',
                'color' => 'Orange'
            ]
        );
    }

    public function testFetchFirstObjectWithNonExistentTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $sql =
            'SELECT *
             FROM XXX
             WHERE RecordId = %i';
        $result = $db->fetchFirstObject($sql, 1);
    }

    public function testFetchAll()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE color = %s';
        $result = $db->fetchAll($sql, ['Blue']);
        Assert::equal(
            $result,
            [
                [0, 1.0, 'Michael', 'Blue'],
                [0, 2.0, 'Robert', 'Blue']
            ]
        );
    }

    public function testFetchAllWithModeObject()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE color = %s';
        $result = $db->fetchAll($sql, ['Blue'], Db::FETCHMODE_OBJECT);
        Assert::equal(
            $result,
            [
                (object)[
                    'CreationDate' => 0,
                    'RecordId' => 1.0,
                    'name' => 'Michael',
                    'color' => 'Blue'
                ],
                (object)[
                    'CreationDate' => 0,
                    'RecordId' => 2.0,
                    'name' => 'Robert',
                    'color' => 'Blue'
                ]
            ]
        );
    }

    public function testFetchAllWithModeAssoc()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM TestTable1
             WHERE color = %s';
        $result = $db->fetchAll($sql, ['Blue'], Db::FETCHMODE_ASSOC);
        Assert::equal(
            $result,
            [
                [
                    'CreationDate' => 0,
                    'RecordId' => 1.0,
                    'name' => 'Michael',
                    'color' => 'Blue'
                ],
                [
                    'CreationDate' => 0,
                    'RecordId' => 2.0,
                    'name' => 'Robert',
                    'color' => 'Blue'
                ]
            ]
        );
    }

    public function testFetchAllWithNonExistentTableFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $sql =
            'SELECT *
             FROM XXX
             WHERE RecordId = %i';
        $result = $db->fetchAll($sql, 1);
    }

    public function testFetchAllEmptyResult()
    {
        $db = $this->db();
        $sql =
            'SELECT CreationDate {i}, RecordId {i}, name {s}, color {s}
             FROM TestTable1
             WHERE color = %s';
        $result = $db->fetchAll($sql, ['Blue'], Db::FETCHMODE_ASSOC);
        Assert::equal(
            $result,
            []
        );
    }

    public function testFetchRowEmptyResult()
    {
        $db = $this->db();
        $sql = "SELECT * FROM TestTable1 WHERE color = %s";
        $result = $db->query($sql, 'Blue');
        $row = $result->fetchRow();
        Assert::equal($row, null);
    }

    public function testFetchAllWithColumnsName()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $sql =
            'SELECT *
             FROM TestTable1
             WHERE color = %s';
        $result = $db->fetchAll($sql, ['Blue'], ['column' => 'name']);
        Assert::equal(
            $result,
            [
                'Michael',
                'Robert'
            ]
        );
    }

    public function testFetchAllWithColumnsPosition()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $sql =
            'SELECT *
             FROM TestTable1
             WHERE color = %s';
        $result = $db->fetchAll($sql, ['Blue'], ['column' => 2]);
        Assert::equal(
            $result,
            [
                'Michael',
                'Robert'
            ]
        );
    }

    public function testFetchRow()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql = "SELECT RecordId {i} FROM TestTable1 WHERE color = %s";
        $result = $db->query($sql, 'Orange');
        $row = $result->fetchRow();
        Assert::equal($row, [1]);
    }

    public function testFetchArray()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql = "SELECT RecordId {i} FROM TestTable1 WHERE color = %s";
        $result = $db->query($sql, 'Orange');
        $row = $result->fetchArray();
        Assert::equal($row, ['RecordId' => 1]);
    }

    public function testFetchObject()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Blue')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql = "SELECT RecordId {i} FROM TestTable1 WHERE color = %s";
        $result = $db->query($sql, 'Orange');
        $row = $result->fetchObject();
        Assert::equal($row, (object)['RecordId' => 1]);
    }

    public function testPrepareStatmentWithFetchRow()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s} FROM
             TestTable1
             WHERE color = %s
             ORDER BY RecordID';
        $stm = $db->prepare($sql);
        $result = $stm->execute(['Orange']);
        $row = $result->fetchRow();
        Assert::equal(
            $row,
            [0, 1.0, 'Michael', 'Orange']
        );
        $result = $stm->execute(['Green']);
        $row = $result->fetchRow();
        Assert::equal(
            $row,
            [0, 3.0, 'John', 'Green']
        );
    }

    public function testPrepareStatmentWithFetchArray()
    {
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'John', 'Green')");
        $sql =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s} FROM
             TestTable1
             WHERE color = %s
             ORDER BY RecordID';
        $stm = $db->prepare($sql);
        $result = $stm->execute(['Orange']);
        $results = [];
        while ($row = $result->fetchArray())
            $results[] = $row;
        Assert::equal(
            $results,
            [
                [
                    'CreationDate' => 0,
                    'RecordId' => 1.0,
                    'name' => 'Michael',
                    'color' => 'Orange'
                ],
                [
                    'CreationDate' => 0,
                    'RecordId' => 2.0,
                    'name' => 'Robert',
                    'color' => 'Orange'
                ]
            ]
        );
        $result = $stm->execute(['Green']);
        $result = $result->fetchArray();
        Assert::equal(
            $result,
            [
                'CreationDate' => 0,
                'RecordId' => 3.0,
                'name' => 'John',
                'color' => 'Green'
            ]
        );
    }

    public function testExecutePrepareStatmentAfterFreeingFailure()
    {
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $db = $this->db();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'Michael', 'Orange')");
        $db->query("INSERT INTO TestTable1 VALUES (0, 3, 'Robert', 'Green')");
        $sql = "SELECT * FROM TestTable1 WHERE color = %s";
        $stm = $db->prepare($sql);
        $result = $stm->execute(['Orange']);
        $stm = $db->prepare($sql);
        $stm->free();
        $result = $stm->execute(['Green']);
    }

    public function testLastInsertId()
    {
        $db = $this->db();
        $db->insert(
            'TestTable1',
            [
                'name' => 'John',
                'color' => 'Blue'
            ]
        );
        Assert::equal(
            $db->lastInsertId(),
            $db->fetchValue('SELECT MAX(RecordID {i}) FROM TestTable1')
        );
        $db->insert(
            'TestTable1',
            [
                'name' => 'Bob',
                'color' => 'Green'
            ]
        );
        Assert::equal(
            $db->lastInsertId(),
            $db->fetchValue('SELECT MAX(RecordID {i}) FROM TestTable1')
        );
        $db->insert(
            'TestTable1',
            [
                'name' => 'Wendy',
                'color' => 'Orange'
            ]
        );
        Assert::equal(
            $db->lastInsertId(),
            $db->fetchValue('SELECT MAX(RecordID {i}) FROM TestTable1')
        );
        $db->insert(
            'TestTable1',
            [
                'name' => 'Kevin',
                'color' => 'Brown'
            ]
        );
        Assert::equal(
            $db->lastInsertId(),
            $db->fetchValue('SELECT MAX(RecordID {i}) FROM TestTable1')
        );
    }

    public function testDisconnect()
    {
        $db = $this->db();
        $db->disconnect();
    }

    public function testQoute()
    {
        $db = $this->db();
        $i = 1;
        foreach (["FOO", "'FOO'", "''FOO''", "'''FOO'''", "'''''''"] as $v) {
            $literal = $db->quote($v);
            $db->query("INSERT INTO TestTable1 VALUES (0, $i, $literal, 'Blue')");
            Assert::equal(
                $db->fetchValue(
                    "SELECT name
                     FROM TestTable1
                     WHERE RecordID = $i"
                ),
                $v
            );
            ++$i;
        }
    }

    public function testQuoteIdentifier()
    {
        $db = $this->db();
        $select = $db->quoteIdentifier('Select');
        $insert = $db->quoteIdentifier('insert');
        $from = $db->quoteIdentifier('from');
        $set = $db->quoteIdentifier('set');
        $where = $db->quoteIdentifier('where');
        $values = ['a', 'b', 'c', 'd'];
        $sql =
            "INSERT INTO $select ($insert, $from, $set, $where)
             VALUES (%s, %s, %s, %s);";
        $db->query($sql, $values);
        $sql =
            "SELECT $insert, $from, $set, $where
             FROM $select
             WHERE $insert = %s AND
                   $from = %s AND
                   $set = %s AND
                   $where = %s";
        $row = $db->fetchFirstRow($sql, $values);
        Assert::equal($row, $values);
    }

    public function testFetchingConnectingParams()
    {
        $config = Config::current();
        $db = $this->db();
        Assert::equal(
            $db->params()->dbms(),
            $config->getRequiredProperty('db.dbms')
        );
        Assert::equal(
            $db->params()->host(),
            $config->getRequiredProperty('test.db.host')
        );
        Assert::equal(
            $db->params()->username(),
            $config->getRequiredProperty('test.db.username')
        );
        Assert::equal(
            $db->params()->password(),
            $config->getRequiredProperty('test.db.password')
        );
        Assert::equal(
            $db->params()->database(),
            $this->databaseName
        );
    }

    public function testRollback()
    {
        $db = $this->db();
        $db->beginTransaction();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Red')");
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 1);
        $db->rollback();
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 0);
    }

    public function testCommit()
    {
        $db = $this->db();
        $db->beginTransaction();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Red')");
        $db->commit();
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 1);
    }

    public function testCreateNestedTransactionInNonNestableTransactionFailure()
    {
        $this->setExpectedStatusCode('STATE_ERROR');
        $db = Db::nonNestableInstance();
        $db->beginTransaction();
        $db->beginTransaction();
    }

    public function testNestedTransaction1()
    {
        $db = $this->db();
        $db->beginTransaction();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Red')");

        // Nested transaction
        $db->beginTransaction();
        $db->query(
            "INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Red')"
        );

        // The commit of nested transaction must have no effect
        $db->commit();

        // Rollback parent transaction will rollback all the changes
        $db->rollback();
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 0);
    }

    public function testNestedTransaction2()
    {
        $db = $this->db();
        $db->beginTransaction();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Red')");

        // Nested transaction
        $db->beginTransaction();
        $db->query(
            "INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Red')"
        );

        // Rollback of nested transacation must have no effect
        $db->rollback();

        // Commit parent transaction will commit all the changes
        $db->commit();
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 2);
    }

    public function testTransactionWithDifferentConnection()
    {
        // Connection 1
        $db = $this->db();
        $db->beginTransaction();
        $db->query("INSERT INTO TestTable1 VALUES (0, 1, 'James', 'Red')");

        // Connection 2
        $db2 = $this->db();
        $db2->beginTransaction();
        $db2->query(
            "INSERT INTO TestTable1 VALUES (0, 2, 'Robert', 'Red')"
        );

        // The commit of connection 2 makes changes visible for connection 1
        $db2->commit();
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 2);

        // Rollback connection 2
        $db->rollback();
        $count = $db->fetchValue("SELECT COUNT(*) {i} FROM TestTable1");
        Assert::equal($count, 1);
    }

    public function testQueryProcessor1()
    {
        $qp = new QueryProcessor($this->db());
        $unescaped = "SELECT '% {} {r} [ ] %k %'";
        $escaped = "SELECT '%% {{}} {{r}} [[ ] %%k %%'";
        list($sql) = $qp->process($escaped);
        Assert::equal($sql, $unescaped);
    }

    public function testQueryProcessor2()
    {
        $qp = new QueryProcessor($this->db());
        $unescaped =
            'SELECT CreationDate {i}, RecordId {f}, name {s}, color {s}
             FROM [TestTable1]
             WHERE color = %s
             ORDER BY RecordID';
        $escaped =
            'SELECT CreationDate {{i}}, RecordId {{f}}, name {{s}}, color {{s}}
             FROM [[TestTable1]
             WHERE color = %%s
             ORDER BY RecordID';
        list($sql) = $qp->process($escaped);
        Assert::equal($sql, $unescaped);
    }

    public function testQueryProcessor3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $qp = new QueryProcessor($this->db());
        $qp->process('SELECT %i, %s, %');
    }

    public function testQueryProcessor4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $qp = new QueryProcessor($this->db());
        $qp->process('SELECT %i, %s, firstName {');
    }

    public function testQueryProcessor5()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $qp = new QueryProcessor($this->db());
        $qp->process('SELECT %i, %s, [');
    }

    protected function suiteInitialize()
    {
        $this->databaseName =
            '__test_' . Random::string(self::RANDOM_STRING_LENGTH);
        $config = Config::current();
        $this->params =
            new \CodeRage\Db\Params([
                    'dbms' => $config->getRequiredProperty('db.dbms'),
                    'host' => $config->getRequiredProperty('test.db.host'),
                    'username' => $config->getRequiredProperty('test.db.username'),
                    'password' => $config->getRequiredProperty('test.db.password'),
                    'database' => $this->databaseName
                ]);
        Operations::createDatabase(self::SCHEMA, $this->params);
    }

    protected function suiteCleanup()
    {
        Operations::dropDatabase($this->databaseName, $this->params);
    }

    protected function componentInitialize($component)
    {
        $db = $this->db();
        foreach (self::TABLES as $t)
            $db->query("DELETE from $t");
    }

    /**
     * Returns an instance of CodeRage\Db
     *
     * @return CodeRage\Db
     */
    private function db()
    {
        return new Db(['params' => $this->params]);
    }

    /**
     * The database name
     *
     * @var string
     */
    private $databaseName;

    /**
     * Connection parameter for test database
     *
     * @var CodeRage\Db\Params
     */
    private $params;
}
