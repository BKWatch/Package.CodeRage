<?php

/**
 * Defines the class CodeRage\Test\Test\OperationSuite
 *
 * File:        CodeRage/Test/Test/OperationSuite.php
 * Date:        Tue May 22 23:09:46 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Test;

use Exception;
use stdClass;
use CodeRage\File;
use CodeRage\Test\Assert;
use CodeRage\Test\Operations\Constraint\Scalar;
use CodeRage\Test\Operations\ExecutionPlan;
use CodeRage\Test\Operations\Operation;
use CodeRage\Test\Operations\Schedule;
use CodeRage\Test\Operations\Test\LabeledSchedule;
use CodeRage\Test\PathComponent;
use CodeRage\Test\PathExpr;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Test suite for the classes in CodeRage\Test\Operations
 */
class OperationSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * @var int
     */
    const LOAD_OPERATION_COUNT = 9;

    /**
     * @var int
     */
    const EXECUTE_OPERATION_COUNT = 5;

    /**
     * Path to the schema for the execution plans
     *
     * @var string
     */
    const EXECUTION_PLAN_SCHEMA = 'executionPlan.xsd';

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

    public function testPathParse()
    {
        $paths =
            [
                ['hello', 'hello', false, null],
                ['hello/goodbye', 'goodbye', false, null],
                ['hello[9]', 'hello', true, 9],
                ['hello[*]', 'hello', true, null],
                ['hello/goodbye[1]', 'goodbye', true, 1],
                ['hello/goodbye[*]', 'goodbye', true, null],
                ['hello/goodbye[3]/thanks', 'thanks', false, null],
                ['hello/goodbye[*]/thanks', 'thanks', false, null],
            ];
        foreach ($paths as $p) {
            list($path, $name, $isListItem, $index) = $p;
            $expr = PathExpr::parse($path);
            $last = $expr->last();
            Assert::equivalentData((string) $expr, $path);
            Assert::equivalentData($last->name(), $name);
            Assert::equivalentData($last->isListItem(), $isListItem);
            Assert::equivalentData($last->index(), $index);
        }
        $expr = PathExpr::parse('/');
        Assert::equivalentData((string) $expr, '/');
    }

    public function testPathAppendAbsolute()
    {
        $cases =
            [
                ['hello', true, 9, '/hello[9]'],
                ['thanks', false, null, '/hello[9]/thanks'],
                ['goodbye', true, 3, '/hello[9]/thanks/goodbye[3]']
            ];
        $expr = PathExpr::parse('/');
        foreach ($cases as $case) {
            list($name, $isListItem, $index, $path) = $case;
            $component =
                new PathComponent($name, $isListItem, $index);
            $expr = $expr->append($component);
            Assert::equivalentData((string) $expr, $path);
        }
    }

    public function testPathAppendRelative()
    {
        $cases =
            [
                ['hello', true, 9, 'dave/hello[9]'],
                ['thanks', false, null, 'dave/hello[9]/thanks'],
                ['goodbye', true, 3, 'dave/hello[9]/thanks/goodbye[3]']
            ];
        $expr = PathExpr::parse('dave');
        foreach ($cases as $case) {
            list($name, $isListItem, $index, $path) = $case;
            $component =
                new PathComponent($name, $isListItem, $index);
            $expr = $expr->append($component);
            Assert::equivalentData((string) $expr, $path);
        }
    }

    public function testPathMatches1()
    {
        $paths =
            [
                ['hello', 'hello', true],
                ['hello', 'goodbye', false],
                ['hello/goodbye', 'hello/goodbye', true],
                ['hello/goodbye', 'goodbye/goodbye', false],
                ['hello[*]', 'hello[9]', true],
                ['hello[8]', 'hello[9]', false],
                ['hello[8]', 'hello[*]', false],
                ['hello/goodbye[*]', 'hello/goodbye[9]', true],
                ['hello/goodbye[8]', 'hello/goodbye[9]', false],
                ['hello/goodbye[8]', 'hello/goodbye[*]', false],
                ['hello/goodbye[*]/thanks', 'hello/goodbye[9]/thanks', true],
                ['hello/goodbye[8]/thanks', 'hello/goodbye[9]/thanks', false],
                ['hello/goodbye[8]/thanks', 'hello/goodbye[*]/thanks', false],
            ];
        foreach ($paths as $p) {
            list($lhs, $rhs, $matches) = $p;
            $lExpr = PathExpr::parse($lhs);
            $rExpr = PathExpr::parse($rhs);
            Assert::equivalentData(
                $lExpr->matches($rExpr),
                $matches,
                "The path $lhs does not match"
            );
        }
    }

    public function testPathMatches2()
    {
        $lhs = PathExpr::parse('children/child[0]/account');
        $rhs = PathExpr::parse('children/child[1]/account');
        Assert::isFalse($lhs->matches($rhs));
    }

    public function testPathMatches3()
    {
        $lhs = PathExpr::parse('children/child[1]/account');
        $rhs = PathExpr::parse('children/child[0]/account');
        Assert::isFalse($lhs->matches($rhs));
    }

    public function testPathFailure1()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $expr = PathExpr::parse('/');
        $expr->last();
    }

    public function testPathFailure2()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $expr = PathExpr::parse('/');
        $expr->append(new PathComponent('hello', false, 12));
    }

    public function testPathFailure3()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $expr = PathExpr::parse('hello[-8]');
    }

    public function testPattern()
    {
        $patterns =
            [
                ['#', '', 'hell#o', false],
                ['.*#.*', '', 'hell#o', true],
                ['.*H.*', '', 'hell#o', false],
                ['.*H.*', 'i', 'hell#o', true],
                ['.*\n^l.*', '', "hel\nlo", false],
                ['.*\n^l.*', 'm', "hel\nlo", true],
                ['.*', '', "hel\nlo", false],
                ['.*', 's', "hel\nlo", true],
                ["hel\nlo", '', 'hello', false],
                ["hel\nlo", 'x', 'hello', true]
            ];
        $root = PathExpr::parse('/');
        foreach ($patterns as $p) {
            [$text, $flags, $subject, $matches] = $p;
            $scalar = new Scalar($text, $flags, $root);
            Assert::equivalentData(
                $scalar->matches($subject, $root),
                $matches,
                "The pattern '$text' with flags '$flags' does not match " .
                $subject
            );
        }
    }

    public function testDataMatcher1()
    {
        $matcher =
            self::createDataMatcher([
                '\d{7}+::/children/child[*]/account/number'
            ]);
        $actual = self::createComplexData(false, false);
        $expected = self::createComplexData(true, true);
        $matcher->assertMatch($actual, $expected, "The output does not match");
    }

    public function testDataMatcher2()
    {
        $matcher =
            self::createDataMatcher([
                '\d{7}+::/children/child[1]/account/number'
            ]);
        $actual = self::createComplexData(true, false);
        $expected = self::createComplexData(true, true);
        $matcher->assertMatch($actual, $expected, "The output does not match");
    }

    public function testDataMatcher3()
    {
        $matcher =
            self::createDataMatcher([
                '\d{7}+::/children/child[0]/account/number'
            ]);
        $actual = self::createComplexData(false, true);
        $expected = self::createComplexData(true, true);
        $matcher->assertMatch($actual, $expected, "The output does not match");
    }

    public function testDataMatcher4()
    {
        $matcher =
            self::createDataMatcher([
                '1|0::/veteran'
            ]);
        $expected = (object)
            [
                'name' => 'Sam',
                'veteran' => false
            ];
        $matcher->assertMatch(
            (object) [
                'name' => 'Sam',
                'veteran' => true
            ],
            $expected,
            "The output does not match"
        );
    }

    public function testDataMatcher5()
    {
        $matcher =
            self::createDataMatcher([
                '0|[1-9][0-9]*(\.[0-9]+)?::/weight'
            ]);
        $expected = (object)
            [
                'name' => 'Sam',
                'weight' => 0
            ];
        $matcher->assertMatch(
            (object) [
                'name' => 'Sam',
                'weight' => 99.5
            ],
            $expected,
            "The output does not match"
        );
        $matcher->assertMatch(
            (object) [
                'name' => 'Sam',
                'weight' => 150
            ],
            $expected,
            "The output does not match"
        );
        $matcher->assertMatch(
            (object) [
                'name' => 'Sam',
                'weight' => 150.0
            ],
            $expected,
            "The output does not match"
        );
    }

    public function testDataMatcherFailure1()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        self::createDataMatcher()->assertMatch(
            "hello",
            [1, 2, 3],
            "The output does not match"
        );
    }

    public function testDataMatcherFailure2()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        self::createDataMatcher()->assertMatch(
            "hello",
            (object) [
                'hello' => 'goodbye',
                'thanks' => "you're welcome"
            ],
            "The output does not match"
        );
    }

    public function testDataMatcherFailure3()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        self::createDataMatcher()->assertMatch(
            [1, 2, 3],
            (object) [
                'hello' => 'goodbye',
                'thanks' => "you're welcome"
            ],
            "The output does not match"
        );
    }

    public function testDataMatcherFailure4()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $matcher =
            self::createDataMatcher([
                '\d{7}+::/children/child[0]/account/number'
            ]);
        $actual = self::createComplexData(false, false);
        $expected = self::createComplexData(true, true);
        $matcher->assertMatch($actual, $expected, "The output does not match");
    }

    public function testDataMatcherFailure5()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $matcher =
            self::createDataMatcher([
                '\d{7}+::/children/child[1]/account/number'
            ]);
        $actual = self::createComplexData(false, false);
        $expected = self::createComplexData(true, true);
        $matcher->assertMatch($actual, $expected, "The output does not match");
    }

    public function testLoadSaveOperation1()
    {
        $this->_testLoadSaveOperation(1);
    }

    public function testLoadSaveOperation2()
    {
        $this->_testLoadSaveOperation(2);
    }

    public function testLoadSaveOperation3()
    {
        $this->_testLoadSaveOperation(3);
    }

    public function testLoadSaveOperation4()
    {
        $this->_testLoadSaveOperation(4);
    }

    public function testLoadSaveOperation5()
    {
        $this->_testLoadSaveOperation(5);
    }

    public function testLoadSaveOperation6()
    {
        $this->_testLoadSaveOperation(6);
    }

    public function testLoadSaveOperation7()
    {
        $this->_testLoadSaveOperation(7);
    }

    public function testLoadSaveOperation8()
    {
        $this->_testLoadSaveOperation(8);
    }

    public function testLoadSaveOperation9()
    {
        $this->_testLoadSaveOperation(9);
    }

    public function testLoadAll()
    {
        // Save operations to a temporary directory
        $dir = File::tempDir();
        for ($i = 1; $i <= self::LOAD_OPERATION_COUNT; ++$i) {
            $method = "getOrCheckLoadOperation$i";
            $xml = $this->$method();
            file_put_contents("$dir/operation-$i.xml", $xml);
        }

        // Load operations
        $operations = Operation::loadAll($dir);
        usort(
            $operations,
            function($a, $b)
            {
                return strcmp($a->description(), $b->description());
            }
        );

        // Validate operations
        for ($i = 0; $i < self::LOAD_OPERATION_COUNT; ++$i) {
            $method = 'getOrCheckLoadOperation' . ($i + 1);
            $this->$method($operations[$i]);
        }
    }

    public function testExecute1()
    {
        $returnValue =
            '<car>
               <make>Honda</make>
               <model>Civic</model>
             </car>';
        $returnValue = htmlspecialchars($returnValue);
        $xml =
            "<?xml version='1.0' encoding='UTF-8'?>
             <operation xmlns='http://www.coderage.com/2012/operation'>
               <description>My Operation</description>
               <name>execute</name>
               <instance class='CodeRage.Test.Test.Operation.Instance'>
                 <param name='returnValue' value='$returnValue'/>
               </instance>
               <input/>
             </operation>";
        $result = $this->executeOperation($xml);
        Assert::equivalentData(
            $result,
            (object) [
                'make' => 'Honda',
                'model' => 'Civic',
            ]
        );
    }

    public function testExecute2()
    {
        $returnValue =
            '<car>
               <make>Honda</make>
               <model>Civic</model>
             </car>';
        $returnValue = htmlspecialchars($returnValue);
        $xml =
            "<?xml version='1.0' encoding='UTF-8'?>
             <operation xmlns='http://www.coderage.com/2012/operation'>
               <description>My Operation</description>
               <name>execute</name>
               <instance class='CodeRage.Test.Test.Operation.Instance'>
                 <param name='returnValue' value='$returnValue'/>
                 <param name='returnObject' value='true'/>
               </instance>
               <input/>
             </operation>";
        $result = $this->executeOperation($xml);
        Assert::equivalentData(
            $result,
            (object) [
                'make' => 'Honda',
                'model' => 'Civic',
            ]
        );
    }

    public function testExecute3()
    {
        $this->setExpectedException();
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>My Operation</description>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="Exception"/>
                 <param name="exceptionMessage" value="Segmentation fault"/>
               </instance>
               <input/>
             </operation>';
        $this->executeOperation($xml);
    }

    public function testExecute4()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('DATABASE_ERROR');
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>My Operation</description>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="CodeRage\Error"/>
                 <param name="exceptionStatus" value="DATABASE_ERROR"/>
                 <param name="exceptionMessage" value="No such table: Car"/>
               </instance>
               <input/>
             </operation>';
        $this->executeOperation($xml);
    }

    public function testTest1()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Echo object</description>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="echo" value="true"/>
               </instance>
               <input>
                 <arg>
                   <make>Honda</make>
                   <model>Civic</model>
                 </arg>
               </input>
               <output>
                 <make>Honda</make>
                 <model>Civic</model>
               </output>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testTest2()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Echo object with embedded array</description>
               <xmlEncoding>
                 <listElement name="children" itemName="child"/>
               </xmlEncoding>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="echo" value="true"/>
               </instance>
               <input>
                 <arg>
                   <children>
                     <child>Bob</child>
                     <child>Sarah</child>
                   </children>
                 </arg>
               </input>
               <output>
                 <children>
                   <child>Bob</child>
                   <child>Sarah</child>
                 </children>
               </output>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testTest3()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Throw exception</description>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="Exception"/>
                 <param name="exceptionMessage" value="Segmentation fault"/>
               </instance>
               <input/>
               <exception>
                 <class>Exception</class>
                 <message>Segmentation fault</message>
               </exception>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testTest4()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Deduce exception class</description>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="Exception"/>
                 <param name="exceptionMessage" value="Segmentation fault"/>
               </instance>
               <input/>
               <exception>
                 <message>Segmentation fault</message>
               </exception>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testTest5()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Allow inexact error message macthing</description>
               <dataMatching>
                 <pattern text=".*" address="exception/message"/>
               </dataMatching>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="Exception"/>
                 <param name="exceptionMessage" value="Segmentation fault"/>
               </instance>
               <input/>
               <exception>
                 <class>Exception</class>
                 <message>An error occurred</message>
               </exception>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testTest6()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Throw CodeRage\Error</description>
               <dataMatching>
                 <pattern text=".*" address="exception/message"/>
               </dataMatching>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="CodeRage\Error"/>
                 <param name="exceptionStatus" value="DATABASE_ERROR"/>
                 <param name="exceptionMessage" value="No such table: car"/>
               </instance>
               <input/>
               <exception>
                 <class>CodeRage\Error</class>
                 <status>DATABASE_ERROR</status>
                 <message>An error occurred</message>
               </exception>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testTest7()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <operation xmlns="http://www.coderage.com/2012/operation">
               <description>Deduce exception class</description>
               <dataMatching>
                 <pattern text=".*" address="exception/message"/>
               </dataMatching>
               <name>execute</name>
               <instance class="CodeRage.Test.Test.Operation.Instance">
                 <param name="exceptionClass" value="CodeRage\Error"/>
                 <param name="exceptionStatus" value="DATABASE_ERROR"/>
                 <param name="exceptionMessage" value="No such table: car"/>
               </instance>
               <input/>
               <exception>
                 <status>DATABASE_ERROR</status>
                 <message>An error occurred</message>
               </exception>
             </operation>';
        $operation = $this->createOperation($xml);
        $operation->test();
    }

    public function testGenerateOperation1()
    {
        $this->_testGenerateOperation(1);
    }

    public function testGenerateOperation2()
    {
        $this->_testGenerateOperation(2);
    }

    public function testGenerateOperation3()
    {
        $this->_testGenerateOperation(3);
    }

    public function testGenerateOperation4()
    {
        $this->_testGenerateOperation(4);
    }

    public function testGenerateOperation5()
    {
        $this->_testGenerateOperation(5);
    }

    public function testGenerateAll()
    {
        // Save operations to a temporary directory
        $source = File::tempDir();
        $target = File::tempDir();
        for ($i = 1; $i <= self::EXECUTE_OPERATION_COUNT; ++$i) {
            $method = "getOrCheckGenerateOperation$i";
            $xml = $this->$method();
            file_put_contents("$source/operation-$i.xml", $xml);
        }

        // Invoke generateAll
        Operation::generateAll([
            'source' => $source,
            'target' => $target
        ]);

        // Load operations
        $operations = Operation::loadAll($target);
        usort(
            $operations,
            function($a, $b)
            {
                return strcmp($a->description(), $b->description());
            }
        );

        // Validate operations
        for ($i = 0; $i < self::EXECUTE_OPERATION_COUNT; ++$i) {
            $method = 'getOrCheckGenerateOperation' . ($i + 1);
            $this->$method($operations[$i]);
        }
    }

    public function testScheduleMissingTimeAndFromFailure()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $to = '2016-02-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'to' => $to,
                'repeat' => '*/5 1,2,3 * 3,4  *'
            ]);
    }

    public function testScheduleInconsistentParameterTimeAndFromFailure()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $time = '2016-01-01T00:00:00+00:00';
        $from = '2016-02-01T00:00:00+00:00';
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'time' => $time,
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * * *'
            ]);
    }

    public function testScheduleInvalidParameterTimeFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $schedule = new Schedule(
            [
                'time' => 11,
            ]);
    }

    public function testScheduleInvalidParameterToFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-02-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => [],
                'repeat' => '* * * * *'
            ]);
    }

    public function testInvalidParameterFromFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => [],
                'to' => $to,
                'repeat' => '* * * * *'
            ]);
    }

    public function testScheduleInconsistentDateFailure()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-02-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * * *'
            ]);
    }

    public function testScheduleInvalidParameterRepeatFormat1Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-02-01T00:00:00+00:00';
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '111* * * *'
            ]);
    }

    public function testScheduleInvalidParameterRepeatFormat2Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-02-01T00:00:00+00:00';
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '1 * * *'
            ]);
    }

    public function testScheduleInvalidParameterRepeatFormat3Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-02-01T00:00:00+00:00';
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * * 2/t'
            ]);
    }

    public function testScheduleInvalidParameterRepeatFormat4Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-02-01T00:00:00+00:00';
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * * 2/2'
            ]);
    }

    public function testScheduleInvalidParameterRepeatFormat5Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-02-01T00:00:00+00:00';
        $to = '2016-03-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * 1.2 * *'
            ]);
    }

    public function testScheduleInvalidParameterRepeatInconsistentDayAndWeekDayFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * 1 * 1'
            ]);
    }

    public function testScheduleRepeatOptionWithLeadingWhiteSpacesFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '  1 5 * */10 */2';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
    }

    public function testScheduleOutOfRangeRepeatColumnValue1Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '70 * * * *'
            ]);
    }

    public function testScheduleOutOfRangeRepeatColumnValue2Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* 25 * * *'
            ]);
    }

    public function testScheduleOutOfRangeRepeatColumnValue3Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * 0 * *'
            ]);
    }

    public function testScheduleOutOfRangeRepeatColumnValue4Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * 40 *'
            ]);
    }

    public function testScheduleOutOfRangeRepeatColumnValue5Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * * 10'
            ]);
    }

    public function testScheduleInvalidModulusRepeatColumnValue1Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '*/100 * * * *'
            ]);
    }

    public function testScheduleInvalidModulusRepeatColumnValue2Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* */70 * * *'
            ]);
    }

    public function testScheduleInvalidModulusRepeatColumnValue3Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * */32 * *'
            ]);
    }

    public function testScheduleInvalidModulusRepeatColumnValue4Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * */13 *'
            ]);
    }

    public function testScheduleInvalidModulusRepeatColumnValue5Failure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '*00 * * * */7'
            ]);
    }

    public function testScheduleRepeatValuesInDesendingOrderFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * 4,3,2,1 *'
            ]);
    }

    public function testScheduleRepeatValueInDesendingOrderFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => '* * * 4,3,3 *'
            ]);
    }

    public function testScheduleRepeatOption1()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '* * * * *';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => null,
                'hour' => null,
                'day' => null,
                'month' => null,
                'weekday' => null
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testScheduleRepeatOption2()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '20,30,40 * * * *';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => [20, 30, 40],
                'hour' => null,
                'day' => null,
                'month' => null,
                'weekday' => null
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testScheduleRepeatOption3()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '* 2,4,5 * */10 *';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => null,
                'hour' => [2, 4, 5],
                'day' => null,
                'month' => [1, 11],
                'weekday' => null
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testScheduleRepeatOption4()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '* 2,4,5  */5  11  *';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => null,
                'hour' => [2, 4, 5],
                'day' => [1, 6, 11, 16, 21, 26, 31],
                'month' => [11],
                'weekday' => null
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testScheduleRepeatOption5()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '*   *   * */10 4,5,6';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => null,
                'hour' => null,
                'day' => null,
                'month' => [1, 11],
                'weekday' => [4, 5, 6]
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testScheduleRepeatOption6()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '1   2,4,5   1   */10   *';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => [1],
                'hour' => [2, 4, 5],
                'day' => [1],
                'month' => [1, 11],
                'weekday' => null
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testScheduleRepeatOption7()
    {
        $from = '2016-04-01T00:00:00+00:00';
        $to = '2016-05-01T00:00:00+00:00';
        $repeat = '1   2,4,5   *   *   */2';
        $schedule = new Schedule(
            [
                'from' => $from,
                'to' => $to,
                'repeat' => $repeat
            ]);
        $expectedValues =
            [
                'minute' => [1],
                'hour' => [2, 4, 5],
                'day' => null,
                'month' => null,
                'weekday' => [0, 2, 4, 6]
            ];
        self::checkSchedule($schedule, $repeat, $expectedValues);
    }

    public function testExecutionPlanMinuteHourDayMonthAndWeekday()
    {
        self::checkExecutionPlan("min-hour-day-month-weekday.xml");
    }

    public function testExecutionPlanHourAndMinute()
    {
         self::checkExecutionPlan("hour-minute.xml");
    }

    public function testExecutionPlanDay()
    {
        self::checkExecutionPlan("day.xml");
    }

    public function testExecutionPlanWeekday()
    {
        self::checkExecutionPlan("weekday.xml");
    }

    public function testExecutionPlanMonth()
    {
        self::checkExecutionPlan("month.xml");
    }

    public function testLoadSaveScheduledOperationList()
    {
        // Load operation list from an XML element and validate
        $method = "getOrCheckLoadScheduledOperationList1";
        $xml = $this->$method();
        $operationList = $this->createOperation($xml);
        $this->$method($operationList);

        // Store operation list
        $temp1 = File::temp();
        file_put_contents($temp1, $xml);

        // Load operation list from file and validates
        $operationList = Operation::load($temp1);
        $this->$method($operationList);

         // Save operation list
        $temp2 = File::temp();
        $operationList->save($temp2);

        // Compare xml files before and after saving, removing comments and
        // leading white spaces for comparison
        $file1 = preg_replace('#^\s+#m', '', file_get_contents($temp1));
        $file2 = preg_replace('#^\s+#m', '', file_get_contents($temp2));
        $file2 = preg_replace('#<!--.*-->\n#', '', $file2);
        if ($file1 != $file2)
            throw new
                \CodeRage\Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' => 'XML differs before and after saving'
                ]);
    }

    public function testGenerateScheduledOperationList()
    {
        // Load operation from an XML element and validate
        $method = "getOrCheckGenerateScheduledOperationList1";
        $xml = $this->$method();
        $operationList = $this->createOperation($xml);
        $operationList->generate();
        $this->$method($operationList);
    }

    private function _testLoadSaveOperation($index)
    {
        // Load operation from an XML element and validate
        $method = "getOrCheckLoadOperation$index";
        $xml = $this->$method();
        $operation = $this->createOperation($xml);
        $this->$method($operation);

        // Store operation
        $temp = File::temp();
        file_put_contents($temp, $xml);

        // Load operation from file and validates
        $operation = Operation::load($temp);
        $this->$method($operation);
    }

    private function _testGenerateOperation($index)
    {
        // Load operation from an XML element and validate
        $method = "getOrCheckGenerateOperation$index";
        $xml = $this->$method();
        $operation = $this->createOperation($xml);
        $operation->generate();
        $this->$method($operation);
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation1($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 1</description>
                   <name>execute</name>
                   <input/>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 1"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation2($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 2</description>
                   <nativeDataEncoding/>
                   <xmlEncoding/>
                   <name>execute</name>
                   <input/>
                   <output>hello</output>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 2"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), "hello");
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation3($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 3</description>
                   <name>execute</name>
                   <instance class="Vehicle.Car">
                     <param name="make" value="Honda"/>
                     <param name="model" value="Civic"/>
                   </instance>
                   <input/>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 3"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            $instance = $operation->instance();
            Assert::equivalentData($instance->class(), 'Vehicle.Car');
            Assert::equivalentData($instance->classPath(), null);
            Assert::equivalentData(
                (object) $instance->params(),
                (object) [
                    'make' => 'Honda',
                    'model' => 'Civic'
                ]
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation4($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 4</description>
                   <name>execute</name>
                   <instance class="Vehicle.Car" classPath="/usr/lib">
                     <param name="make" value="Honda"/>
                     <param name="model" value="Civic"/>
                   </instance>
                   <input/>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 4"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            $instance = $operation->instance();
            Assert::equivalentData($instance->class(), 'Vehicle.Car');
            Assert::equivalentData($instance->classPath(), '/usr/lib');
            Assert::equivalentData(
                (object) $instance->params(),
                (object) [
                    'make' => 'Honda',
                    'model' => 'Civic'
                ]
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation5($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 5</description>
                   <nativeDataEncoding>
                     <option name="version" value="2.0"/>
                   </nativeDataEncoding>
                   <xmlEncoding>
                     <listElement name="children" itemName="child"/>
                     <listElement name="accounts" itemName="account"/>
                   </xmlEncoding>
                   <dataMatching>
                     <pattern text="dave" flags="i" address="account/number"/>
                   </dataMatching>
                   <name>execute</name>
                   <input>
                     <arg>hello</arg>
                     <arg>
                       <name>Tony</name>
                       <age>20</age>
                     </arg>
                   </input>
                   <output>
                     <name>Sarah</name>
                     <age>17</age>
                   </output>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 5"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                (object) ['version' => '2.0']
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                (object) [
                    'children' => 'child',
                    'accounts' => 'account',
                ]
            );
            $patterns = $operation->dataMatcher()->constraints();
            Assert::equivalentData($patterns[0]->text(), "dave");
            Assert::equivalentData($patterns[0]->flags(), "i");
            Assert::equivalentData(
                (string) $patterns[0]->address(),
                "/account/number"
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData(
                $operation->input(),
                [
                    'hello',
                    [
                        'name' => 'Tony',
                        'age' => '20'
                    ]
                ]
            );
            Assert::equivalentData(
                $operation->output(),
                (object) [
                    'name' => 'Sarah',
                    'age' => '17'
                ]
            );
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation6($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 6</description>
                   <name>execute</name>
                   <input/>
                   <exception>
                     <message>Segmentation fault</message>
                   </exception>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 6"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            $exception = $operation->exception();
            Assert::equivalentData($exception->class(), 'Exception');
            Assert::equivalentData(
                $exception->status(),
                null
            );
            Assert::equivalentData(
                $exception->message(),
                'Segmentation fault'
            );
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation7($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 7</description>
                   <name>execute</name>
                   <input/>
                   <exception>
                     <class>Exception</class>
                     <message>Segmentation fault</message>
                   </exception>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 7"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            $exception = $operation->exception();
            Assert::equivalentData($exception->class(), 'Exception');
            Assert::equivalentData(
                $exception->status(),
                null
            );
            Assert::equivalentData(
                $exception->message(),
                'Segmentation fault'
            );
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation8($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 8</description>
                   <name>execute</name>
                   <input/>
                   <exception>
                     <status>MISSING_PARAMETER</status>
                     <message>Missing icicle</message>
                   </exception>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 8"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            $exception = $operation->exception();
            Assert::equivalentData($exception->class(), 'CodeRage\Error');
            Assert::equivalentData(
                $exception->status(),
                'MISSING_PARAMETER'
            );
            Assert::equivalentData(
                $exception->message(),
                'Missing icicle'
            );
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckLoadOperation9($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 9</description>
                   <name>execute</name>
                   <input/>
                   <exception>
                     <class>CodeRage\Error</class>
                     <status>MISSING_PARAMETER</status>
                     <message>Missing icicle</message>
                   </exception>
                 </operation>';
        } else {
            Assert::equivalentData(
                $operation->description(),
                "Operation 9"
            );
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                new stdClass
            );
            Assert::equivalentData(
                $operation->dataMatcher()->constraints(),
                []
            );
            Assert::equivalentData($operation->name(), 'execute');
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData($operation->output(), null);
            $exception = $operation->exception();
            Assert::equivalentData($exception->class(), 'CodeRage\Error');
            Assert::equivalentData(
                $exception->status(),
                'MISSING_PARAMETER'
            );
            Assert::equivalentData(
                $exception->message(),
                'Missing icicle'
            );
        }
    }

    /**
     * Returns an XML definition of an scheduleOperationList, if no
     * scheduleOperationList is supplied; otherwise throws an exception if the
     * specified scheduleOperationList does not meet the expected critieria
     *
     * @param string CodeRage\Test\Operations\ScheduleOperationList The
     *   scheduled operation list to be validated, if any
     * @return string The XML scheduleOperationList definition, if any
     */
    private function getOrCheckLoadScheduledOperationList1($operationList = null)
    {
        if ($operationList === null) {
            return
               '<?xml version="1.0" encoding="utf-8"?>
                <scheduledOperationList xmlns="http://www.coderage.com/2012/operation" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <description>Operation list 1</description>
                  <properties>
                    <property name="cost" value="0.00"/>
                  </properties>
                  <config>
                    <property name="path" value="xxx/yyy"/>
                  </config>
                  <operations>
                    <operation>
                      <description>Operation 1</description>
                      <properties>
                        <property name="cost" value="0.00"/>
                      </properties>
                      <config>
                        <property name="path" value="xxx/yyy"/>
                      </config>
                      <nativeDataEncoding/>
                      <xmlEncoding/>
                      <dataMatching/>
                      <schedule time="2010-01-01T00:00:00+00:00"/>
                      <name>execute</name>
                      <input/>
                      <output>1</output>
                    </operation>
                  </operations>
                  <repeatingOperations>
                    <operation>
                      <description>Operation 1</description>
                      <properties>
                        <property name="cost" value="0.00"/>
                      </properties>
                      <config>
                        <property name="path" value="xxx/yyy"/>
                      </config>
                      <nativeDataEncoding/>
                      <xmlEncoding/>
                      <schedule from="2016-04-02T00:00:00+00:00" to="2016-05-01T00:00:00+00:00" repeat="0  1,2 */2  4  *"/>
                      <name>execute</name>
                      <input/>
                      <output>1</output>
                    </operation>
                  </repeatingOperations>
                </scheduledOperationList>
                ';
        } else {
            Assert::equivalentData(
                count($operationList->operations()),
                2
            );
            Assert::equivalentData(
                $operationList->description(),
                "Operation list 1"
            );
            $properties = [];
            foreach ($operationList->properties()->propertyNames() as $property) {
                $value = $operationList->properties()->getProperty($property);
                $properties[$property] = $value;
            }
            Assert::equivalentData(
                (object) $properties,
                (object) [ 'cost' => "0.00" ]
            );
            Assert::equivalentData(
                (object) $operationList->configProperties(),
                (object) [ 'path' => "xxx/yyy" ]
            );
            $operation = $operationList->operations()[0];
            $repeatingOperation = $operationList->operations()[-1];
            foreach ([$operation, $repeatingOperation] as $op) {
                Assert::equivalentData(
                    $op->description(),
                    "Operation 1"
                );
                Assert::equivalentData(
                    (object) $op->nativeDataEncoder()->options(),
                    new stdClass
                );
                Assert::equivalentData(
                    $op->xmlEncoder()->namespace(),
                    null
                );
                Assert::equivalentData(
                    (object) $op->xmlEncoder()->listElements(),
                    new stdClass
                );
                Assert::equivalentData(
                    $op->dataMatcher()->constraints(),
                    []
                );
                Assert::equivalentData($op->name(), 'execute');
                Assert::equivalentData($op->input(), []);
                Assert::equivalentData($op->output(), '1');
                Assert::equivalentData($op->exception(), null);
                $properties = [];
                foreach ($op->properties()->propertyNames() as $property) {
                    $value = $operationList->properties()->getProperty($property);
                    $properties[$property] = $value;
                }
                Assert::equivalentData(
                    (object) $properties,
                    (object) [ 'cost' => "0.00" ]
                );
                Assert::equivalentData(
                    (object) $op->configProperties(),
                    (object) [ 'path' => "xxx/yyy" ]
                );
            }
        }
        $schedule =
            new Schedule(
                [
                    'time' => "2010-01-01T00:00:00+00:00"
                ]
            );
        if ($schedule != $operation->schedule()) {
            throw new
                \CodeRage\Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' => 'Failed checking operation schedule'
                ]);
        }
        $schedule =
            new Schedule(
                [
                    'to' => "2016-05-01T00:00:00+00:00",
                    'from' => "2016-04-02T00:00:00+00:00",
                    'repeat' => "0  1,2 */2  4  *"
                ]
            );
        if ($schedule != $repeatingOperation->schedule()) {
            throw new
                \CodeRage\Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' => 'Failed checking repeating operation schedule'
                ]);
        }

    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria; the intention is that $operation is the result of
     * invoking generate() on an operation constructed using the XML returned
     * by this method
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckGenerateOperation1($operation = null)
    {
        $returnValue =
            '<car>
               <make>Honda</make>
               <model>Civic</model>
             </car>';
        if ($operation === null) {
            $returnValue = htmlspecialchars($returnValue);
            return
                "<?xml version='1.0' encoding='UTF-8'?>
                 <operation xmlns='http://www.coderage.com/2012/operation'>
                   <description>Operation 1</description>
                   <nativeDataEncoding>
                     <option name='version' value='2.0'/>
                   </nativeDataEncoding>
                   <xmlEncoding>
                     <listElement name='children' itemName='child'/>
                     <listElement name='accounts' itemName='account'/>
                   </xmlEncoding>
                   <dataMatching>
                     <pattern text='dave' flags='i' address='account/number'/>
                   </dataMatching>
                   <name>execute</name>
                   <instance class='CodeRage.Test.Test.Operation.Instance'>
                     <param name='returnValue' value='$returnValue'/>
                   </instance>
                   <input/>
                 </operation>";
        } else {
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                (object) ['version' => '2.0']
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                (object) [
                    'children' => 'child',
                    'accounts' => 'account',
                ]
            );
            $patterns = $operation->dataMatcher()->constraints();
            Assert::equivalentData($patterns[0]->text(), "dave");
            Assert::equivalentData($patterns[0]->flags(), "i");
            Assert::equivalentData(
                (string) $patterns[0]->address(),
                "/account/number"
            );
            Assert::equivalentData($operation->name(), 'execute');
            $instance = $operation->instance();
            Assert::equivalentData(
                $instance->class(),
                'CodeRage.Test.Test.Operation.Instance'
            );
            $params = $instance->params();
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData(
                $operation->output(),
                (object) [
                    'make' => 'Honda',
                    'model' => 'Civic',
                ]
            );
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria; the intention is that $operation is the result of
     * invoking generate() on an operation constructed using the XML returned
     * by this method
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    private function getOrCheckGenerateOperation2($operation = null)
    {
        $returnValue =
            '<car>
               <make>Honda</make>
               <model>Civic</model>
             </car>';
        if ($operation === null) {
            $returnValue = htmlspecialchars($returnValue);
            return
                "<?xml version='1.0' encoding='UTF-8'?>
                 <operation xmlns='http://www.coderage.com/2012/operation'>
                   <description>Operation 2</description>
                   <nativeDataEncoding>
                     <option name='version' value='2.0'/>
                   </nativeDataEncoding>
                   <xmlEncoding>
                     <listElement name='children' itemName='child'/>
                     <listElement name='accounts' itemName='account'/>
                   </xmlEncoding>
                   <dataMatching>
                     <pattern text='dave' flags='i' address='account/number'/>
                   </dataMatching>
                   <name>execute</name>
                   <instance class='CodeRage.Test.Test.Operation.Instance'>
                     <param name='returnValue' value='$returnValue'/>
                     <param name='returnObject' value='true'/>
                   </instance>
                   <input/>
                 </operation>";
        } else {
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                (object) ['version' => '2.0']
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                (object) [
                    'children' => 'child',
                    'accounts' => 'account',
                ]
            );
            $patterns = $operation->dataMatcher()->constraints();
            Assert::equivalentData($patterns[0]->text(), "dave");
            Assert::equivalentData($patterns[0]->flags(), "i");
            Assert::equivalentData(
                (string) $patterns[0]->address(),
                "/account/number"
            );
            Assert::equivalentData($operation->name(), 'execute');
            $instance = $operation->instance();
            Assert::equivalentData(
                $instance->class(),
                'CodeRage.Test.Test.Operation.Instance'
            );
            $params = $instance->params();
            Assert::equivalentData($operation->input(), []);
            Assert::equivalentData(
                $operation->output(),
                (object) [
                    'make' => 'Honda',
                    'model' => 'Civic',
                ]
            );
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria; the intention is that $operation is the result of
     * invoking generate() on an operation constructed using the XML returned
     * by this method
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    public function getOrCheckGenerateOperation3($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 3</description>
                   <nativeDataEncoding>
                     <option name="version" value="2.0"/>
                   </nativeDataEncoding>
                   <xmlEncoding>
                     <listElement name="children" itemName="child"/>
                     <listElement name="accounts" itemName="account"/>
                   </xmlEncoding>
                   <dataMatching>
                     <pattern text="dave" flags="i" address="account/number"/>
                   </dataMatching>
                   <name>execute</name>
                   <instance class="CodeRage.Test.Test.Operation.Instance">
                     <param name="echo" value="true"/>
                   </instance>
                   <input>
                     <arg>
                       <children>
                         <child>Bob</child>
                         <child>Sarah</child>
                       </children>
                     </arg>
                   </input>
                 </operation>';
        } else {
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                (object) ['version' => '2.0']
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                (object) [
                    'children' => 'child',
                    'accounts' => 'account',
                ]
            );
            $patterns = $operation->dataMatcher()->constraints();
            Assert::equivalentData($patterns[0]->text(), "dave");
            Assert::equivalentData($patterns[0]->flags(), "i");
            Assert::equivalentData(
                (string) $patterns[0]->address(),
                "/account/number"
            );
            Assert::equivalentData($operation->name(), 'execute');
            $instance = $operation->instance();
            Assert::equivalentData(
                $instance->class(),
                'CodeRage.Test.Test.Operation.Instance'
            );
            $params = $instance->params();
            Assert::equivalentData($params['echo'], 'true');
            Assert::equivalentData(
                $operation->input(),
                [
                    ['children' => ['Bob', 'Sarah']]
                ]
            );
            Assert::equivalentData(
                $operation->output(),
                (object) [
                    'children' => ['Bob', 'Sarah']
                ]
            );
            Assert::equivalentData($operation->exception(), null);
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria; the intention is that $operation is the result of
     * invoking generate() on an operation constructed using the XML returned
     * by this method
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    public function getOrCheckGenerateOperation4($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 4</description>
                   <nativeDataEncoding>
                     <option name="version" value="2.0"/>
                   </nativeDataEncoding>
                   <xmlEncoding>
                     <listElement name="children" itemName="child"/>
                     <listElement name="accounts" itemName="account"/>
                   </xmlEncoding>
                   <dataMatching>
                     <pattern text="dave" flags="i" address="account/number"/>
                   </dataMatching>
                   <name>execute</name>
                   <instance class="CodeRage.Test.Test.Operation.Instance">
                     <param name="exceptionClass" value="Exception"/>
                     <param name="exceptionMessage" value="Segmentation fault"/>
                   </instance>
                   <input/>
                 </operation>';
        } else {
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                (object) ['version' => '2.0']
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                (object) [
                    'children' => 'child',
                    'accounts' => 'account',
                ]
            );
            $patterns = $operation->dataMatcher()->constraints();
            Assert::equivalentData($patterns[0]->text(), "dave");
            Assert::equivalentData($patterns[0]->flags(), "i");
            Assert::equivalentData(
                (string) $patterns[0]->address(),
                "/account/number"
            );
            Assert::equivalentData($operation->name(), 'execute');
            $instance = $operation->instance();
            Assert::equivalentData(
                $instance->class(),
                'CodeRage.Test.Test.Operation.Instance'
            );
            $params = $instance->params();
            Assert::equivalentData(
                $params['exceptionClass'],
                'Exception'
            );
            Assert::isFalse(isset($params['exceptionStatus']));
            Assert::equivalentData(
                $params['exceptionMessage'],
                'Segmentation fault'
            );
            Assert::equivalentData($operation->input(), []);
            $exception = $operation->exception();
            Assert::equivalentData($exception->class(), 'Exception');
            Assert::equivalentData($exception->status(), null);
            Assert::equivalentData(
                $exception->message(),
                'Segmentation fault'
            );
        }
    }

    /**
     * Returns an XML definition of an operation, if no operation is supplied;
     * otherwise throws an exception if the specified operation does not meet
     * the expected critieria; the intention is that $operation is the result of
     * invoking generate() on an operation constructed using the XML returned
     * by this method
     *
     * @param string $operation The operation to be validated, if any
     * @return string The XML operation definition, if any
     */
    public function getOrCheckGenerateOperation5($operation = null)
    {
        if ($operation === null) {
            return
                '<?xml version="1.0" encoding="UTF-8"?>
                 <operation xmlns="http://www.coderage.com/2012/operation">
                   <description>Operation 5</description>
                   <nativeDataEncoding>
                     <option name="version" value="2.0"/>
                   </nativeDataEncoding>
                   <xmlEncoding>
                     <listElement name="children" itemName="child"/>
                     <listElement name="accounts" itemName="account"/>
                   </xmlEncoding>
                   <dataMatching>
                     <pattern text="dave" flags="i" address="account/number"/>
                   </dataMatching>
                   <name>execute</name>
                   <instance class="CodeRage.Test.Test.Operation.Instance">
                     <param name="exceptionClass" value="CodeRage\Error"/>
                     <param name="exceptionStatus" value="DATABASE_ERROR"/>
                     <param name="exceptionMessage" value="No such table: Car"/>
                   </instance>
                   <input/>
                 </operation>';
        } else {
            Assert::equivalentData(
                (object) $operation->nativeDataEncoder()->options(),
                (object) ['version' => '2.0']
            );
            Assert::equivalentData(
                $operation->xmlEncoder()->namespace(),
                null
            );
            Assert::equivalentData(
                (object) $operation->xmlEncoder()->listElements(),
                (object) [
                    'children' => 'child',
                    'accounts' => 'account',
                ]
            );
            $patterns = $operation->dataMatcher()->constraints();
            Assert::equivalentData($patterns[0]->text(), "dave");
            Assert::equivalentData($patterns[0]->flags(), "i");
            Assert::equivalentData(
                (string) $patterns[0]->address(),
                "/account/number"
            );
            Assert::equivalentData($operation->name(), 'execute');
            $instance = $operation->instance();
            Assert::equivalentData(
                $instance->class(),
                'CodeRage.Test.Test.Operation.Instance'
            );
            $params = $instance->params();
            Assert::equivalentData(
                $params['exceptionClass'],
                'CodeRage\Error'
            );
            Assert::equivalentData(
                $params['exceptionStatus'],
                'DATABASE_ERROR'
            );
            Assert::equivalentData(
                $params['exceptionMessage'],
                'No such table: Car'
            );
            Assert::equivalentData($operation->input(), []);
            $exception = $operation->exception();
            Assert::equivalentData($exception->class(), 'CodeRage\Error');
            Assert::equivalentData(
                $exception->status(),
                'DATABASE_ERROR'
            );
            Assert::equivalentData(
                $exception->message(),
                'No such table: Car'
            );
        }
    }

    /**
     * Returns an XML definition of an scheduleOperationList, if no
     * scheduleOperationList is supplied; otherwise throws an exception if the
     * specified scheduleOperationList does not meet the expected critieria
     *
     * @param string CodeRage\Test\Operations\ScheduleOperationList The
     *   scheduled operation list to be validated, if any
     * @return string The XML scheduled operation list definition, if any
     */
    private function getOrCheckGenerateScheduledOperationList1($operationList = null)
    {
        $returnValue =
            '<car>
               <make>Honda</make>
               <model>Civic</model>
             </car>';
        if ($operationList === null) {
            $returnValue = htmlspecialchars($returnValue);
            return
               "<?xml version='1.0'?>
                <scheduledOperationList xmlns='http://www.coderage.com/2012/operation'>
                  <description>Operation list 1</description>
                  <properties>
                    <property name='cost' value='0.00'/>
                  </properties>
                  <config>
                    <property name='path' value='xxx/yyy'/>
                  </config>
                  <operations>
                    <operation xmlns='http://www.coderage.com/2012/operation'>
                    <description>Operation 1</description>
                    <nativeDataEncoding>
                      <option name='version' value='2.0'/>
                    </nativeDataEncoding>
                    <xmlEncoding>
                      <listElement name='children' itemName='child'/>
                      <listElement name='accounts' itemName='account'/>
                    </xmlEncoding>
                    <dataMatching>
                      <pattern text='dave' flags='i' address='account/number'/>
                    </dataMatching>
                    <schedule time='2010-01-01T00:00:00+00:00'/>
                    <name>execute</name>
                    <instance class='CodeRage.Test.Test.Operation.Instance'>
                      <param name='returnValue' value='$returnValue'/>
                    </instance>
                    <input/>
                  </operation>
                  </operations>
                  <repeatingOperations>
                    <operation xmlns='http://www.coderage.com/2012/operation'>
                      <description>Operation 1</description>
                      <nativeDataEncoding>
                        <option name='version' value='2.0'/>
                      </nativeDataEncoding>
                      <xmlEncoding>
                        <listElement name='children' itemName='child'/>
                        <listElement name='accounts' itemName='account'/>
                      </xmlEncoding>
                      <schedule from='2016-04-02T00:00:00+00:00' to='2016-05-01T00:00:00+00:00' repeat='0   1,2    */2 4 *'/>
                      <name>execute</name>
                      <instance class='CodeRage.Test.Test.Operation.Instance'>
                        <param name='returnValue' value='$returnValue'/>
                      </instance>
                      <input/>
                    </operation>
                  </repeatingOperations>
                </scheduledOperationList>";
        } else {
            Assert::equivalentData(
                count($operationList->operations()),
                2
            );
            Assert::equivalentData(
                $operationList->description(),
                "Operation list 1"
            );
            $properties = [];
            foreach ($operationList->properties()->propertyNames() as $property) {
                $value = $operationList->properties()->getProperty($property);
                $properties[$property] = $value;
            }
            Assert::equivalentData(
                (object) $properties,
                (object) [ 'cost' => "0.00" ]
            );
            Assert::equivalentData(
                (object) $operationList->configProperties(),
                (object) [ 'path' => "xxx/yyy" ]
            );
            $operation = $operationList->operations()[0];
            $repeatingOperation = $operationList->operations()[-1];
            foreach ([$operation, $repeatingOperation] as $op) {
                Assert::equivalentData(
                    (object) $operation->nativeDataEncoder()->options(),
                    (object) ['version' => '2.0']
                );
                Assert::equivalentData(
                    $operation->xmlEncoder()->namespace(),
                    null
                );
                Assert::equivalentData(
                    (object) $operation->xmlEncoder()->listElements(),
                    (object) [
                        'children' => 'child',
                        'accounts' => 'account',
                    ]
                );
                $patterns = $operation->dataMatcher()->constraints();
                Assert::equivalentData($patterns[0]->text(), "dave");
                Assert::equivalentData($patterns[0]->flags(), "i");
                Assert::equivalentData(
                    (string) $patterns[0]->address(),
                    "/operation[0]/account/number"
                );
                Assert::equivalentData($operation->name(), 'execute');
                $instance = $operation->instance();
                Assert::equivalentData(
                    $instance->class(),
                    'CodeRage.Test.Test.Operation.Instance'
                );
                $params = $instance->params();
                Assert::equivalentData($operation->input(), []);
                Assert::equivalentData(
                    $operation->output(),
                    (object) [
                        'make' => 'Honda',
                        'model' => 'Civic',
                    ]
                );
                Assert::equivalentData($operation->exception(), null);
            }
        }
        $schedule =
            new Schedule(
                [
                    'time' => "2010-01-01T00:00:00+00:00"
                ]
            );
        if ($schedule != $operation->schedule()) {
            throw new
                \CodeRage\Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' => 'Failed checking operation schedule'
                ]);
        }
        $schedule =
            new Schedule(
                [
                    'to' => "2016-05-01T00:00:00+00:00",
                    'from' => "2016-04-02T00:00:00+00:00",
                    'repeat' => '0   1,2    */2 4 *'
                ]
            );
        if ($schedule != $repeatingOperation->schedule()) {
            throw new
                \CodeRage\Error([
                    'status' => 'ASSERTION_FAILED',
                    'details' => 'Failed checking repeting operation schedule'
                ]);
        }

    }

    /**
     * Returns an instance of CodeRage\Test\Operations\DataMatcher with list
     * elements (children => child, accounts => account).
     *
     * @param $constraints A list strings used to construct instance of
     *   CodeRage\Test\Operations\Constraint\Scalar, specified in the format
     *   text:flags:expression
     */
    private function createDataMatcher($patterns = [])
    {
        $xmlEncoder =
            new \CodeRage\Util\XmlEncoder(
                    null,
                    [
                        'children' => 'child',
                        'accounts' => 'account'
                    ]
                );
        $scalars = [];
        foreach ($patterns as $p) {
            [$text, $flags, $expression] = explode(':', $p);
            $scalars[] =
                new Scalar(
                        $text,
                        $flags,
                        PathExpr::parse($expression)
                    );
        }
        return new \CodeRage\Test\Operations\DataMatcher($xmlEncoder, $scalars);
    }

    private function
        createComplexData(
            $obfuscateFirstAccount,
            $obfuscateSecondAccount
        )
    {
        return (object)
            [
                'name' => 'Sam',
                'children' =>
                    [
                        (object) [
                            'name' => 'Bob',
                            'account' => (object)
                                [
                                    'type' => 'checking',
                                    'number' => $obfuscateFirstAccount ?
                                        'xxxxxxx' :
                                        '2845791',
                                    'institution' =>
                                        "People's Bank of Brighton Beach"
                                ]
                        ],
                        (object) [
                            'name' => 'Sarah',
                            'account' => (object)
                                [
                                    'type' => 'savings',
                                    'number' => $obfuscateSecondAccount ?
                                        'xxxxxxx' :
                                        '1934783',
                                    'institution' =>
                                        "People's Bank of Brighton Beach"
                                ]
                        ]
                    ]
            ];
    }

    /**
     * Returns an operation, operation list, or schedule operation list
     * created from the given XML data using
     * CodeRage\Test\Operations\Operation::load()
     *
     * @param unknown $xml
     */
    private function createOperation($xml)
    {
        $dom = Xml::loadDocumentXml($xml, Operation::SCHEMA_PATH);
        $temp = File::temp();
        file_put_contents($temp, $dom->saveXml());
        return Operation::load($temp);
    }

    private function executeOperation($xml)
    {
        return $this->createOperation($xml)->execute();
    }

    private static function checkSchedule(
        $schedule, $expectedDefinition, $expectedValues)
    {
        $repeat = '';
        foreach (['minute', 'hour', 'day', 'month', 'weekday'] as $type)
        {
            $spec = $schedule->$type();
            $repeat .= $spec->definition();
            Assert::equal(
                $spec->values(),
                $expectedValues[$type],
                "type '$type' with definition '{$spec->definition()}'"
            );
        }
        Assert::equal($repeat, $expectedDefinition);
    }

    private static function checkExecutionPlan($filename)
    {
        $dir = __DIR__ . '/../Operations/Test/';
        $dom = Xml::loadDocument("$dir/ExecutionPlan/$filename");
        $dom->schemaValidate($dir . '/' . self::EXECUTION_PLAN_SCHEMA);
        $operations = Xml::firstChildElement($dom->documentElement, 'operations');
        $schedulables = [];
        foreach (Xml::childElements($operations, 'operation') as $operation) {
            $elt = Xml::firstChildElement($operation, 'schedule');
            $schedule = null;
            if ($elt->hasAttribute('time')) {
                $schedule =
                    new Schedule(['time' => $elt->getAttribute('time')]);
            }  else {
                $schedule =
                    new Schedule(
                        [
                            'to' => $elt->getAttribute('to'),
                            'from' => $elt->getAttribute('from'),
                            'repeat' => $elt->getAttribute('repeat'),
                        ]
                    );
            }
            $schedulables[] =
               new LabeledSchedule(
                       $operation->getAttribute('label'),
                       $schedule
                   );
        }

        // Actual output
        $actual = [];
        $plan = new ExecutionPlan('Test execution plan', $schedulables);
        foreach ($plan->steps() as $step) {
            $actual[] = (object)
                [
                    'label' => $step->operation()->label(),
                    'date' => $step->time()->format(DATE_W3C)
                ];
        }

        // Expected output
        $expected = [];
        $stepsElement = Xml::firstChildElement($dom->documentElement, 'steps');
        foreach (Xml::childElements($stepsElement, 'step') as $step) {
            $expected[] = (object)
                [
                    'label' => $step->getAttribute('label'),
                    'date' => $step->getAttribute('date')
                ];
        }

        Assert::equal($actual, $expected);
    }
}
