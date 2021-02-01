<?php

/**
 * Defines the class CodeRage\WebService\Test\SearchSuite
 *
 * File:        CodeRage/WebService/Test/SearchSuite.php
 * Date:        Tue May 22 23:09:46 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Test;

use DateTime;
use CodeRage\Build\Config\Array_ as ArrayConfig;
use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Db\Operations;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Test\Assert;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\Random;
use CodeRage\WebService\Search;

/**
 * @ignore
 */

/**
 * Test suite for the CodeRage\WebService\Search
 */
final class SearchSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * The path to the XML file defining the test database
     *
     * @var string
     */
    const SCHEMA = __DIR__ . '/search.tbx';

    /**
     * The path to the JSON file containing data to populate the database
     *
     * @var string
     */
    const DATA = __DIR__ . '/employees.json';

    /**
     * The length of the random component of the database name
     *
     * @var int
     */
    const RANDOM_STRING_LENGTH = 30;

    /**
     * Sample CodeRage\WebService\Search constructor options
     *
     * @var array
     */
    const SEARCH_OPTIONS_BASIC =
        [
            'fields' =>
                [
                    'RecordID' => 'int',
                    'id' => 'int',
                    'firstName' => 'string',
                    'middleName' => 'string',
                    'lastName' => 'string',
                    'height' => 'int',
                    'dob' => 'date',
                    'deceased' => 'boolean',
                    'salary' => 'decimal'
                ],
             'query' =>
                 'SELECT RecordID, RecordID as id, firstName, middleName,
                         lastName, height, dob, deceased, salary
                  FROM Employees',
             'outputFields' =>
                [ 'id', 'firstName', 'middleName', 'lastName',
                  'height', 'dob','deceased', 'salary' ],
             'maxRows' => 1000
         ];

    /**
     * Sample CodeRage\WebService\Search constructor options
     *
     * @var array
     */
    const SEARCH_OPTIONS_PARAMS =
        [
            'fields' =>
                [
                    'id' => 'int',
                    'firstName' => 'string',
                    'middleName' => 'string',
                    'lastName' => 'string',
                    'height' => 'int',
                    'dob' => 'date',
                    'deceased' => 'boolean',
                    'salary' => 'decimal'
                ],
             'query' =>
                 'SELECT e.RecordID as id, e.firstName, e.middleName,
                         e.lastName, e.height, e.dob, e.deceased, e.salary
                         e.salary {s}
                  FROM Employees e
                  JOIN Employees e2
                    ON e.RecordID = e2.RecordID AND
                       e.RecordID < %i',
             'params' => [100],
             'maxRows' => 1000
         ];

    /**
     * Sample CodeRage\WebService\Search constructor options
     *
     * @var array
     */
    const SEARCH_OPTIONS_DOTS =
        [
            'fields' =>
                [
                    'employee.id' => 'int',
                    'employee.firstName' => 'string',
                    'employee.middleName' => 'string',
                    'employee.lastName' => 'string',
                    'employee.height' => 'int',
                    'employee.dob' => 'date',
                    'employee.deceased' => 'boolean',
                    'employee.salary' => 'decimal'
                ],
             'query' =>
                 'SELECT RecordID AS [employee.id],
                         firstName AS [employee.firstName],
                         middleName AS [employee.middleName],
                         lastName AS [employee.lastName],
                         height AS [employee.height],
                         dob AS [employee.dob],
                         deceased AS [employee.deceased],
                         salary AS [employee.salary]
                  FROM Employees',
             'params' => [100],
             'maxRows' => 1000
         ];

    /**
     * Constructs an instance of CodeRage\Test\Test\OperationSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "Operation Suite",
            "Tests for the class CodeRage\Test\Operations\Operation"
        );
    }

    /**
     * Tests constructing a search with missing fields
     */
    public function testMissingFields()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        unset($params['fields']);
        new Search($params);
    }

    /**
     * Tests constructing a search with list of fields, instead of an
     * associative
     */
    public function testInvalidFields1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields'] = array_keys($params['fields']);
        new Search($params);
    }

    /**
     * Tests constructing a search with a field whose data type is an int
     * instead of a type name
     */
    public function testInvalidFields2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['dob'] = -1001;
        new Search($params);
    }

    /**
     * Tests constructing a search with a field with malformed type name
     */
    public function testInvalidFields3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['dob'] = 'string!!!';
        new Search($params);
    }

    /**
     * Tests constructing a search with a field with unrecognized data type
     */
    public function testInvalidFields4()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['dob'] = 'aardvark';
        new Search($params);
    }

    /**
     * Tests constructing a search with missing query
     */
    public function testMissingQuery()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        unset($params['query']);
        new Search($params);
    }


    /**
     * Tests constructing a search with a query that is an int instead of a
     * string
     */
    public function testInvalidQuery()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['query'] = -1001;
        new Search($params);
    }

    /**
     * Tests constructing a search with an int instead of a list of query
     * parameters
     */
    public function testInvalidQueryParams1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['queryParams'] = -1001;
        new Search($params);
    }

    /**
     * Tests constructing a search with an associative array of query
     * parameters instead of a list
     */
    public function testInvalidQueryParams2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['queryParams'] = ['params' => 97.9];
        new Search($params);
    }

    /**
     * Tests constructing a search with a list of query parameters containing
     * a non-scalar
     */
    public function testInvalidQueryParams3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['queryParams'] = [new DateTime];
        new Search($params);
    }

    /**
     * Tests constructing a search with a transform consisting of a DateTime
     * object instead of a callable
     */
    public function testInvalidTransform()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['transform'] = new DateTime;
        new Search($params);
    }

    /**
     * Tests constructing a search with missing maxRows
     */
    public function testMissingMaxRows()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        unset($params['maxRows']);
        new Search($params);
    }

    /**
     * Tests constructing a search with maxRows set to a float
     */
    public function testInvalidMaxRows1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['maxRows'] = 0.000341;
        new Search($params);
    }

    /**
     * Tests constructing a search with maxRows set to 0
     */
    public function testInvalidMaxRows2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['maxRows'] = 0;
        new Search($params);
    }

    /**
     * Tests constructing a search with maxRows set to a negative value
     */
    public function testInvalidMaxRows3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['maxRows'] = -1001;
        new Search($params);
    }

    /**
     * Tests constructing a search with an int instead of a database connection
     */
    public function testInvalidDb()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['db'] = -1001;
        new Search($params);
    }

    /**
     * Tests executing a search with a lower bound but no upper bound
     */
    public function testFromWithoutTo()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'from' => 100
        ]);
    }

    /**
     * Tests executing a search with an upper bound but no lower bound
     */
    public function testToWithoutFrom()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'to' => 100
        ]);
    }

    /**
     * Tests executing a search with lower bound 0
     */
    public function testInvalidFrom1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'from' => 0,
            'to' => 100
        ]);
    }

    /**
     * Tests executing a search with negative lower bound
     */
    public function testInvalidFrom2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'from' => -1001,
            'to' => 100
        ]);
    }

    /**
     * Tests executing a search with upper bound less than lower bound
     */
    public function testInvalidRange()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'from' => 100,
            'to' => 99
        ]);
    }

    /**
     * Tests executing a search with a collection of filters that is an
     * associative array instead of a list
     */
    public function testInvalidFilters1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName' => 'firstName,like,S*']
        ]);
    }

    /**
     * Tests executing a search with a collection of filters that contains an
     * int
     */
    public function testInvalidFilters2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => [-1001]
        ]);
    }

    /**
     * Tests executing a search with a collection of filters that is an int
     * instead of an array
     */
    public function testInvalidFilters3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => -1001
        ]);
    }

    /**
     * Tests executing a search with a syntactically malformed filter
     */
    public function testInvalidFilters4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,,S*']
        ]);
    }

    /**
     * Tests executing a search with a syntactically malformed filter
     */
    public function testInvalidFilters5()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['"firstName",like,S*']
        ]);
    }

    /**
     * Tests executing a search with a syntactically malformed filter
     */
    public function testInvalidFilters6()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['height,>,80']
        ]);
    }

    /**
     * Tests executing a search with a filter containing a malformed field name
     */
    public function testInvalidFilters7()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['.employee.firstName,like,S*']
        ]);
    }

    /**
     * Tests executing a search with a filter containing a malformed field name
     */
    public function testInvalidFilters8()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['1stname,like,S*']
        ]);
    }

    /**
     * Tests executing a search with a filter referencing an unsupported field
     */
    public function testInvalidFilters9()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['surname,like,S*']
        ]);
    }

    /**
     * Tests executing a search with a filter with an unsupported operation
     */
    public function testInvalidFilters10()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,matches,S*']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "exists" with
     * a value
     */
    public function testInvalidFilters11()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,exists,John']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "exists" with
     * an empty value
     */
    public function testInvalidFilters12()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,exists,']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notexists"
     * with a value
     */
    public function testInvalidFilters13()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,notexists,John']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notexists"
     * with an empty value
     */
    public function testInvalidFilters14()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,notexists,']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "eq"
     * without a value
     */
    public function testInvalidFilters15()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,eq']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "ne"
     * without a value
     */
    public function testInvalidFilters16()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,ne']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "lt"
     * without a value
     */
    public function testInvalidFilters17()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,lt']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "le"
     * without a value
     */
    public function testInvalidFilters18()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,le']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "gt"
     * without a value
     */
    public function testInvalidFilters19()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,gt']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "ge"
     * without a value
     */
    public function testInvalidFilters20()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,ge']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "like"
     * without a value
     */
    public function testInvalidFilters21()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,like']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notlike"
     * without a value
     */
    public function testInvalidFilters22()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,notlike']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "match"
     * without a value
     */
    public function testInvalidFilters23()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,match']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notmatch"
     * without a value
     */
    public function testInvalidFilters24()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['firstName,notmatch']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "lt"
     * with a boolean field
     */
    public function testInvalidFilters25()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,lt,1']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "le"
     * with a boolean field
     */
    public function testInvalidFilters26()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,le,1']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "gt"
     * with a boolean field
     */
    public function testInvalidFilters27()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,gt,1']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "ge"
     * with a boolean field
     */
    public function testInvalidFilters28()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,ge,1']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "like"
     * with a boolean field
     */
    public function testInvalidFilters29()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,like,tr*']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notlike"
     * with a boolean field
     */
    public function testInvalidFilters30()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,notlike,tr*']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "match"
     * with a boolean field
     */
    public function testInvalidFilters31()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,match,t']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notmatch"
     * with a boolean field
     */
    public function testInvalidFilters32()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        (new Search($params))->execute([
            'filters' => ['deceased,notmatch,t']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "eq" with a
     * floating-point field
     */
    public function testInvalidFilters33()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,eq,20.000']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "ne" with a
     * floating-point field
     */
    public function testInvalidFilters34()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,ne,20.000']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "like"
     * with a floating-point field
     */
    public function testInvalidFilters35()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,like,2????.00']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notlike"
     * with a floating-point field
     */
    public function testInvalidFilters36()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,notlike,2????.00']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "match"
     * with a floating-point field
     */
    public function testInvalidFilters37()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,match,^2....$']
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "notmatch"
     * with a floating-point field
     */
    public function testInvalidFilters38()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,notmatch,^2....$']
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid boolean value
     */
    public function testInvalidFilters39()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['deceased,eq,TRUE']
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid integer value
     */
    public function testInvalidFilters40()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['height,eq,tall']
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid floating-point
     * value
     */
    public function testInvalidFilters41()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,gt,20,000.00']
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid decimal value
     */
    public function testInvalidFilters42()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['salary,gt,20,000.00']
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid date
     */
    public function testInvalidFilters43()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['dob,ge,2/28/1961']
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid date
     */
    public function testInvalidFilters44()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ['dob,ge,Jun 3, 1981']
        ]);
    }

    /**
     * Tests executing a search with a filter using a string with illegal
     * characters
     */
    public function testInvalidFilters45()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $jsonLiteral =  // PHP doesn't recognize Unicode escapes
            '"\u0160\u00ed\u006c\u0065\u006e\u011b\u0020\u017e\u006c\u0075' .
            '\u0165\u006f\u0075\u010d\u006b\u00fd\u0020\u0056\u0061\u0161' .
            '\u0065\u006b\u0020\u00fa\u0070\u011b\u006c\u0020\u006f\u006c' .
            '\u006f\u006c"';
        $name = json_decode($jsonLiteral);
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ["lastName,eq,$name"]
        ]);
    }

    /**
     * Tests executing a search with a filter using a wildcard expression
     * with an invalid escape character
     */
    public function testInvalidFilters46()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ["lastName,like,Smi\\th"]
        ]);
    }

    /**
     * Tests executing a search with a filter using an invalid regular
     * expression
     */
    public function testInvalidFilters47()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'filters' => ["salary,match,2[0-9]{6}.00"]
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "lt"
     * with field of type resourceid
     */
    public function testInvalidFilters48()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['id'] = 'resourceid[employee,emp]';
        (new Search($params))->execute([
            'filters' => ["id,lt,100"]
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "match"
     * with field of type resourceid
     */
    public function testInvalidFilters49()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['id'] = 'resourceid[employee,emp]';
        (new Search($params))->execute([
            'filters' => ["id,match,emp-.*"]
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "lt"
     * with field of type enum
     */
    public function testInvalidFilters50()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        (new Search($params))->execute([
            'filters' => ["deceased,lt,TRUE"]
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "lt"
     * with field of type enum
     */
    public function testInvalidFilters51()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        (new Search($params))->execute([
            'filters' => ["deceased,lt,TRUE"]
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "match"
     * with field of type enum
     */
    public function testInvalidFilters52()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        (new Search($params))->execute([
            'filters' => ["deceased,match,T.*"]
        ]);
    }

    /**
     * Tests executing a search with a filter using the operation "in" with a
     * floating-point field
     */
    public function testInvalidFilters53()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['salary'] = 'float';
        (new Search($params))->execute([
            'filters' => ['salary,in,20.00,25.00']
        ]);
    }


    /**
     * Tests executing a search with a sort criteria that is an int instead of
     * a string
     */
    public function testInvalidSort1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'sort' => -1001
        ]);
    }

    /**
     * Tests executing a search with a sort criteria with a malformed sort
     * specifier
     */
    public function testInvalidSort2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'sort' => 'firstName,lastName,--height'
        ]);
    }

    /**
     * Tests executing a search with a sort criteria with a sort specifier
     * containing an unsupported field
     */
    public function testInvalidSort3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Search(self::SEARCH_OPTIONS_BASIC))->execute([
            'sort' => '-surname'
        ]);
    }

    /**
     * Tests executing a search with a sort criteria with a sort specifier
     * containing an field of unsortable type
     */
    public function testInvalidSort4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['id'] = 'resourceid[dog]';
        (new Search($params))->execute([
            'sort' => 'id'
        ]);
    }

    /**
     * Tests a serach expected to return the entire employees array, in order
     */
    public function testNoRange()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' => ['sort' => 'id'],
            'indices' => range(0, 285),
            'total' => 286
        ]);
    }

    /**
     * Tests a serach in which the results are truncated because the request
     * range exceeds maxRows
     */
    public function testRange1()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['maxRows'] = 10;
        $this->checkSearch([
            'construct' => $params,
            'execute' => ['sort' => 'id'],
            'indices' => range(0, 9),
            'total' => 286
        ]);
    }

    /**
     * Tests a serach in which the results are truncated because the requested
     * range exceeds maxRows
     */
    public function testRange2()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['maxRows'] = 10;
        $this->checkSearch([
            'construct' => $params,
            'execute' => ['sort' => 'id', 'from' => 1, 'to' => 1000],
            'indices' => range(0, 9),
            'total' => 286
        ]);
    }

    /**
     * Tests a serach in which the results are truncated because the requested
     * range exceeds maxRows
     */
    public function testRange3()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['maxRows'] = 10;
        $this->checkSearch([
            'construct' => $params,
            'execute' => ['sort' => 'id', 'from' => 100, 'to' => 1000],
            'indices' => range(99, 108),
            'total' => 286
        ]);
    }

    /**
     * Tests a serach in which the results are truncated because the requested
     * range overlaps with the end of the list of items
     */
    public function testRange4()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' => ['sort' => 'id', 'from' => 200, 'to' => 1000],
            'indices' => range(199, 285),
            'total' => 286
        ]);
    }

    /**
     * Tests filtering a boolean property using "eq"
     */
    public function testFilterBooleanEq1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' => ['filters' => ['deceased,eq,1'], 'sort' => 'id'],
            'indices' =>
                [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 13, 14, 15, 16, 18, 19, 20,
                  21, 23, 24, 25, 29, 33, 35, 37, 38, 40, 43, 44, 48, 51, 53,
                  54, 57, 63, 68, 69, 70, 76, 83, 88, 102, 107, 109, 110, 111,
                  120, 121, 122, 125, 126, 130, 136, 137, 142, 148, 152, 233 ],
            'total' => 59
        ]);
    }

    /**
     * Tests filtering a boolean property using "eq"
     */
    public function testFilterBooleanEq2()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['deceased,eq,0'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 20
                ],
            'indices' =>
                [ 12, 17, 22, 26, 27, 28, 30, 31, 32, 36, 39, 41, 42, 45, 46,
                  47, 49, 50, 52, 55 ],
            'total' => 221
        ]);
    }

    /**
     * Tests filtering a boolean property using "ne"
     */
    public function testFilterBooleanNe1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['deceased,ne,1'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 20
                ],
            'indices' =>
                [ 12, 17, 22, 26, 27, 28, 30, 31, 32, 36, 39, 41, 42, 45, 46,
                  47, 49, 50, 52, 55 ],
            'total' => 221
        ]);
    }

    /**
     * Tests filtering a boolean property using "ne"
     */
    public function testFilterBooleanNe2()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' => ['filters' => ['deceased,ne,0'], 'sort' => 'id'],
            'indices' =>
                [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 13, 14, 15, 16, 18, 19, 20,
                  21, 23, 24, 25, 29, 33, 35, 37, 38, 40, 43, 44, 48, 51, 53,
                  54, 57, 63, 68, 69, 70, 76, 83, 88, 102, 107, 109, 110, 111,
                  120, 121, 122, 125, 126, 130, 136, 137, 142, 148, 152, 233 ],
            'total' => 59
        ]);
    }

    /**
     * Tests filtering a boolean property using "exists"
     */
    public function testFilterBooleanExists()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['deceased,exists'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 20
                ],
            'indices' =>
                [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 14, 15, 16, 17, 18,
                  19, 20 ],
            'total' => 280
        ]);
    }

    /**
     * Tests filtering a boolean property using "notexists"
     */
    public function testFilterBooleanNotExists()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['deceased,notexists'],
                    'sort' => 'id'
                ],
            'indices' => [11, 34, 77, 128, 199, 270],
            'total' => 6
        ]);
    }

    /**
     * Tests filtering a int property using "eq"
     */
    public function testFilterIntEq()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' => ['filters' => ['height,eq,169'], 'sort' => 'id'],
            'indices' => [69, 71, 82, 107, 127, 160, 192],
            'total' => 7
        ]);
    }

    /**
     * Tests filtering a int property using "ne"
     */
    public function testFilterIntNe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,ne,163'],
                    'sort' => 'id',
                    'from' => 20,
                    'to' => 119
                ],
            'indices' =>
                [ 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33,
                  34, 35, 36, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 50,
                  51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65,
                  66, 67, 68, 69, 70, 71, 73, 74, 75, 76, 77, 78, 79, 80, 81,
                  82, 83, 84, 85, 86, 87, 88, 90, 91, 92, 93, 94, 95, 96, 97,
                  98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110,
                  111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122 ],
            'total' => 275
        ]);
    }

    /**
     * Tests filtering a int property using "lt"
     */
    public function testFilterIntLt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,lt,140'],
                    'sort' => 'id'
                ],
            'indices' => [75, 118, 168, 271],
            'total' => 4
        ]);
    }

    /**
     * Tests filtering a int property using "le"
     */
    public function testFilterIntLe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,le,140'],
                    'sort' => 'id'
                ],
            'indices' => [9, 29, 75, 87, 118, 128, 148, 168, 271],
            'total' => 9
        ]);
    }

    /**
     * Tests filtering a int property using "gt"
     */
    public function testFilterIntGt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,gt,200'],
                    'sort' => 'id'
                ],
            'indices' => [21, 57, 123, 137, 200],
            'total' => 5
        ]);
    }

    /**
     * Tests filtering a int property using "ge"
     */
    public function testFilterIntGe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,ge,200'],
                    'sort' => 'id'
                ],
            'indices' => [21, 57, 119, 123, 137, 200],
            'total' => 6
        ]);
    }

    /**
     * Tests filtering a int property using "like"
     */
    public function testFilterIntLike1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,like,2*'],
                    'sort' => 'id'
                ],
            'indices' => [21, 57, 119, 123, 137, 200],
            'total' => 6
        ]);
    }

    /**
     * Tests filtering a int property using "like"
     */
    public function testFilterIntLike2()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,like,??0'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 7, 9, 15, 29, 35, 40, 45, 47, 60, 65, 67, 86, 87, 92, 98, 101,
                  109, 119, 128, 148, 157, 158, 168, 180, 185, 201, 211, 221,
                  227, 242, 259, 268, 281 ],
            'total' => 33
        ]);
    }

    /**
     * Tests filtering a int property using "notlike"
     */
    public function testFilterIntNotLike()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,notlike,1*'],
                    'sort' => 'id'
                ],
            'indices' => [21, 57, 119, 123, 137, 200],
            'total' => 6
        ]);
    }

    /**
     * Tests filtering a int property using "match"
     */
    public function testFilterIntMatch1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,match,^19.|20.$'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 14, 21, 32, 33, 57, 76, 88, 98, 119, 123, 137, 138, 162, 166,
                  169, 180, 200, 221, 234, 250, 262, 265 ],
            'total' => 22
        ]);
    }

    /**
     * Tests filtering a int property using "notmatch"
     */
    public function testFilterIntNotMatch()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,notmatch,.5.|.6.|.7.|.8.|.9.'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 1, 9, 16, 17, 21, 29, 31, 57, 75, 80, 87, 100, 111, 118, 119,
                  123, 125, 128, 134, 137, 146, 148, 163, 168, 186, 200, 215,
                  219, 238, 271, 277, 283 ],
            'total' => 32
        ]);
    }

    /**
     * Tests filtering a decimal property using "eq"
     */
    public function testFilterDecimalEq()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,eq,14975.60'],
                    'sort' => 'id'
                ],
            'indices' => [227],
            'total' => 1
        ]);
    }

    /**
     * Tests filtering a decimal property using "ne"
     */
    public function testFilterDecimalNe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,ne,14975.60'],
                    'sort' => 'id',
                    'from' => 227,
                    'to' => 236
                ],
            'indices' => [226, 228, 229, 230, 231, 232, 233, 234, 235, 236],
            'total' => 285
        ]);
    }

    /**
     * Tests filtering a decimal property using "lt"
     */
    public function testFilterDecimalLt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,lt,14975.60'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 0, 9, 14, 55, 56, 67, 85, 114, 121, 126, 143, 155, 162, 165,
                  169, 174, 178, 187, 194, 212, 215, 245, 247, 249, 253, 261,
                  272, 273, 282 ],
            'total' => 29
        ]);
    }

    /**
     * Tests filtering a decimal property using "le"
     */
    public function testFilterDecimalLe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,le,14975.60'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 0, 9, 14, 55, 56, 67, 85, 114, 121, 126, 143, 155, 162, 165,
                  169, 174, 178, 187, 194, 212, 215, 227, 245, 247, 249, 253,
                  261, 272, 273, 282 ],
            'total' => 30
        ]);
    }

    /**
     * Tests filtering a decimal property using "gt"
     */
    public function testFilterDecimalGt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,gt,90334.34'],
                    'sort' => 'id'
                ],
            'indices' => [18, 21, 52, 66, 142, 170, 224, 250, 283],
            'total' => 9
        ]);
    }

    /**
     * Tests filtering a decimal property using "ge"
     */
    public function testFilterDecimalGe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,ge,90334.34'],
                    'sort' => 'id'
                ],
            'indices' => [18, 21, 52, 66, 142, 170, 224, 250, 255, 283],
            'total' => 10
        ]);
    }

    /**
     * Tests filtering a decimal property using "like"
     */
    public function testFilterDecimalLike1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,like,*.00'],
                    'sort' => 'id'
                ],
            'indices' => [178],
            'total' => 1
        ]);
    }

    /**
     * Tests filtering a decimal property using "notlike"
     */
    public function testFilterDecimalNotLike()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,notlike,?????.??'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 0, 9, 14, 55, 85, 121, 126, 155, 169, 174, 178, 187, 212, 215,
                  247, 249, 253, 261, 272, 273, 282 ],
            'total' => 21
        ]);
    }

    /**
     * Tests filtering a decimal property using "match"
     */
    public function testFilterDecimalMatch1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,match,^(8....\...|9....\...)'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 17, 18, 21, 32, 52, 57, 61, 62, 66, 102, 133, 135, 138, 139,
                  142, 144, 149, 152, 159, 161, 170, 181, 197, 199, 208, 224,
                  232, 233, 235, 250, 255, 256, 268, 283 ],
            'total' => 34
        ]);
    }

    /**
     * Tests filtering a decimal property using "notmatch"
     */
    public function testFilterDecimalNotMatch()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['salary,notmatch,\.(0|1|2|3|4|5|6)'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 7, 8, 10, 13, 16, 27, 30, 42, 44, 52, 55, 56, 59, 64, 68, 72,
                  74, 75, 76, 77, 80, 83, 84, 85, 86, 88, 89, 94, 102, 103, 107,
                  113, 115, 122, 127, 128, 136, 140, 146, 158, 159, 160, 166,
                  167, 169, 172, 174, 175, 176, 177, 179, 182, 183, 184, 189,
                  193, 206, 207, 212, 215, 222, 224, 226, 229, 230, 232, 235,
                  238, 239, 242, 247, 252, 254, 260, 263, 266, 271, 273, 277,
                  280, 281, 283, 284, 285 ],
            'total' => 84
        ]);
    }

    /**
     * Tests filtering a date property using "lt"
     */
    public function testFilterDateLt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,lt,1951-01-01'],
                    'sort' => 'id'
                ],
            'indices' =>
                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17],
            'total' => 18
        ]);
    }

    /**
     * Tests filtering a date property using "le"
     */
    public function testFilterDateLe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,le,1952-12-31'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16,
                  17, 18, 19 ],
            'total' => 20
        ]);
    }

    /**
     * Tests filtering a date property using "gt"
     */
    public function testFilterDateGt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,gt,1996-01-01'],
                    'sort' => 'id'
                ],
            'indices' => [282, 283, 284, 285],
            'total' => 4
        ]);
    }

    /**
     * Tests filtering a date property using "ge"
     */
    public function testFilterDateGe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,ge,1994-01-01'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284,
                  285 ],
            'total' => 13
        ]);
    }

    /**
     * Tests filtering a date property using "like"
     */
    public function testFilterDateLike1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,like,*-01-01'],
                    'sort' => 'id'
                ],
            'indices' => [209],
            'total' => 1
        ]);
    }

    /**
     * Tests filtering a date property using "notlike"
     */
    public function testFilterDateNotLike()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,notlike,191*'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 10
                ],
            'indices' => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            'total' => 283
        ]);
    }

    /**
     * Tests filtering a date property using "match"
     */
    public function testFilterDateMatch1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,match,^19(2|3|4)'],
                    'sort' => 'id'
                ],
            'indices' => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
            'total' => 11
        ]);
    }

    /**
     * Tests filtering a date property using "notmatch"
     */
    public function testFilterDateNotMatch()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['dob,notmatch,^19(6|7|8|9)'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17,
                  18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32,
                  33, 34, 35, 36, 37, 38, 39, 40 ],
            'total' => 41
        ]);
    }

    /**
     * Tests filtering a string property using "le"
     */
    public function testFilterStringLe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,le,An'],
                    'sort' => 'id'
                ],
            'indices' => [2, 43, 104, 134, 142, 166, 177, 227, 276],
            'total' => 9
        ]);
    }

    /**
     * Tests filtering a string property using "gt"
     */
    public function testFilterStringGt()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,gt,Wong'],
                    'sort' => 'id'
                ],
            'indices' => [139, 172, 181, 238, 253],
            'total' => 5
        ]);
    }

    /**
     * Tests filtering a string property using "ge"
     */
    public function testFilterStringGe()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,ge,Wo'],
                    'sort' => 'id'
                ],
            'indices' => [96, 139, 172, 181, 238, 253],
            'total' => 6
        ]);
    }

    /**
     * Tests filtering a string property using "like"
     */
    public function testFilterStringLike1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,s*n'],
                    'sort' => 'id'
                ],
            'indices' => [14, 26, 41, 84, 190, 283],
            'total' => 6
        ]);
    }

    /**
     * Tests filtering a string property using "like"
     */
    public function testFilterStringLike2()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,s????'],
                    'sort' => 'id'
                ],
            'indices' => [31, 32, 108, 156, 196, 198, 202, 258, 275, 277],
            'total' => 10
        ]);
    }

    /**
     * Tests filtering a string property using "notlike"
     */
    public function testFilterStringNotLike()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,notlike,????*'],
                    'sort' => 'id'
                ],
            'indices' => [50, 178, 217, 226],
            'total' => 4
        ]);
    }

    /**
     * Tests filtering a string property using "match"
     */
    public function testFilterStringMatch1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,match,^k|k$'],
                    'sort' => 'id'
                ],
            'indices' =>
                [ 1, 15, 66, 68, 72, 112, 130, 136, 145, 153, 170, 174, 180,
                  185, 191, 205, 247, 252, 258 ],
            'total' => 19
        ]);
    }

    /**
     * Tests filtering a string property using "match"
     */
    public function testFilterStringMatch2()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,match,(man|sen)$'],
                    'sort' => 'id'
                ],
            'indices' => [29, 83, 162, 176, 188, 190, 192, 195, 200, 214, 259],
            'total' => 11
        ]);
    }

    /**
     * Tests filtering a string property using "notmatch"
     */
    public function testFilterStringNotMatch()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,notmatch,a|b|c|d|e|f|g|h'],
                    'sort' => 'id'
                ],
            'indices' => [7, 28, 81, 122, 160, 202, 221, 222, 229, 246, 249],
            'total' => 11
        ]);
    }

    /**
     * Tests filtering a string property using "exists"
     */
    public function testFilterStringExists()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['middleName,exists'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 20
                ],
            'indices' =>
                [ 0, 2, 3, 5, 6, 8, 10, 13, 14, 15, 16, 17, 18, 22, 31, 32, 34,
                  35, 45, 46 ],
            'total' => 128
        ]);
    }

    /**
     * Tests filtering a string property using "notexists"
     */
    public function testFilterStringNotExists()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['middleName,notexists'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 20
                ],
            'indices' =>
                [ 1, 4, 7, 9, 11, 12, 19, 20, 21, 23, 24, 25, 26, 27, 28, 29,
                  30, 33, 36, 37 ],
            'total' => 158
        ]);
    }

    /**
     * Tests filtering an resourceid property using "eq"
     */
    public function testFilterResourceIdEq()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['id'] = 'resourceid[employee]';
        $params['outputFields'] = ['id', 'lastName'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['id,eq,employee-34abc05e'],
                    'sort' => 'RecordID'
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 'employee-34abc05e', 'lastName' => 'Cano']
                        ],
                    'offset' => 1,
                    'total' => 1
                ]
        ]);
    }

    /**
     * Tests filtering an resourceid property using "in"
     */
    public function testFilterResourceIdIn()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['id'] = 'resourceid[employee,emp]';
        $params['outputFields'] = ['id', 'lastName'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['id,in,emp-a112e696,emp-2fd8262f,emp-2878e533'],
                    'sort' => 'RecordID'
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 'emp-2fd8262f', 'lastName' => 'Straatmeyer'],
                            ['id' => 'emp-a112e696', 'lastName' => 'Cottle'],
                            ['id' => 'emp-2878e533', 'lastName' => 'Van Ry']
                        ],
                    'offset' => 1,
                    'total' => 3
                ]
        ]);
    }

    /**
     * Tests filtering an resourceid property using "notin"
     */
    public function testFilterResourceIdNotIn()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['id'] = 'resourceid[employee,emp]';
        $params['outputFields'] = ['id', 'lastName'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['id,notin,emp-524f5227,emp-0a482a51,emp-67af3a55'],
                    'sort' => 'RecordID',
                    'from' => 8,
                    'to' => 11
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 'emp-2f7cef02', 'lastName' => 'Morrill'],
                            ['id' => 'emp-8324e880', 'lastName' => 'Bell'],
                            ['id' => 'emp-a112e7c6', 'lastName' => 'Thomas'],
                            ['id' => 'emp-adb75374', 'lastName' => 'Martinez']
                        ],
                    'offset' => 8,
                    'total' => 283
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "eq"
     */
    public function testFilterEnumEq1()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,eq,YES'],
                    'sort' => 'id',
                    'from' => 10,
                    'to' => 12
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 10],
                            ['id' => 11],
                            ['id' => 14]
                        ],
                    'offset' => 10,
                    'total' => 59
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "eq"
     */
    public function testFilterEnumEq2()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,eq,NO'],
                    'sort' => 'id',
                    'from' => 1,
                    'to' => 3
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 13],
                            ['id' => 18],
                            ['id' => 23]
                        ],
                    'offset' => 1,
                    'total' => 221
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "ne"
     */
    public function testFilterEnumNe1()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,ne,YES'],
                    'sort' => 'id',
                    'from' => 10,
                    'to' => 12
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 37],
                            ['id' => 40],
                            ['id' => 42]
                        ],
                    'offset' => 10,
                    'total' => 221
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "ne"
     */
    public function testFilterEnumNe2()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,ne,NO'],
                    'sort' => 'id',
                    'from' => 10,
                    'to' => 12
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 10],
                            ['id' => 11],
                            ['id' => 14]
                        ],
                    'offset' => 10,
                    'total' => 59
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "exists"
     */
    public function testFilterEnumExists()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,exists'],
                    'sort' => 'id',
                    'from' => 10,
                    'to' => 12
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 10],
                            ['id' => 11],
                            ['id' => 13]
                        ],
                    'offset' => 10,
                    'total' => 280
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "notexists"
     */
    public function testFilterEnumNotExists()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,notexists'],
                    'sort' => 'id'
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 12],
                            ['id' => 35],
                            ['id' => 78],
                            ['id' => 129],
                            ['id' => 200],
                            ['id' => 271]
                        ],
                    'offset' => 1,
                    'total' => 6
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "in"
     */
    public function testFilterEnumIn1()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,in,YES'],
                    'sort' => 'id',
                    'from' => 10,
                    'to' => 12
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 10],
                            ['id' => 11],
                            ['id' => 14]
                        ],
                    'offset' => 10,
                    'total' => 59
                ]
        ]);
    }

    /**
     * Tests filtering an enum property using "in"
     */
    public function testFilterEnumIn2()
    {
        $params = self::SEARCH_OPTIONS_BASIC;
        $params['fields']['deceased'] = 'enum[yesno,YES:1,NO:0]';
        $params['outputFields'] = ['id'];
        $this->checkSearch([
            'construct' => $params,
            'execute' =>
                [
                    'filters' => ['deceased,in,NO,YES'],
                    'sort' => 'id',
                    'from' => 10,
                    'to' => 12
                ],
            'result' =>
                [
                    'items' =>
                        [
                            ['id' => 10],
                            ['id' => 11],
                            ['id' => 13]
                        ],
                    'offset' => 10,
                    'total' => 280
                ]
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "lastName,firstName" (firstName is necessary to make the order
     * deterministic)
     */
    public function testSort1()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => 'lastName,firstName'
                ],
            'indices' =>
                [ 127, 82, 126, 285, 278, 62, 209, 19, 17, 13, 206, 163, 261,
                  86, 74, 124, 97, 58, 30, 139 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "+lastName,firstName" (firstName is necessary to make the order
     * deterministic)
     */
    public function testSort2()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => '+lastName,firstName'
                ],
            'indices' =>
                [ 127, 82, 126, 285, 278, 62, 209, 19, 17, 13, 206, 163, 261,
                  86, 74, 124, 97, 58, 30, 139 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "-lastName,firstName" (firstName is necessary to make the order
     * deterministic)
     */
    public function testSort3()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => '-lastName,firstName'
                ],
            'indices' =>
                [ 139, 30, 58, 97, 74, 124, 163, 261, 86, 206, 13, 17, 19, 209,
                  278, 62, 285, 126, 82, 127 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "height,lastName" (lastName is necessary to make the order
     * deterministic)
     */
    public function testSort4()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => 'height,lastName'
                ],
            'indices' =>
                [ 163, 17, 30, 209, 86, 285, 278, 124, 139, 62, 126, 97, 127,
                  82, 261, 206, 58, 74, 19, 13 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "-height,lastName" (lastName is necessary to make the order
     * deterministic)
     */
    public function testSort5()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => '-height,lastName'
                ],
            'indices' =>
                [ 13, 19, 74, 58, 206, 261, 127, 82, 97, 126, 62, 124, 139,
                  278, 285, 86, 209, 30, 17, 163 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "height,middleName" (involves some null values)
     */
    public function testSort6()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => 'height,middleName'
                ],
            'indices' =>
                [ 163, 17, 30, 209, 86, 285, 278, 124, 139, 62, 126, 97, 82,
                  127, 261, 206, 58, 74, 19, 13 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using
     * "-height,middleName" (involves some null values)
     */
    public function testSort7()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => '-height,middleName'
                ],
            'indices' =>
                [ 13, 19, 74, 58, 206, 261, 82, 127, 97, 126, 62, 124, 139,
                  278, 285, 86, 209, 30, 17, 163 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "dob"
     */
    public function testSort8()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => 'dob'
                ],
            'indices' =>
                [ 13, 17, 19, 30, 58, 62, 74, 82, 86, 97, 124, 126, 127, 139,
                  163, 206, 209, 261, 278, 285 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "-dob"
     */
    public function testSort9()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => '-dob'
                ],
            'indices' =>
                [ 285, 278, 261, 209, 206, 163, 139, 127, 126, 124, 97, 86, 82,
                  74, 62, 58, 30, 19, 17, 13 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "deceased"
     */
    public function testSort10()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['middleName,like,*z*'],
                    'sort' => 'deceased'
                ],
            'indices' => [180, 68],
            'total' => 2
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "-deceased"
     */
    public function testSort11()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['middleName,like,*z*'],
                    'sort' => '-deceased'
                ],
            'indices' => [68, 180],
            'total' => 2
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "salary"
     */
    public function testSort12()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => 'salary'
                ],
            'indices' =>
                [ 261, 126, 163, 285, 124, 30, 74, 82, 209, 86, 97, 13, 19, 127,
                  206, 58, 278, 17, 139, 62 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "-salary"
     */
    public function testSort13()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['lastName,like,*z*'],
                    'sort' => '-salary'
                ],
            'indices' =>
                [ 62, 139, 17, 278, 58, 206, 127, 19, 13, 97, 86, 209, 82, 74,
                  30, 124, 285, 163, 126, 261 ],
            'total' => 20
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "middleName"
     */
    public function testSort14()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,eq,143'],
                    'sort' => 'middleName'
                ],
            'indices' => [ 277, 80, 111, 134 ],
            'total' => 4
        ]);
    }

    /**
     * Tests a serach in which the results are sorted using "-middleName"
     */
    public function testSort15()
    {
        $this->checkSearch([
            'construct' => self::SEARCH_OPTIONS_BASIC,
            'execute' =>
                [
                    'filters' => ['height,eq,143'],
                    'sort' => '-middleName'
                ],
            'indices' => [ 134, 111, 80, 277 ],
            'total' => 4
        ]);
    }

    protected function suiteInitialize()
    {
        // Construct new project configuration
        $initial = Config::current();
        $properties = [];
        foreach (['host', 'username', 'password'] as $name) {
            $properties["db.$name"] =
                $initial->getProperty("test.db.$name");
        }
        $properties['db.database'] =
            '__test_' . Random::string(self::RANDOM_STRING_LENGTH);
        $new = new \CodeRage\Build\Config\Array_($properties, $initial);

        // Create database
        $this->params = \CodeRage\Db\Params::create($new);
        Operations::createDatabase(self::SCHEMA, $this->params);

        // Install configuration
        Config::setCurrent($new);
        $this->initialConfig = $initial;

        // Populate database
        File::checkFile(self::DATA, 0b0100);
        $json = file_get_contents(self::DATA);
        $employees = json_decode($json);
        if ($employees === null)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' => 'Failed parsing employee list'
                ]);
        foreach ($employees as $i => $e) {
            array_unshift($e, $i + 1); // Add position in data set to each row
            $employees[$i] = $e;
        }
        $db = new Db;
        foreach ($employees as $i => $e) {
            list( $id, $first, $middle, $last, $height, $dob, $deceased,
                  $salary ) = $e;
            $employees[$i] =
                [
                    'id' => $i + 1,
                    'firstName' => $first,
                    'middleName' => $middle,
                    'lastName' => $last,
                    'height' => (int) $height,
                    'dob' => $dob,
                    'deceased' => $deceased !== null ?
                        (boolean) $deceased :
                        null,
                    'salary' => $salary
                ];
        }
        $db = new Db;
        foreach ($employees as $e) {
            $e['RecordID'] = $e['id'];
            unset($e['id']);
            $db->insert('Employees', $e);
        }
        $this->employees = $employees;
    }

    protected function suiteCleanup()
    {
        try {
            Operations::dropDatabase($this->params->database(), $this->params);
        } finally {
            if ($this->initialConfig !== null)
                Config::setCurrent($this->initialConfig);
        }
    }

    /**
     * Constructs and executes a search and verifies the results
     *
     * @param array $options The options array; supports the following options
     *     construct - The options to pass to the CodeRage\WebService\Search
     *       constructor
     *     execute - The options to pass to CodeRage\WebService\Search::execute()
     *     result - The expected output of execute(), as an instance of stdClass
     *       (optional)
     *     indices - The list of indices of $employees corresponding to the
     *       expected output of execute() (optional)
     *     total - The total number of rows matching the searc criteria
     *       (optiona)
     *   Exactly one of "results" and "subsequence" must be supplied; the
     *   options "indices" and "total" must be supplied together
     */
    private function checkSearch(array $options)
    {
        Args::checkKey($options, 'construct', 'map', [
            'required' => true
        ]);
        Args::checkKey($options, 'execute', 'map', [
            'required' => true
        ]);
        Args::checkKey($options, 'result', 'array');
        Args::checkKey($options, 'indices', 'list[int]');
        if (isset($options['indices']) != isset($options['total']))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'indices' and 'total' must be specified" .
                        "together"
                ]);
        Args::uniqueKey($options, ['result', 'indices']);
        $search = new Search($options['construct']);
        $result = $search->execute($options['execute']);
        if (isset($options['result'])) {
            Assert::equal($result, $options['result']);
        } else {
            $indices = $options['indices'];
            $items = Array_::values($this->employees, $indices);
            $from = isset($options['execute']['from']) ?
                $options['execute']['from'] :
                1;
            $expected = !empty($indices) ?
                [
                    'items' => $items,
                    'offset' => $from,
                    'total' => $options['total']
                ] :
                (object) ['total' => 0];
            Assert::equal($result, $expected);
        }
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
     * Connection parameter for test database
     *
     * @var CodeRage\Db\Params
     */
    private $params;

    /**
     * The current configuration at the time of suite execution, to be
     * reinstalled as the current configuration when the suite terminates
     *
     * @var CodeRage\Config
     */
    private $initialConfig;

    /**
     * The data used to populate the database
     *
     * @var array
     */
    private $employees;
}
