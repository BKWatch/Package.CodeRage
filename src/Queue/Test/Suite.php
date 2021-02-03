<?php

/**
 * Defines the class CodeRage\Queue\Test\Suite
 *
 * File:        CodeRage/Queue/Test/Suite.php
 * Date:        Mon Dec 30 19:36:30 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Queue\Test;

use drupol\phpermutations\Generators\Combinations;
use CodeRage\Access;
use CodeRage\Access\Session;
use CodeRage\Access\User;
use CodeRage\Build\Config\Array_ as ArrayConfig;
use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Db\Operations;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Queue\Manager;
use CodeRage\Queue\Pruner;
use CodeRage\Queue\Task;
use CodeRage\Test\Assert;
use CodeRage\Util\Args;
use CodeRage\Util\Random;
use CodeRage\Util\Time;


/**
 * Test suite for the classes in the namespace CodeRage\Queue
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
    const TABLES =
        [ 'CodeRageQueueTestAuxiliarylData' , 'CodeRageQueueTestBasicQueue',
          'AccessSession' ];

    /**
     * @var int
     */
    const RANDOM_STRING_LENGTH = 30;

    /**
     * @var array
     */
    const MANAGER_CTOR_OPTIONS_MINIMAL =
        [
            'queue' => 'CodeRageQueueTestBasicQueue'
        ];

    /**
     * @var array
     */
    const MANAGER_CTOR_OPTION_NAMES =
        [
            'queue', 'parameters', 'lifetime', 'maxAttempts',
            'sessionid', 'sessionLifetime', 'sessionUserid'
        ];

    /**
     * @var array
     */
    const MANAGER_CTOR_OPTION_VALUES =
        [
            'queue' => 'CodeRageQueueTestBasicQueue',
            'parameters' => '{"quality":0.91}',
            'lifetime' => 3600 * 24 * 7,
            'maxAttempts' => 100,
            'sessionLifetime' => 7200,
            'sessionUserid' => User::ROOT
        ];

    /**
     * @var array
     */
    const TASK_CTOR_REQUIRED_OPTION_NAMES =
        [
            'RecordID', 'CreationDate', 'parameters',
            'expires', 'attempts', 'status'
        ];

    /**
     * @var array
     */
    const TASK_CTOR_OPTIONAL_OPTION_NAMES =
        [
            'data1', 'data2', 'data3', 'maxAttempts',
            'sessionid', 'errorStatus', 'errorMessage'
        ];

    /**
     * @var array
     */
    const TASK_CTOR_OPTION_VALUES =
        [
            'RecordID' => 325041,
            'CreationDate' => 1478577278,
            'data1' => '75339',
            'data2' => 'text/xml; charset=utf-8',
            'data3' => 'Tue Nov  8 03:54:38 UTC 2016',
            'parameters' => '{"quality":0.91}',
            'expires' => 1481169278,
            'maxAttempts' => 100,
            'attempts' => 3,
            'status' => Task::STATUS_FAILURE,
            'errorStatus' => 'OBJECT_DOES_NOT_EXIST',
            'errorMessage' =>'No such rank: Seargennt'
        ];

    /**
     * @var array
     */
    const CREATE_TASK_OPTIONS =
        [
            'data1' => '0182573',
            'data2' => 'application/json',
            'data3' => 'Tue Dec 31 15:58:51 UTC 2019',
            'parameters' => '{"purity":"99.02%","color":"blue"}',
            'lifetime' => 3600 * 24,
            'maxAttempts' => 10
        ];

    /**
     * @var array
     */
    const TASKID = 'nc-7260';

    /**
     * @var array
     */
    const TASK_CTOR_OPTION_PROPERTY_NAMES =
        [
            'RecordID' => 'id',
            'CreationDate' => 'created'
        ];

    /**
     * @var array
     */
    const TASK_TIMESTAMP_LABELS =
        [
            'created' => 'creation date',
            'expires' => 'expiration',
            'completed' => 'completion date'
        ];

    /**
     * @var array
     */
    const TIMESTAMP_EPSILON = 60;

    /**
     * Constructs an instance of \CodeRage\Db\Test\Suite
     */
    public function __construct()
    {
        parent::__construct(
            'coderage-queue-suite',
            'Test suite for he CodeRage queue framework'
        );
    }

    /**
     * Tests constructing a manager with all valid option combinations
     */
    public function testConstructManager1()
    {
        $optionNames = self::MANAGER_CTOR_OPTION_NAMES;
        unset($optionNames['queue']);
        $optionValues = self::MANAGER_CTOR_OPTION_VALUES;
        $tool = new MockProcessor;
        $sessionid =
            Session::create([
                'userid' => $optionValues['sessionUserid'],
                'lifetime' => $optionValues['sessionLifetime']
            ])->sessionid();
        for ($i = 1, $last = count($optionValues); $i <= $last; ++$i) {
            $combinations = new Combinations($optionNames, $i);
            foreach ($combinations->generator() as $names) {
                $options =
                    [
                        'queue' => $optionValues['queue'],
                        'tool' => $tool
                    ];
                foreach ($names as $n) {
                    $v = $n == 'sessionid' ?
                        $sessionid :
                        $optionValues[$n];
                    $options[$n] = $v;
                }
                if ( isset($options['sessionid']) &&
                     ( isset($options['sessionLifetime']) ||
                       isset($options['sessionUserid']) ) )
                {
                    continue;
                }
                $manager = null;
                try {
                    $manager = new Manager($options);
                } catch (Error $e) {
                    throw new
                        Error([
                            'message' =>
                                'Failed constructing a manager with options ' .
                                json_encode($options),
                            'inner' => $e
                        ]);
                }
                if (isset($options['sessionid']))
                    Assert::equal($manager->sessionid(), $sessionid);
            }
        }
    }

    /**
     * Tests constructing a manager with the special value
     * CodeRage\Queue\Task::NO_PARAMS
     */
    public function testConstructManager2()
    {
        $options = self::managerConstructorOptions();
        $options['parameters'] = Task::NO_PARAMS;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with no tool
     */
    public function testConstructManagerMissingToolFaill()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $options = self::managerConstructorOptions();
        unset($options['tool']);
        new Manager(self::MANAGER_CTOR_OPTION_VALUES);
    }

    /**
     * Tests attempting to construct a manager with an invalid tool
     */
    public function testConstructManagerInvalidToolFaill()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['tool'] = new \DateTime;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with no queue
     */
    public function testConstructManagerMissingQueueFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $options = self::managerConstructorOptions();
        unset($options['queue']);
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with an invalid queue
     */
    public function testConstructManagerInvalidQueueFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['queue'] = INF;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with invalid runtime parameters
     */
    public function testConstructManagerInvalidParametersFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['parameters'] = -10009;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with runtime parameters set to
     * a floating point value other than CodeRage\Queue\Task::NO_PARAMS
     */
    public function testConstructManagerInvalidParametersFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['parameters'] = -0.00001425;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a non-integral lifetime
     */
    public function testConstructManagerInvalidLifetimesFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['lifetime'] = 7.5;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a negative lifetime
     */
    public function testConstructManagerInvalidLifetimesFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['lifetime'] = -11;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a non-integral "maxAttempts"
     * option
     */
    public function testConstructManagerInvalidMaxAttemptsFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['maxAttempts'] = 7.5;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a negative "maxAttempts"
     * option
     */
    public function testConstructManagerInvalidMaxAttemptsFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['maxAttempts'] = -11;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a non-string session ID
     */
    public function testConstructManagerInvalidSessionidFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        unset($options['sessionLifetime']);
        unset($options['sessionUserid']);
        $options['sessionid'] = -1009;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a session ID that doesn't
     * reference an existing session
     */
    public function testConstructManagerInvalidSessionidFail2()
    {
        // Currently thows an error with status INVALID_PARAMETER, but this
        // could change
        $this->setExpectedException('CodeRage\Error');
        $options = self::managerConstructorOptions();
        unset($options['sessionLifetime']);
        unset($options['sessionUserid']);
        $options['sessionid'] = Random::string(Session::SESSIONID_LENGTH);
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a non-integral session
     * lifetime
     */
    public function testConstructManagerInvalidSessionLifetimesFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['sessionLifetime'] = 7.5;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a negative session lifetime
     */
    public function testConstructManagerInvalidSessionLifetimesFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['sessionLifetime'] = -11;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a non-integral session
     * user ID
     */
    public function testConstructManagerInvalidSessionUseridFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $options = self::managerConstructorOptions();
        $options['sessionUserid'] = 7.5;
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a session user ID that
     * doesn't correspond to any existing user
     */
    public function testConstructManagerInvalidSessionUseridFail2()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $options = self::managerConstructorOptions();
        $options['sessionUserid'] = (int) (PHP_INT_MAX / 2);
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a session ID and a session
     * lifetime
     */
    public function testConstructManagerInconistentSessionOptionsFail1()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $options = self::managerConstructorOptions();
        $optionValues = self::MANAGER_CTOR_OPTION_VALUES;
        $options['sessionid'] =
            Session::create([
                'userid' => $optionValues['sessionUserid'],
                'lifetime' => $optionValues['sessionLifetime']
            ])->sessionid();
        unset($options['sessionUserid']);
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a session ID and a session
     * user ID
     */
    public function testConstructManagerInconistentSessionOptionsFail2()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $options = self::managerConstructorOptions();
        $optionValues = self::MANAGER_CTOR_OPTION_VALUES;
        $options['sessionid'] =
            Session::create([
                'userid' => $optionValues['sessionUserid'],
                'lifetime' => $optionValues['sessionLifetime']
            ])->sessionid();
        unset($options['sessionLifetime']);
        new Manager($options);
    }

    /**
     * Tests attempting to construct a manager with a session ID, a session
     * lifetime, and a session userid
     */
    public function testConstructManagerInconistentSessionOptionsFail3()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $options = self::managerConstructorOptions();
        $optionValues = self::MANAGER_CTOR_OPTION_VALUES;
        $options['sessionid'] =
            Session::create([
                'userid' => $optionValues['sessionUserid'],
                'lifetime' => $optionValues['sessionLifetime']
            ])->sessionid();
        new Manager($options);
    }

    /**
     * Tests constructing a task with all valid option combinations not
     * including the "task" option
     */
    public function testConstructTask1()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $allOptions =
            array_merge(
                self::TASK_CTOR_REQUIRED_OPTION_NAMES,
                self::TASK_CTOR_OPTIONAL_OPTION_NAMES,
                ['taskid']
            );
        $required = self::taskConstructorOptions();
        $sessionid =
            Session::create([
                'userid' => $managerOptions['sessionUserid'],
                'lifetime' => $managerOptions['sessionLifetime']
            ])->sessionid();
        for ( $i = 1, $last = count(self::TASK_CTOR_OPTIONAL_OPTION_NAMES);
              $i <= $last;
              ++$i )
        {
            $combinations =
                new Combinations(self::TASK_CTOR_OPTIONAL_OPTION_NAMES, $i);
            foreach ($combinations->generator() as $names) {
                $options = $required;
                foreach ($names as $n) {
                    $v = $n == 'sessionid' ?
                        $sessionid :
                        self::TASK_CTOR_OPTION_VALUES[$n];
                    $options[$n] = $v;
                }
                if (isset($options['errorStatus']) != isset($options['errorMessage']))
                    continue;
                $task = null;
                try {
                    $task = $manager->constructTask($options);
                } catch (Error $e) {
                    throw new
                        Error([
                            'message' =>
                                'Failed constructing a task with options ' .
                                json_encode($options),
                            'inner' => $e
                        ]);
                }
                $encoded = json_encode($options);
                foreach ($allOptions as $n) {
                    if ($n == 'RecordID')
                        continue;
                    $p = self::TASK_CTOR_OPTION_PROPERTY_NAMES[$n] ?? $n;
                    Assert::equal(
                        $task->$p(),
                        $options[$n] ?? null,
                        "Invalid '$p' for task constructed with options " .
                            $encoded
                    );
                }
            }
        }
    }

    /**
     * Tests constructing a task with an options array that is
     * non-associative
     */
    public function testConstructTaskInvalidRowFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->constructTask([5, 4, 3, 2, 1]);
    }

    /**
     * Tests constructing a task with no "RecordID" option
     */
    public function testConstructTaskMissingRecordIdFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        unset($options['RecordID']);
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "RecordID" option
     */
    public function testConstructTaskInvalidRecordIdFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['RecordID'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with no "CreationDate" option
     */
    public function testConstructTaskMissingCreationDateFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        unset($options['CreationDate']);
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "CreationDate" option
     */
    public function testConstructTaskInvalidCreationDateFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['CreationDate'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with no "task" or "taskid" option
     */
    public function testConstructTaskMissingTaskidFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        unset($options['taskid']);
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "task" option
     */
    public function testConstructTaskInvalidTaskFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = ['taskid' => -1009];
        foreach (self::TASK_CTOR_REQUIRED_OPTION_NAMES as $n)
            $options[$n] = self::TASK_CTOR_OPTION_VALUES[$n];
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "data1" option
     */
    public function testConstructTaskInvalidData1Fail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['data1'] = -1009;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "data2" option
     */
    public function testConstructTaskInvalidData2Fail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['data2'] = false;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "data3" option
     */
    public function testConstructTaskInvalidData3Fail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['data3'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with no "expires" option
     */
    public function testConstructTaskMissingExpiresFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        unset($options['expires']);
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid "expires" option
     */
    public function testConstructTaskInvalidExpiresFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['expires'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with a non-integral "maxAttempts" option
     */
    public function testConstructTaskInvalidMaxAttemptsFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['maxAttempts'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with a negative "maxAttempts" option
     */
    public function testConstructTaskInvalidMaxAttemptsFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['maxAttempts'] = -1009;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with no "attempts" option
     */
    public function testConstructTaskMissingAttemptsFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        unset($options['attempts']);
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with a non-integral "attempts" option
     */
    public function testConstructTaskInvalidAttemptsFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['attempts'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with a negative "attempts" option
     */
    public function testConstructTaskInvalidAttemptsFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['attempts'] = -1009;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with no "status" option
     */
    public function testConstructTaskMissingStatusFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        unset($options['status']);
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with a "status" set to an invalid integral
     * value
     */
    public function testConstructTaskInvalidStatusFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['status'] = 3;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with a "status" set to an invalid integral
     * value
     */
    public function testConstructTaskInvalidStatusFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['status'] = -0.00001425;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid error status
     */
    public function testConstructTaskInvalidErrorStatusFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['errorStatus'] = 17;
        $options['errorMessage'] = 'No such file: desktop.ini';
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an invalid error message
     */
    public function testConstructTaskInvalidErrorMessageFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['errorStatus'] = 'UNSUPPORTED_OPERATION';
        $options['errorMessage'] = -1009;
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an error status but no error message
     */
    public function testConstructTaskInconsistentErrorOptionsFail1()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['errorStatus'] = 'UNSUPPORTED_OPERATION';
        $manager->constructTask($options);
    }

    /**
     * Tests constructing a task with an error message but no error status
     */
    public function testConstructTaskInconsistentErrorOptionsFail2()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $options = self::taskConstructorOptions();
        $options['errorMessage'] = 'No such file: desktop.ini';
        $manager->constructTask($options);
    }

    /**
     * Tests creating a task with all valid option combinations for a manager
     * constructed with default values for "parameters", "lifetime", and
     * "maxAttempts", excluding the options "takeOwnership" and
     * "replaceExisting"
     */
    public function testCreateTask1()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $optionNames = array_keys(self::CREATE_TASK_OPTIONS);
        for ($i = 1, $last = count($optionNames); $i <= $last; ++$i) {
            $combinations = new Combinations($optionNames, $i);
            foreach ($combinations->generator() as $names) {

                // Create task
                $taskOptions = [];
                foreach ($names as $n)
                    $taskOptions[$n] = self::CREATE_TASK_OPTIONS[$n];
                $task = null;
                try {
                    $task = $manager->createTask(self::TASKID, $taskOptions);
                } catch (Error $e) {
                    throw new
                        Error([
                            'message' =>
                                'Failed creating a task with options ' .
                                json_encode($taskOptions),
                            'inner' => $e
                        ]);
                }

                // Load task from database
                $queue = $this->loadQueue($manager);
                Assert::equal(count($queue), 1, 'Incorrect task count');
                $actual = $queue[0];

                // Compare actual and expected values
                $expected = $taskOptions +
                    [
                        'taskid' => self::TASKID,
                        'sessionid' => $manager->sessionid()
                    ];
                foreach (['parameters', 'maxAttempts'] as $n)
                    if (!isset($taskOptions[$n]))
                        $expected[$n] = $managerOptions[$n];
                $encodedOpts = json_encode($taskOptions);
                $encodedTask = json_encode($actual->encode());
                foreach ($optionNames as $n) {
                    if ($n == 'lifetime')
                        continue;
                    Assert::equal(
                        $actual->$n() ?? null,
                        $expected[$n] ?? null,
                        "Invalid '$n' for task $encodedTask created with " .
                            "options $encodedOpts"
                    );
                }
                $now = Time::get();
                foreach (self::TASK_TIMESTAMP_LABELS as $name => $label) {
                    $act = $actual->$name();
                    if ($name == 'completed') {
                        Assert::isNull($act, "Incorrect $name");
                    } else {
                        $exp = $now;
                        if ($name == 'expires')
                            $exp +=
                                $taskOptions['lifetime'] ??
                                $managerOptions['lifetime'];
                        Assert::almostEqual(
                            $act, $exp, self::TIMESTAMP_EPSILON,
                            "Incorrect $label for task $encodedTask created " .
                            "with options $encodedOpts"
                        );
                    }
                }
                $this->clearDatabase();
            }
        }
    }

    /**
     * Tests creating a task with for a manager constructed without default
     * values for "parameters", "lifetime", and "maxAttempts"
     */
    public function testCreateTask2()
    {
        // Create task
        $managerOptions = self::managerConstructorOptions();
        unset($managerOptions['parameters']);
        unset($managerOptions['lifetime']);
        unset($managerOptions['maxAttempts']);
        $manager = new Manager($managerOptions);
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $task = $manager->createTask(self::TASKID, $taskOptions);

        $queue = $this->loadQueue($manager);
        Assert::equal(count($queue), 1, 'Incorrect task count');
        $actual = $queue[0];

        // Compare actual and expected values
        $expected = $taskOptions + ['taskid' => self::TASKID];
        $encodedOpts = json_encode($taskOptions);
        $encodedTask = json_encode($actual->encode());
        foreach (array_keys(self::CREATE_TASK_OPTIONS) as $n) {
            if ($n == 'lifetime')
                continue;
            Assert::equal(
                $actual->$n() ?? null,
                $expected[$n] ?? null,
                "Invalid '$n' for task $encodedTask created with " .
                    "options $encodedOpts"
            );
        }
        $now = Time::get();
        foreach (self::TASK_TIMESTAMP_LABELS as $name => $label) {
            $act = $actual->$name();
            if ($name == 'completed') {
                Assert::isNull($act, "Incorrect $name");
            } else {
                $exp = $now;
                if ($name == 'expires')
                    $exp +=
                        $taskOptions['lifetime'] ??
                        $managerOptions['lifetime'];
                Assert::almostEqual(
                    $act, $exp, self::TIMESTAMP_EPSILON,
                    "Incorrect $label for task $encodedTask created with " .
                    "options $encodedOpts"
                );
            }
        }
    }

    /**
     * Tests creating tasks with the option "replaceExisting" set to true
     */
    public function testCreateTask3()
    {
        // Create manager
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;

        // Create initial task and clear session ID
        $manager->createTask(self::TASKID, $taskOptions);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, ['maintainOwnership' => false]);

        // Create second task with same task identifier but different "data1"
        // option, replacing the existing task, and clear session ID
        $taskOptions['replaceExisting'] = true;
        $taskOptions['data1'] .= $task1->data1() . '00000';
        $manager->createTask(self::TASKID, $taskOptions);
        $task2 = $this->lastTask($manager);
        $task2->update(Task::STATUS_PENDING, ['maintainOwnership' => false]);
        Assert::equal(
            $task2->data1(),
            $taskOptions['data1'],
            "Incorrect 'data1' property"
        );

        // Create a third task,  with same task identifier but different "data1"
        // option, replacing the second
        $taskOptions['data1'] .= $task2->data1() . '11111';
        $manager->createTask(self::TASKID, $taskOptions);
        $task3 = $this->lastTask($manager);
        Assert::equal(
            $task3->data1(),
            $taskOptions['data1'],
            "Incorrect 'data1' property"
        );
    }

    /**
     * Tests creating tasks with the option "takeOwnership" set to true
     */
    public function testCreateTask4()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['takeOwnership'] = true;
        $manager->createTask(self::TASKID, $taskOptions);
        $task = $this->lastTask($manager);
        Assert::equal(
            $task->sessionid(),
            $manager->sessionid(),
            "Incorrect 'sessionid' property"
        );
    }

    /**
     * Tests creating tasks with the option "takeOwnership" set to false
     */
    public function testCreateTask5()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['takeOwnership'] = false;
        $manager->createTask(self::TASKID, $taskOptions);
        $task = $this->lastTask($manager);
        Assert::isNull($task->sessionid(), "Incorrect 'sessionid' property");
    }

    /**
     * Tests constructing a task with an invalid "taskid" option
     */
    public function testCreateTaskInvalidTaskidFail()
    {
        $this->setExpectedException('Error');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask([], self::CREATE_TASK_OPTIONS);
    }

    /**
     * Tests constructing a task with an invalid "data1" option
     */
    public function testCreateTaskInvalidData1Fail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['data1'] = -1009;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with an invalid "data2" option
     */
    public function testCreateTaskInvalidData2Fail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['data2'] = false;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with an invalid "data3" option
     */
    public function testCreateTaskInvalidData3Fail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['data3'] = -0.00001425;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with missing runtime parameters and no default
     * runtime parameters
     */
    public function testCreateTaskMissingParametersFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $managerOptions['parameters'] = Task::NO_PARAMS;
        $manager = new Manager($managerOptions);
        $taskOptions = self::CREATE_TASK_OPTIONS;
        unset($taskOptions['parameters']);
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with an invalid "parameters" option
     */
    public function testCreateTaskInvalidParametersFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['parameters'] = ['quality' => 0.9];
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with no "lifetime" option and no default
     * lifetime
     */
    public function testCreateTaskMissingLifetimeFail()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        unset($managerOptions['lifetime']);
        $manager = new Manager($managerOptions);
        $taskOptions = self::CREATE_TASK_OPTIONS;
        unset($taskOptions['lifetime']);
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with an invalid "lifetime" option
     */
    public function testCreateTaskInvalidExpiresFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['lifetime'] = -0.00001425;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with a non-integral "maxAttempts" option
     */
    public function testCreateTaskInvalidMaxAttemptsFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['maxAttempts'] = -0.00001425;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with a negative "maxAttempts" option
     */
    public function testCreateTaskInvalidMaxAttemptsFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['maxAttempts'] = -1009;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with an invalid "takeOwnership" option
     */
    public function testCreateTaskInvalidTakeOwnership()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['takeOwnership'] = 1;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with an invalid "replaceExisting" option
     */
    public function testCreateTaskInvalidReplaceExistingFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['replaceExisting'] = 1;
        $manager->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests constructing a task with no "replaceExisting" option and an
     * existing task with the same task identifier
     */
    public function testCreateTaskInvalidReplaceExistingFail2()
    {
        $this->setExpectedStatusCode('OBJECT_EXISTS');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID, self::CREATE_TASK_OPTIONS);
        $this->lastTask($manager)->update(Task::STATUS_PENDING, [
            'maintainOwnership' => false
        ]);
        $manager->createTask(self::TASKID, self::CREATE_TASK_OPTIONS);
    }

    /**
     * Tests constructing a task with "replaceExisting" set to false and an
     * existing task with the same task identifier
     */
    public function testCreateTaskInvalidReplaceExistingFail3()
    {
        $this->setExpectedStatusCode('OBJECT_EXISTS');
        $manager = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['replaceExisting'] = false;
        $manager->createTask(self::TASKID, $taskOptions);
        $this->lastTask($manager)->update(Task::STATUS_PENDING, [
            'maintainOwnership' => false
        ]);
        $manager->createTask(self::TASKID, self::CREATE_TASK_OPTIONS);
    }

    /**
     * Tests constructing a task with "replaceExisting" set to true and an
     * existing task with the same task identifier owner by another queue
     * processing session
     */
    public function testCreateTaskInvalidReplaceExistingFail4()
    {
        $this->setExpectedStatusCode('OBJECT_EXISTS');

        // Create first task
        $manager1 = new Manager(self::managerConstructorOptions());
        $manager1->createTask(self::TASKID, self::CREATE_TASK_OPTIONS);

        // Attempt to create second task
        $manager2 = new Manager(self::managerConstructorOptions());
        $taskOptions = self::CREATE_TASK_OPTIONS;
        $taskOptions['replaceExisting'] = true;
        $manager2->createTask(self::TASKID, $taskOptions);
    }

    /**
     * Tests loadTasks() with random permutations of data items and matching
     * criteria
     */
    public function testLoadTasks()
    {
        // Create collections of data
        $set1 = ['cat', 'dog'];
        $set2 = ['male', 'female'];
        $set3 = ['white', 'black'];
        $byId = [];
        $count = 0;
        for ($w1 = 0, $w2 = count($set1); $w1 < $w2; ++$w1) {
            for ($x1 = 0, $x2 = count($set2); $x1 < $x2; ++$x1) {
                for ($y1 = 0, $y2 = count($set3); $y1 < $y2; ++$y1) {
                    $data1 = $set1[$w1] ?? null;
                    $data2 = $set2[$x1] ?? null;
                    $data3 = $set3[$y1] ?? null;
                    $taskid = "$data1#$data2#$data3";
                    $status = $count % 3;
                    $byId[$taskid] = [$data1, $data2, $data3, $status];
                    ++$count;
                }
            }
        }

        // Create combinations of matching criteria
        $combos1 = [null, ['cat'], ['dog'], ['cat', 'dog']];
        $combos2 = [null, ['male'], ['female'], ['male', 'female']];
        $combos3 = [null, ['white'], ['black'], ['white', 'black']];
        $combos4 = [null, [0], [0, 1], [0, 2], [0, 1, 2]];

        // Iterate over combinations
        for ($w1 = 0, $w2 = count($combos1); $w1 < $w2; ++$w1) {
            for ($x1 = 0, $x2 = count($combos2); $x1 < $x2; ++$x1) {
                for ($y1 = 0, $y2 = count($combos3); $y1 < $y2; ++$y1) {
                    for ($z1 = 0, $z2 = count($combos4); $z1 < $z2; ++$z1) {
                        if (random_int(1, 16) > 1)
                            continue;


                        // Create tasks
                        $managerOptions = self::managerConstructorOptions();
                        $manager = new Manager($managerOptions);
                        foreach ($byId as $id => [$d1, $d2, $d3, $s]) {
                            $manager->createTask($id, [
                                'data1' => $d1,
                                'data2' => $d2,
                                'data3' => $d3
                            ]);
                        }

                        // Set statuses and collect matching tasks
                        $queue = $this->loadQueue($manager);
                        $expected = [];
                        foreach ($queue as $t) {
                            $status = $byId[$t->taskid()][3];
                            if ($status != Task::STATUS_PENDING)
                                $t->update($status);
                            if ( ( $combos1[$w1] === null ||
                                   in_array($t->data1(), $combos1[$w1]) ) &&
                                 ( $combos2[$x1] === null ||
                                   in_array($t->data2(), $combos2[$x1]) ) &&
                                 ( $combos3[$y1] === null ||
                                   in_array($t->data3(), $combos3[$y1]) ) &&
                                 ( $combos4[$z1] === null ||
                                   in_array($status, $combos4[$z1]) ) )
                            {
                                $expected[] = $t;
                            }
                        }
                        $taskid = null;
                        if (count($expected) > 0 && random_int(1, 8) == 1) {
                            $taskid = $expected[0]->taskid();
                            $expected = array_slice($expected, 0, 1);
                        }

                        // Load tasks
                        $data1 = $w1 == 1 ? $combos1[$w1][0] : $combos1[$w1];
                        $data2 = $x1 == 1 ? $combos2[$x1][0] : $combos2[$x1];
                        $data3 = $y1 == 1 ? $combos3[$y1][0] : $combos3[$y1];
                        $status = $z1 == 1 ? $combos4[$z1][0] : $combos4[$z1];
                        $actual =
                            $manager->loadTasks([
                                'taskid' => $taskid,
                                'data1' => $data1,
                                'data2' => $data2,
                                'data3' => $data3,
                                'status' => $status
                            ]);

                        // Verify result
                        $order =
                            function($a, $b)
                            {
                                return $a->taskid() <=> $b->taskid();
                            };
                        usort($actual, $order);
                        usort($expected, $order);
                        Assert::equal(
                            $actual, $expected,
                            "Incorrect task list for combos " .
                                json_encode([
                                    $combos1[$w1],
                                    $combos2[$x1],
                                    $combos3[$y1],
                                    $combos4[$z1]
                                ])
                        );

                        $this->clearDatabase();
                    }
                }
            }
        }
    }

    /**
     * Tests loadTasks() with a in integral task ID
     */
    public function testLoadTasksInvalidTaskidFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['taskid' => -1009]);
    }

    /**
     * Tests loadTasks() with a "data1" option that is neither a string nor a
     * list of strings
     */
    public function testLoadTasksInvalidData1Fail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['data1' => ['color' => 'blue']]);
    }

    /**
     * Tests loadTasks() with a "data1" option that is an empty array
     */
    public function testLoadTasksInvalidData1Fail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['data1' => []]);
    }

    /**
     * Tests loadTasks() with a "data2" option that is neither a string nor a
     * list of strings
     */
    public function testLoadTasksInvalidData2Fail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['data2' => ['color' => 'blue']]);
    }

    /**
     * Tests loadTasks() with a "data2" option that is an empty array
     */
    public function testLoadTasksInvalidData2Fail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['data2' => []]);
    }

    /**
     * Tests loadTasks() with a "data3" option that is neither a string nor a
     * list of strings
     */
    public function testLoadTasksInvalidData3Fail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['data3' => ['color' => 'blue']]);
    }

    /**
     * Tests loadTasks() with a "data3" option that is an empty array
     */
    public function testLoadTasksInvalidData3Fail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['data3' => []]);
    }

    /**
     * Tests loadTasks() with a "status" option that is neither and integer nor
     * an array of integers
     */
    public function testLoadTasksInvalidStatusFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['status' => "PENDING"]);
    }

    /**
     * Tests loadTasks() with a "status" option that is an integer outside the
     * valid range
     */
    public function testLoadTasksInvalidStatusFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['status' => -1009]);
    }

    /**
     * Tests loadTasks() with a "status" option that is an array of integers
     * containing an integer outside the valid range
     */
    public function testLoadTasksInvalidStatusFail3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->loadTasks(['status' => [Task::STATUS_SUCCESS, -1009]]);
    }

    /**
     * Tests claimTasks() with random permutations of data items and matching
     * criteria
     */
    public function testClaimTasks1()
    {

        // Create collections of data
        $set1 = ['cat', 'dog'];
        $set2 = ['male', 'female'];
        $set3 = ['white', 'black'];
        $tasks = [];
        $count = 0;
        for ($x1 = 0, $x2 = count($set1); $x1 <= $x2; ++$x1) {
            for ($y1 = 0, $y2 = count($set2); $y1 <= $y2; ++$y1) {
                for ($z1 = 0, $z2 = count($set3); $z1 <= $z2; ++$z1) {
                    $data1 = $set1[$x1] ?? null;
                    $data2 = $set2[$y1] ?? null;
                    $data3 = $set3[$z1] ?? null;
                    $taskid = "$data1#$data2#$data3";
                    $tasks[$count % 2][$taskid] = [$data1, $data2, $data3];
                    ++$count;
                }
            }
        }

        // Create combinations of matching criteria
        $combos1 = [null, ['cat'], ['dog'], ['cat', 'dog']];
        $combos2 = [null, ['male'], ['female'], ['male', 'female']];
        $combos3 = [null, ['white'], ['black'], ['white', 'black']];

        // Iterate over combinations
        for ($x1 = 0, $x2 = count($combos1); $x1 < $x2; ++$x1) {
            for ($y1 = 0, $y2 = count($combos2); $y1 < $y2; ++$y1) {
                for ($z1 = 0, $z2 = count($combos3); $z1 < $z2; ++$z1) {
                    if (random_int(1, 6) > 1)
                        continue;

                    $combos =  // For use in assertions messages
                        json_encode([
                            $combos1[$x1],
                            $combos2[$y1],
                            $combos3[$z1]
                        ]);

                    // Create managers
                    $managerOptions = self::managerConstructorOptions();
                    $manager0 = new Manager($managerOptions);
                    $manager1 = new Manager($managerOptions);

                    // Create tasks
                    foreach ($tasks[0] as $id => [$d1, $d2, $d3]) {
                        $manager0->createTask($id, [
                            'data1' => $d1,
                            'data2' => $d2,
                            'data3' => $d3,
                            'takeOwnership' => true
                        ]);
                    }
                    foreach ($tasks[1] as $id => [$d1, $d2, $d3]) {
                        $manager1->createTask($id, [
                            'data1' => $d1,
                            'data2' => $d2,
                            'data3' => $d3,
                            'takeOwnership' => false
                        ]);
                    }

                    // Claim tasks
                    $data1 = $x1 == 1 ? $combos1[$x1][0] : $combos1[$x1];
                    $data2 = $y1 == 1 ? $combos2[$y1][0] : $combos2[$y1];
                    $data3 = $z1 == 1 ? $combos3[$z1][0] : $combos3[$z1];
                    $manager1->claimTasks([
                        'data1' => $data1,
                        'data2' => $data2,
                        'data3' => $data3
                    ]);

                    // Verify queue
                    $queue = $this->loadQueue($manager0); // Either manager ok
                    Assert::equal(
                        count($queue),
                        count($tasks[0]) + count($tasks[1]),
                        "Incorrect queue length for combinations $combos"
                    );
                    foreach ($queue as $task) {
                        $taskid = $task->taskid();
                        if (isset($tasks[0][$taskid])) {
                            Assert::equal(
                                $task->sessionid(),
                                $manager0->sessionid(),
                                "Incorrect session ID for task $taskid and " .
                                    "combinations $combos"
                            );
                        } else {
                            [$d1, $d2, $d3] = $tasks[1][$taskid];
                            $claimed =
                                ( $combos1[$x1] === null ||
                                  in_array($d1, $combos1[$x1]) ) &&
                                ( $combos2[$y1] === null ||
                                  in_array($d2, $combos2[$y1]) ) &&
                                ( $combos3[$z1] === null ||
                                  in_array($d3, $combos3[$z1]) );
                            Assert::equal(
                                $task->sessionid() === $manager1->sessionid(),
                                $claimed,
                                "Incorrect claimed status for $taskid and " .
                                    "combinations $combos"
                            );
                        }
                    }

                    $this->clearDatabase();
                }
            }
        }
    }

    /**
     * Tests claimTasks() with some tasks that have different parameters,
     * are expired, or have already been attempted the maximum number of times
     */
    public function testClaimTasks2()
    {
        $params1 = '{"color":"blue"}';
        $params2 = '{"color":"green"}';
        $this->executeCallbacks([
            '1970-01-01 00:00:00' => // Create tasks and attempt each one thrice
                function() use($params1, $params2)
                {
                    $options = self::managerConstructorOptions();
                    $options['sessionLifetime'] = 3600;
                    $manager = new Manager($options);
                    for ($i = 0; $i < 12; ++$i) {
                        $rem = $i % 4;
                        $params = $rem != 0 ? $params1 : $params2;
                        $lifetime = $rem != 1 ? 3 * 3600 : 3600;
                        $maxAttempts = $rem != 2 ? null : 3;
                        $manager->createTask("task-$i", [
                            'parameters' => $params,
                            'lifetime' => $lifetime,
                            'maxAttempts' => $maxAttempts,
                        ]);
                    }
                    foreach ($this->loadQueue($manager) as $task)
                        foreach ([1, 2, 3] as $i)
                            $task->update(Task::STATUS_PENDING);
                },
            '1970-01-01 02:00:00' => // Claim tasks
                function() use($params1)
                {
                    $options = self::managerConstructorOptions();
                    $options['parameters'] = $params1;
                    $manager = new Manager($options);
                    $manager->claimTasks();
                    foreach ($this->loadQueue($manager) as $i => $task) {
                        Assert::equal(
                            $task->sessionid() == $manager->sessionid(),
                            $i % 4 == 3,
                            'Incorrect claimed status for task ' .
                                $task->taskid()
                        );
                    }
                }
        ]);
    }

    /**
     * Tests claimTasks() with the "maxTasks" options
     */
    public function testClaimTasks3()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        for ($i = 0; $i < 20; ++$i) {
            $taskOptions['data1'] = (string) ($i % 2);
            $taskOptions['parameters'] = $managerOptions['parameters'];
            $taskOptions['takeOwnership'] = false;
            $manager->createTask("task$i", $taskOptions);
        }
        $maxTasks = 5;
        $manager->claimTasks(['data1' => '1', 'maxTasks' => $maxTasks]);
        $queue = $this->loadQueue($manager);
        $count = 0;
        foreach ($queue as $task) {
            $claimed = $task->sessionid() === $manager->sessionid();
            Assert::equal(
                $claimed,
                $task->data1() == '1' && $count < $maxTasks,
                'Incorrect claimed status at for task ' . $task->taskid()
            );
            $count += (int) $claimed;
        }
    }

    /**
     * Tests claimTasks() with a "data1" option that is neither a string nor a
     * list of strings
     */
    public function testClaimTasksInvalidData1Fail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['data1' => ['color' => 'blue']]);
    }

    /**
     * Tests claimTasks() with a "data1" option that is an empty array
     */
    public function testClaimTasksInvalidData1Fail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['data1' => []]);
    }

    /**
     * Tests claimTasks() with a "data2" option that is neither a string nor a
     * list of strings
     */
    public function testClaimTasksInvalidData2Fail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['data2' => ['color' => 'blue']]);
    }

    /**
     * Tests claimTasks() with a "data2" option that is an empty array
     */
    public function testClaimTasksInvalidData2Fail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['data2' => []]);
    }

    /**
     * Tests claimTasks() with a "data3" option that is neither a string nor a
     * list of strings
     */
    public function testClaimTasksInvalidData3Fail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['data3' => ['color' => 'blue']]);
    }

    /**
     * Tests claimTasks() with a "data3" option that is an empty array
     */
    public function testClaimTasksInvalidData3Fail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['data3' => []]);
    }

    /**
     * Tests claimTasks() with a non-integral "maxTasks" option
     */
    public function testClaimTasksInvalidMaxTasksFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['maxTasks' => -0.00001425]);
    }

    /**
     * Tests claimTasks() with negative "maxTasks" option
     */
    public function testClaimTasksInvalidMaxTasksFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);
        $manager->claimTasks(['maxTasks' => -1009]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_SUCCESS and the
     * default value for the option "maintainOwnership"
     */
    public function testUpdateTask1()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_SUCCESS);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_SUCCESS, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::almostEqual(
            $task1->completed(), Time::get(), self::TIMESTAMP_EPSILON,
            'Incorrect completion date'
        );
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_SUCCESS and
     * "maintainOwnership" set to true
     */
    public function testUpdateTask2()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_SUCCESS, ['maintainOwnership' => true]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_SUCCESS, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::almostEqual(
            $task1->completed(), Time::get(), self::TIMESTAMP_EPSILON,
            'Incorrect completion date'
        );
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_SUCCESS and
     * "maintainOwnership" set to false
     */
    public function testUpdateTask3()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_SUCCESS, ['maintainOwnership' => false]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_SUCCESS, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::almostEqual(
            $task1->completed(), Time::get(), self::TIMESTAMP_EPSILON,
            'Incorrect completion date'
        );
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_PENDING and the
     * default value for the option "maintainOwnership"
     */
    public function testUpdateTask4()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_PENDING, 'Incorrect status');
        Assert::equal($task1->sessionid(), $manager->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::isNull($task1->completed(), 'Incorrect completion date');
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_PENDING and
     * "maintainOwnership" set to true
     */
    public function testUpdateTask5()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, ['maintainOwnership' => true]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_PENDING, 'Incorrect status');
        Assert::equal($task1->sessionid(), $manager->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::isNull($task1->completed(), 'Incorrect completion date');
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_PENDING and
     * "maintainOwnership" set to false
     */
    public function testUpdateTask6()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, ['maintainOwnership' => false]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_PENDING, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::isNull($task1->completed(), 'Incorrect completion date');
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_FAILURE andthe
     * default value for the option "maintainOwnership"
     */
    public function testUpdateTask7()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_FAILURE);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_FAILURE, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::almostEqual(
            $task1->completed(), Time::get(), self::TIMESTAMP_EPSILON,
            'Incorrect completion date'
        );
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_FAILURE and
     * "maintainOwnership" set to true
     */
    public function testUpdateTask8()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_FAILURE, ['maintainOwnership' => true]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_FAILURE, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::almostEqual(
            $task1->completed(), Time::get(), self::TIMESTAMP_EPSILON,
            'Incorrect completion date'
        );
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_FAILURE and
     * "maintainOwnership" set to false
     */
    public function testUpdateTask9()
    {
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_FAILURE, ['maintainOwnership' => false]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_FAILURE, 'Incorrect status');
        Assert::isNull($task1->sessionid(), 'Incorrect session ID');
        Assert::isNull($task1->errorStatus(), 'Incorrect error status');
        Assert::isNull($task1->errorMessage(), 'Incorrect error message');
        Assert::almostEqual(
            $task1->completed(), Time::get(), self::TIMESTAMP_EPSILON,
            'Incorrect completion date'
        );
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_PENDING and
     * an error status and message
     */
    public function testUpdateTask10()
    {
        $errorStatus = 'THIRD_PARTY_SERVICE_UNAVAILABLE';
        $errorMessage = "Can't connect to Carnegie Deli";
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'errorStatus' => $errorStatus,
            'errorMessage' => $errorMessage
        ]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_PENDING, 'Incorrect status');
        Assert::equal($task1->sessionid(), $manager->sessionid(), 'Incorrect session ID');
        Assert::equal($task1->errorStatus(), $errorStatus, 'Incorrect error status');
        Assert::equal($task1->errorMessage(), $errorMessage, 'Incorrect error message');
        Assert::isNull($task1->completed(), 'Incorrect completion date');
    }

    /**
     * Tests CodeRage\Queue\Task::update() with status STATUS_PENDING and
     * an exception object
     */
    public function testUpdateTask11()
    {
        $errorStatus = 'THIRD_PARTY_SERVICE_UNAVAILABLE';
        $errorMessage = "Can't connect to Carnegie Deli";
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'error' =>
                new Error([
                        'status' => $errorStatus,
                        'details' => $errorMessage
                    ])
        ]);
        $task2 = $this->lastTask($manager);
        foreach (
            [ 'status', 'sessionid', 'completed',
              'errorStatus', 'errorMessage' ]
            as $name )
        {
            Assert::equal(
                $task2->$name(),
                $task1->$name(),
                "Task '$name' properties differ"
            );
        }
        Assert::equal($task1->status(), Task::STATUS_PENDING, 'Incorrect status');
        Assert::equal($task1->sessionid(), $manager->sessionid(), 'Incorrect session ID');
        Assert::equal($task1->errorStatus(), $errorStatus, 'Incorrect error status');
        Assert::equal($task1->errorMessage(), $errorMessage, 'Incorrect error message');
        Assert::isNull($task1->completed(), 'Incorrect completion date');
    }

    /**
     * Tests logging critical erros when tasks fail permantently
     */
    public function testUpdateTask12()
    {
        $log = Log::current();
        $provider = new \CodeRage\Log\Provider\Queue;
        $log->registerProvider($provider, Log::CRITICAL);
        try {
            $this->executeCallbacks([
                '1970-01-01 00:00:00' =>
                    function()  // Create tasks and attempt each one thrice
                    {
                        $options = self::managerConstructorOptions();
                        $options['sessionLifetime'] = 3600;
                        $manager = new Manager($options);
                        for ($i = 0; $i < 9; ++$i) {
                            $rem = $i % 3;
                            $lifetime = $rem != 0 ? 3 * 3600 : 3600;
                            $maxAttempts = $rem != 1 ? null : 3;
                            $manager->createTask("task-$i", [
                                'lifetime' => $lifetime,
                                'maxAttempts' => $maxAttempts,
                            ]);
                        }
                        foreach ($this->loadQueue($manager) as $task)
                            foreach ([1, 2, 3] as $i)
                                $task->update(Task::STATUS_PENDING);
                    },
                '1970-01-01 02:00:00' => // Claim tasks and mark them failed
                    function() use($provider)
                    {
                        $manager = new Manager(self::managerConstructorOptions());
                        Assert::equal(
                            count($provider->entries()), 6,
                            'Incorrect number of log entries'
                        );
                        $manager->claimTasks();
                        foreach ($this->loadQueue($manager) as $i => $task)
                            if ($task->sessionid() === $manager->sessionid())
                                $task->update(Task::STATUS_FAILURE);
                        Assert::equal(
                            count($provider->entries()), 9,
                            'Incorrect number of log entries'
                        );
                    }
            ]);
        } finally {
            $log->unregisterProvider($provider);
        }
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an invalid "maintainOwernship"
     * option
     */
    public function testUpdateTaskInvalidMaintainOwnershipFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'maintainOwnership' => 0
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an invalid error status
     */
    public function testUpdateTaskInvalidErrorStatusFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'errorStatus' => 5,
            'errorMessage' => 'An error occurred'
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an invalid error message
     */
    public function testUpdateTaskInvalidErrorMessageFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'errorStatus' => 'THIRD_PARTY_SERVICE_UNAVAILABLE',
            'errorMessage' => -1009
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an invalid exception object
     */
    public function testUpdateTaskInvalidErrorFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'error' => new \Exception("Can't connect to the Carnegie Deli")
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an status STATUS_SUCCESS and
     * an error message
     */
    public function testUpdateTaskInconsistentStatusAndErrorStatusFail()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_SUCCESS, [
            'errorStatus' => 'THIRD_PARTY_SERVICE_UNAVAILABLE',
            'errorMessage' => "Can't connect to the Carnegie Deli"
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an status STATUS_SUCCESS and
     * an exception object
     */
    public function testUpdateTaskInconsistentStatusAndErrorFail()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_SUCCESS, [
            'error' =>
                new Error([
                        'status' => 'THIRD_PARTY_SERVICE_UNAVAILABLE',
                        'details' => "Can't connect to the Carnegie Deli"
                    ])
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with both an error status and an
     * exception object
     */
    public function testUpdateTaskInconsistentErrorStatusAndErrorFail()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'errorStatus' => 'THIRD_PARTY_SERVICE_UNAVAILABLE',
            'error' =>
                new Error([
                        'status' => 'THIRD_PARTY_SERVICE_UNAVAILABLE',
                        'details' => "Can't connect to the Carnegie Deli"
                    ])
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an error status but no error
     * message
     */
    public function testUpdateTaskInconsistentErrorStatusAndErrorMessageFail1()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'errorStatus' => 'THIRD_PARTY_SERVICE_UNAVAILABLE'
        ]);
    }

    /**
     * Tests CodeRage\Queue\Task::update() with an error message but no error
     * status
     */
    public function testUpdateTaskInconsistentErrorStatusAndErrorMessageFail2()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $task1 = $this->lastTask($manager);
        $task1->update(Task::STATUS_PENDING, [
            'errorMessage' => "Can't connect to the Carnegie Deli"
        ]);
    }

    /**
     * Tests processTasks() with an action that verifies task identifiers, data
     * items, parameters, session ID, and status
     */
    public function testProcessTasks1()
    {
        $params1 = '{"color":"blue"}';
        $params2 = '{"color":"green"}';
        $managerOptions = self::managerConstructorOptions();
        $managerOptions['parameters'] = $params1;
        $manager = new Manager($managerOptions);

        // Create tasks and claim the subset with matching parameters
        for ($i = 0; $i < 100; ++$i) {
            $params = random_int(1, 5) == 0 ? $params2 : $params1;
            $data = [];
            foreach ([1, 2, 3] as $j)
                $data[$j] = random_int(1, 2) == 1 ?
                    null :
                    Random::string(20);
            $manager->createTask((string) $i, [
                'data1' => $data[1],
                'data2' => $data[2],
                'data3' => $data[3],
                'takeOwnership' => false
            ]);
        }
        $manager->claimTasks();

        // Process tasks
        $tasks = [];
        $hasRow = null;
        $result =
            $manager->processTasks([
                'action' =>
                    function($task, $arg2) use(&$tasks, &$hasRow)
                    {
                        $tasks[] = $task;
                        if ($arg2 !== null && $foundRow === null)
                            $hasRow = $tasks;
                        return (int) $task->taskid() % 2 == 0;
                    }
            ]);

        // Verify outcome
        Assert::equal($result['total'], 100, 'Incorrect total count');
        Assert::equal($result['success'], 50, 'Incorrect success count');
        $queue =
            array_filter(
                $this->loadQueue($manager),
                function ($t) use($params1) { return $t->parameters() == $params1; }
            );
        Assert::equal(count($tasks), count($queue), 'Incorrect task count');
        if ($hasRow !== null)
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "Second action argument non-null for task $hasRow"
                ]);
        foreach ($queue as $i => $expected) {
            $actual = $tasks[$i];
            Assert::equal(
                $actual->status(),
                $i % 2 == 0 ? Task::STATUS_SUCCESS : Task::STATUS_PENDING,
                "Incorrect status for task $i"
            );
            foreach (['data1', 'data2', 'data3', 'parameters'] as $name) {
                Assert::equal(
                    $actual->$name(),
                    $expected->$name(),
                    "Incorrect $name for task $i"
                );
                Assert::equal(
                    $i % 2 == 0 ? null : $manager->sessionid(),
                    $actual->sessionid(),
                    "Incorrect session ID for task $i"
                );
            }
        }
    }

    /**
     * Tests processTasks() with some failed tasks and the default value for
     * "maintainOwnership"
     */
    public function testProcessTasks2()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);

        // Create and process tasks
        for ($i = 0; $i < 10; ++$i)
            $manager->createTask((string) $i);
        $manager->processTasks([
            'action' =>
                function($task, $arg2) use(&$tasks)
                {
                    return (int) $task->taskid() % 2 == 0;
                }
        ]);

        // Verify results
        foreach ($this->loadQueue($manager) as $i => $task) {
            Assert::equal(
                $task->sessionid() !== null,
                $i % 2 == 1,
                "Incorrect session ID for task $i"
            );
        }
    }

    /**
     * Tests processTasks() with some failed tasks and "maintainOwnership" set
     * to true
     */
    public function testProcessTasks3()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);

        // Create and process tasks
        for ($i = 0; $i < 10; ++$i)
            $manager->createTask((string) $i);
        $manager->processTasks([
            'action' =>
                function($task, $arg2) use(&$tasks)
                {
                    return (int) $task->taskid() % 2 == 0;
                },
            'maintainOwnership' => true
        ]);

        // Verify results
        foreach ($this->loadQueue($manager) as $i => $task) {
            Assert::equal(
                $task->sessionid() !== null,
                $i % 2 == 1,
                "Incorrect session ID for task $i"
            );
        }
    }

    /**
     * Tests processTasks() with some failed tasks and "maintainOwnership" set
     * to false
     */
    public function testProcessTasks4()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager1 = new Manager($managerOptions);
        $manager2 = new Manager($managerOptions);

        // Create tasks using one manager and process them with another
        for ($i = 0; $i < 20; ++$i)
            $manager1->createTask((string) $i, [
                'takeOwnership' => $i >= 10
            ]);
        $manager2->claimTasks();
        $manager2->processTasks([
            'action' =>
                function($task, $arg2) use(&$tasks)
                {
                    return (int) $task->taskid() % 2 == 0;
                },
            'maintainOwnership' => false
        ]);

        // Verify results
        foreach ($this->loadQueue($manager1) as $i => $task) {
            Assert::equal(
                $task->sessionid() !== null,
                $i >= 10,
                "Incorrect session ID for task $i"
            );
        }
    }

    /**
     * Tests processTasks() with delete set to "true"
     */
    public function testProcessTasks5()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);

        // Create and process tasks
        for ($i = 0; $i < 10; ++$i)
            $manager->createTask((string) $i);
        $manager->processTasks([
            'action' =>
                function($task, $arg2) use(&$tasks)
                {
                    return (int) $task->taskid() % 2 == 0;
                },
            'delete' => true
        ]);

        // Verify results
        Assert::equal(
            0, count($this->loadQueue($manager)),
            'Incorrect queue size'
        );
    }

    /**
     * Tests processTasks() with delete set to "false"
     */
    public function testProcessTasks6()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);

        // Create and process tasks
        for ($i = 0; $i < 10; ++$i)
            $manager->createTask((string) $i);
        $manager->processTasks([
            'action' =>
                function($task, $arg2) use(&$tasks)
                {
                    return (int) $task->taskid() % 2 == 0;
                },
            'delete' => false
        ]);

        // Verify results
        Assert::equal(
            10, count($this->loadQueue($manager)),
            'Incorrect queue size'
        );
    }

    /**
     * Tests processTasks() with delete set to "false"
     */
    public function testProcessTasks7()
    {
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager($managerOptions);

        // Create and process tasks
        for ($i = 0; $i < 10; ++$i)
            $manager->createTask((string) $i);
        $manager->processTasks([
            'action' =>
                function($task, $arg2) use(&$tasks)
                {
                    return (int) $task->taskid() % 2 == 0;
                },
            'delete' => false
        ]);

        // Verify results
        Assert::equal(
            10, count($this->loadQueue($manager)),
            'Incorrect queue size'
        );
    }

    /**
     * Tests processTasks() with various values for "touchPeriod"
     */
    public function testProcessTasks8()
    {
        // Create tasks
        $manager = new Manager(self::managerConstructorOptions());
        for ($i = 1; $i <= 12; ++$i)
            $manager->createTask("task-$i", ['takeOwnership' => false]);

        // Register hook to count queries
        $db = Db::nonNestableInstance();
        $totalQueries = 0;
        $hook =
            $db->registerHook(['postQuery' => function() use(&$totalQueries) {
                ++$totalQueries;
            }]);

        // Process tasks once for each divisor of 12
        $divisors = [1, 2, 3, 4, 6, 12];
        $queryCounts = [];
        try {
            foreach ($divisors as $div) {
                $baseline = $totalQueries;
                $manager->claimTasks();
                $manager->processTasks([
                    'action' => function() { return false; },
                    'maintainOwnership' => false,
                    'touchPeriod' => $div
                ]);
                $queryCounts[] = $totalQueries - $baseline;
            }
        } finally {
            $db->unregisterHook($hook);
        }

        // Verify results
        for ($i = 1, $n = count($divisors); $i <= $n - 1; ++$i) {
            $actual = $queryCounts[$i - 1] - $queryCounts[$i];
            $expected = (12 / $divisors[$i - 1]) - (12 / $divisors[$i]);
            Assert::equal(
                $actual, $expected,
                "Session touch decrease at position $i"
            );
        }
    }

    /**
     * Tests processTasks() with a custom, query result for a queue that stores
     * data in an auxiliary table
     */
    public function testProcessTasks9()
    {
        // Create tasks
        $managerOptions = self::managerConstructorOptions();
        $manager = new Manager(self::managerConstructorOptions());
        $queue = $managerOptions['queue'];

        // Create tasks, taking ownership of the first half
        $db = new Db;
        $data = [];
        for ($i = 0; $i < 10; ++$i) {
            $taskid = (string) $i;
            $data1 = Random::string(10);
            $data2 = Random::string(10);
            $data3 = Random::string(10);
            $data[$taskid] = [$data1, $data2, $data3];
            $manager->createTask($taskid, [
                'takeOwnership' => ($i % 2) == 0
            ]);
            $sql =
                "INSERT INTO CodeRageQueueTestAuxiliarylData
                 (task, aaa, bbb, ccc)
                 SELECT RecordID, %s, %s, %s
                 FROM [$queue]
                 WHERE taskid = %s";
            $db->query($sql, $data1, $data2, $data3, $taskid);
        }

        // Process tasks
        $sql =
            "SELECT q.*, d.aaa, d.bbb, d.ccc
             FROM [$queue] q
             JOIN CodeRageQueueTestAuxiliarylData d
               ON d.task = q.RecordID
             WHERE sessionid = %s";
        $queryResult = $db->query($sql, $manager->sessionid());
        $actualData = [];
        $manager->processTasks([
            'action' =>
                function($task, $row) use(&$actualData)
                {
                    Args::check($row, 'map', 'row of query results');
                    $data1 = $row['aaa'] ?? null;
                    $data2 = $row['bbb'] ?? null;
                    $data3 = $row['ccc'] ?? null;
                    $actualData[$task->taskid()] = [$data1, $data2, $data3];
                },
            'queryResult' => $queryResult
        ]);

        // Verify results
        $expectedData = [];
        foreach ($data as $i => $d)
            if ($i % 2 == 0)
                $expectedData[$i] = $d;
        Assert::equal(
            $actualData,
            $expectedData,
            'Invalid data for processed tasks'
        );
    }



    /**
     * Executes the specified callback for each task in the queue owned by
     * the current queue processing session
     *
     * @param array $options The options array; supports the following options:
     *     action - A callable tasking a single task argument, to be invoked for
     *       each task; failure can be indicated by returning false or by
     *       throwing an exception
     *     maintainOwnership - true if the current queue processing session
     *       should keep ownership of the job (optional)
     *     delete - true to delete tasks after processing, instead of updating
     *       their status; defaults to false
     *     touchPeriod - Integer specifying how open to update the expiration
     *       of the current queue processing session (optional)
     *     queryResult - A query result, represented as an instance of
     *       CodeRgae\Db\Results, so be used as a source of rows of data from
     *       which to construct task objects, instead of using a standard query
     *       (optional). If this optin is supplied, the "action" callable
     *       will be invoked with the task as its first argument and
     *       an associative array of query results as its second argument.
     * @return array An associative array with the following keys:
     *     total - The number of tasks processed
     *     succeess  The number of tasks processed successfully
     */

    /**
     * Tests processTasks() without an action
     */
    public function testProcessTasksMissingActionFail()
    {
         $this->setExpectedStatusCode('MISSING_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([]);
    }

    /**
     * Tests processTasks() with an action isn't callable
     */
    public function testProcessTasksInvalidActionFail()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks(['action' => ['Hello', 'World']]);
    }

    /**
     * Tests processTasks() with an invalid "maintainOwnership" option
     */
    public function testProcessTasksInvalidMaintainOwernshipFail1()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'maintainOwnership' => 0
         ]);
    }

    /**
     * Tests processTasks() with an invalid "maintainOwnership" option
     */
    public function testProcessTasksInvalidMaintainOwernshipFail2()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'maintainOwnership' => "false"
         ]);
    }

    /**
     * Tests processTasks() with an invalid "delete" option
     */
    public function testProcessTasksInvalidDeleteFail1()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'delete' => 0
         ]);
    }

    /**
     * Tests processTasks() with an invalid "delete" option
     */
    public function testProcessTasksInvalidDeleteFail2()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'delete' => "false"
         ]);
    }

    /**
     * Tests processTasks() with a non-integral "touchPeriod" option
     */
    public function testProcessTasksInvalidTouchPeriodFail1()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'touchPeriod' => 0.00021637
         ]);
    }

    /**
     * Tests processTasks() with a negative "touchPeriod" option
     */
    public function testProcessTasksInvalidTouchPeriodFail2()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'touchPeriod' => -9
         ]);
    }

    /**
     * Tests processTasks() with an invalid query result
     */
    public function testProcessTasksInvalidQueryResultFail1()
    {
         $this->setExpectedStatusCode('INVALID_PARAMETER');
         $manager = new Manager(self::managerConstructorOptions());
         $manager->processTasks([
             'action' => function($t) { },
             'queryResult' => []
         ]);
    }

    /**
     * Tests processTasks() with an query result for a query not suitable for
     * creating tasks
     */
    public function testProcessTasksInvalidQueryResultFail2()
    {
        $this->setExpectedException(function($e) {
             $status = Error::wrap($e)->status();
             return $status == 'MISSING_PARAMETER' ||
                    $status == 'INVALID_PARAMETER' ||
                    $status == 'INCONSISTENT_PARAMETERS';
        });
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID);
        $manager->processTasks([
            'action' => function($t) { },
            'queryResult' => (new Db)->query('SELECT * from AccessSession')
        ]);
    }

    /**
     * Tests processTasks() with an query result containing tasks not owned by
     * the manager
     */
    public function testProcessTasksInvalidQueryResultFail3()
    {
        $this->setExpectedStatusCode('STATE_ERROR');
        $manager = new Manager(self::managerConstructorOptions());
        $manager->createTask(self::TASKID, ['takeOwnership' => false]);
        $sql =
            'SELECT *
             FROM CodeRageQueueTestBasicQueue';
        $queryResult = (new Db)->query($sql);
        $manager->processTasks([
            'action' => function($task) { },
            'queryResult' => $queryResult
        ]);
    }

    /**
     * Tests clearSessions() in a scenarios with no tasks
     */
    public function testClearSessions1()
    {
        $sessionids = [];
        $this->executeCallbacks([
            '1970-01-01 00:00:00' => // Create sessions
                function() use(&$sessionids)
                {
                    for ($i = 0; $i < 10; ++$i) {
                        $session =
                            Session::create([
                                'userid' => User::ROOT,
                                'lifetime' => $i % 2 == 0 ? 3600 : 3 * 3600
                            ]);
                        $sessionids[] = $session->sessionid();
                    }
                },
            '1970-01-01 02:00:00' => // Clear sessions and verify results
                function() use($sessionids)
                {
                    new Manager(self::managerConstructorOptions());
                    foreach ($sessionids as $i => $sessionid) {
                        $cleared = null;
                        try {
                            Session::load(['sessionid' => $sessionid]);
                            $cleared = false;
                        } catch (Error $e) {
                            if ($e->status() !== 'OBJECT_DOES_NOT_EXIST')
                                throw $e;
                            $cleared = true;
                        }
                        Assert::equal(
                            $exists, $i % 2 == 1,
                            "Incorrect session cleared status at position $i"
                        );
                    }
                }
        ]);
    }

    /**
     * Tests clearSessions() in a scenarios with tasks having various number of
     * attempts
     */
    public function testClearSessions2()
    {
        $managerOptions = self::managerConstructorOptions();
        $managerOptions['axAttempts'] = 100;

        // Create 10 tasks and attempt each a different number of times
        $manager1 = new Manager($managerOptions);
        for ($i = 0; $i < 10; ++$i)
            $manager1->createTask("task-$i");
        $queue = $this->loadQueue($manager1);
        for ($i = 0; $i < 10; ++$i)
            for ($j = 0; $j < $i; ++$j)
                $queue[$i]->update(Task::STATUS_PENDING);

        // Verify twice: once with existing task objects, and once with newly
        // loaded ones
        for ($i = 0; $i < 10; ++$i) {
            $task = $queue[$i];
            Assert::equal(
                $task->attempts(), $i,
                'Inccorrect attempts for task ' . $task->taskid()
            );
        }
        $queue = $this->loadQueue($manager1);
        for ($i = 0; $i < 10; ++$i) {
            $task = $queue[$i];
            Assert::equal(
                $task->attempts(), $i,
                'Inccorrect attempts for task ' . $task->taskid()
            );
        }

        // Delete session and clear session IDs by creating a second manager
        Session::load(['sessionid' => $manager1->sessionid()])->delete();
        $manager2 = new Manager($managerOptions);

        // Verify result
        $queue = $this->loadQueue($manager2);
        for ($i = 0; $i < 10; ++$i) {
            $task = $queue[$i];
            Assert::equal(
                $task->attempts(), $i + 1,
                'Inccorrect attempts for task ' . $task->taskid()
            );
        }
    }

    /**
     * Tests markTasksFailed() with some tasks that are expired and some that
     * have already been attempted the maximum number of times
     */
    public function testMarkTasksFailed()
    {
        $this->executeCallbacks([
            '1970-01-01 00:00:00' =>
                function()
                {
                    // Create tasks with various lifetimes and maxAttempts
                    $options = self::managerConstructorOptions();
                    $options['sessionLifetime'] = 3600;
                    $manager = new Manager($options);
                    for ($i = 0; $i < 9; ++$i) {
                        $rem = $i % 3;
                        $lifetime = $rem != 0 ? 24 * 3600 : 3600;
                        $maxAttempts = $rem != 1 ? null : 3;
                        $manager->createTask("task-$i", [
                            'lifetime' => $lifetime,
                            'maxAttempts' => $maxAttempts,
                        ]);
                    }
                },
            '1970-01-01 02:00:00' =>
                function()
                {
                    // Clear sessions, causing every third task to expire,
                    // and attempt the remaining tasks thrice
                    $options = self::managerConstructorOptions();
                    $manager = new Manager($options);
                    foreach ($this->loadQueue($manager) as $i => $task) {
                        $status = $task->status();
                        Assert::equal(
                            $status,
                            $i % 3 == 0 ?
                                Task::STATUS_FAILURE :
                                Task::STATUS_PENDING,
                            'Incorrect status for task ' . $task->taskid()
                        );
                        if ($status == Task::STATUS_PENDING)
                            foreach ([1, 2, 3] as $i)
                                $task->update(Task::STATUS_PENDING);
                    }
                },
            '1970-01-01 03:00:00' =>
                function()
                {
                    // Clear sessions, causing tasks with maxAttempts 3 to fail
                    $options = self::managerConstructorOptions();
                    $manager = new Manager($options);

                    // Verify results
                    foreach ($this->loadQueue($manager) as $i => $task) {
                        $status = $task->status();
                        Assert::equal(
                            $status,
                            $i % 3 != 2 ?
                                Task::STATUS_FAILURE :
                                Task::STATUS_PENDING,
                            'Incorrect status for task ' . $task->taskid()
                        );
                    }
                }
        ]);
    }

    /**
     * Tests CodeRage\Queue\Pruner in "list" mode
     */
    public function testPrunerList()
    {
        $p = 'CodeRageQueueTestPruner';  // Table prefix
        $cases =
            [
                "{$p}*o?a*:1" =>
                    [
                        "{$p}NorthDakota" => 1,   "{$p}SouthDakota" => 1
                    ],
                "{$p}*o???a*:1" =>
                    [
                        "{$p}NorthCarolina" => 1, "{$p}SouthCarolina" => 1
                    ],
                "{$p}New*:1" =>
                    [
                        "{$p}NewHampshire" => 1,  "{$p}NewJersey" => 1,
                        "{$p}NewYork" => 1
                    ],
                "{$p}South*:1" =>
                    [
                        "{$p}SouthCarolina" => 1, "{$p}SouthDakota" => 1
                    ],
                "{$p}*Carolina:1" =>
                    [
                        "{$p}NorthCarolina" => 1, "{$p}SouthCarolina" => 1
                    ],
                "{$p}*o*o*:1" =>
                    [
                        "{$p}NorthCarolina" => 1, "{$p}NorthDakota" => 1,
                        "{$p}SouthCarolina" => 1, "{$p}SouthDakota" => 1
                    ],
                "{$p}North*:1,{$p}*Dakota:2" =>
                    [
                        "{$p}NorthCarolina" => 1, "{$p}NorthDakota" => 1,
                        "{$p}SouthDakota" => 2
                    ],
                "{$p}*e*e*:1,{$p}*e*:2,{$p}*o*o*:3,{$p}*o*:4" =>
                    [
                        "{$p}NewHampshire" => 1,  "{$p}NewJersey" => 1,
                        "{$p}NewYork" => 2 ,      "{$p}NorthCarolina" => 3,
                        "{$p}NorthDakota" => 3,   "{$p}SouthCarolina" => 3,
                        "{$p}SouthDakota" => 3
                    ],
                "TroutFishingInAmerica:100" => []
             ];
        $pruner = new Pruner;
        foreach ($cases as $queues => $output) {
            Assert::equal(
                $pruner->execute(['queues' => $queues, 'mode' => 'list']),
                $output,
                "Incorrect pruner output for queues '$queues'"
            );
        }
    }

    /**
     * Tests CodeRage\Queue\Pruner in "execute" mode with tasks of various ages
     */
    public function testPrunerExecute1()
    {
        $callbacks = [];

        // Create task at 12AM on each day of Jan 1970
        for ($i = 1; $i <=31; ++$i) {
            $day = sprintf('%02d', $i);
            $callbacks["1970-01-$day 00:00:00"] =
                function() use($i)
                {
                    $manager = new Manager(self::managerConstructorOptions());
                    $manager->createTask((string) $i, [
                        'lifetime' => 365 * 24 * 3600
                    ]);
                };
        }

        // Run pruner on Jan 31 1970 at 12PM and validate queue
        $callbacks["1970-01-31 12:00:00"] =
            function() use($i)
            {
                $pruner = new Pruner;
                $list =
                    $pruner->execute([
                        'queues' => 'CodeRageQueueTestBasicQueue:15'
                    ]);
                $manager = new Manager(self::managerConstructorOptions());
                $queue = $this->loadQueue($manager);
                Assert::equal(count($queue), 15, 'Incorrect queue size');
                foreach ($queue as $task)
                    if ($task->taskid() <= 16)
                        throw new
                            Error([
                                'status' => 'ASSERTION_FAILED',
                                'message' =>
                                    'Pruner failed to delete task ' .
                                        $task->taskid()
                            ]);
            };

        $this->executeCallbacks($callbacks);
    }

    /**
     * Tests CodeRage\Queue\Pruner in "execute" mode various status combinations
     */
    public function testPrunerExecute2()
    {
        $counts =
            [
                'SUCCESS' => 1,
                'PENDING' => 2,
                'FAILURE' => 4
            ];
        $db = new Db;
        $pruner = new Pruner;
        for ($i = 1; $i <= 3; ++$i) {
            $combinations = new Combinations(array_keys($counts), $i);
            foreach ($combinations->generator() as $codes) {

                // Create tasks
                $options = self::managerConstructorOptions();
                $manager = new Manager($options);
                foreach ($counts as $status => $count) {
                    for ($j = 0; $j < $count; ++$j) {
                        $manager->createTask("$status-$j", [
                            'lifetime' => 365 * 24 * 3600
                        ]);
                    }
                }

                // Update statuses
                foreach (self::loadQueue($manager) as $task) {
                    $code = substr($task->taskid(), 0, 7);
                    if ($code == 'SUCCESS') {
                        $task->update(Task::STATUS_SUCCESS);
                    } elseif ($code == 'FAILURE') {
                        $task->update(Task::STATUS_FAILURE);
                    }
                }

                // Run pruner
                $pruner->execute([
                    'queues' => 'CodeRageQueueTestBasicQueue:0',
                    'status' => join(',', $codes)
                ]);

                // Validate queue
                $actual = count(self::loadQueue($manager));
                $expected = array_sum($counts);
                foreach ($codes as $code)
                    $expected -= $counts[$code];
                Assert::equal(
                    $actual, $expected,
                    "Incorrect number of remaining tasks for status '" .
                        join(',', $codes) . "'"
                );

                // Clear queue
                $db->query('DELETE FROM CodeRageQueueTestBasicQueue');
            }
        }
    }

    /**
     * Tests CodeRage\Queue\Pruner with an invalid mode
     */
    public function testPrunerInvalidModeFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => '*:1', 'mode' => 'bark']);
    }

    /**
     * Tests CodeRage\Queue\Pruner with a non-ASCII "queues" option
     */
    public function testPrunerNonAsciiFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "Verkl\xc3\xA4rte Nacht"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component missing a colon
     */
    public function testPrunerMissingColonFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "MyFavoritQueue;20"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component missing a pattern
     */
    public function testPrunerMissingPatternFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => ":20"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component having an pattern
     * containing an invalid character
     */
    public function testPrunerInvalidPatternFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "My Favorite Queue:19"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component having an pattern
     * containing consecutive wildcard characters
     */
    public function testPrunerInvalidPatternFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "My**Queue:19"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component missing an age
     */
    public function testPrunerMissingAgeFail()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "MyFavoriteQueue:"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component having an age with
     * leading zeroes
     */
    public function testPrunerInvalidAgeFail1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "MyFavoriteQueue:019"]);
    }

    /**
     * Tests CodeRage\Queue\Pruner with queue component having an age with
     * non-digits
     */
    public function testPrunerInvalidAgeFail2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        (new Pruner)->execute(['queues' => "MyFavoriteQueue:10d"]);
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
        Access::initialize();
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

    protected function componentInitialize($component)
    {
        $this->clearDatabase();
    }

    private function clearDatabase()
    {
        $db = new Db;
        foreach (self::TABLES as $t)
            $db->query("DELETE from $t");
    }

    /**
     * Returns a collection of valid constructor options for
     * CodeRage\Queue\Manager with values for each non-session option
     *
     * @return array
     */
    private static function managerConstructorOptions()
    {
       $options = self::MANAGER_CTOR_OPTION_VALUES;
       $options['tool'] = new MockProcessor;
       return $options;
    }

    /**
     * Returns a collection of valid constructor options for
     * CodeRage\Queue\Task with values for each required option
     *
     * @return array
     */
    private static function taskConstructorOptions()
    {
        $options = ['taskid' => self::TASKID];
        foreach (self::TASK_CTOR_REQUIRED_OPTION_NAMES as $n)
            $options[$n] = self::TASK_CTOR_OPTION_VALUES[$n];
        return $options;
    }

    /**
     * Returns the contents of the given queue as a list of instances of
     * CodeRage\Queue\Task::encode()
     *
     * @param CodeRage\Queue\Manager $manager The manager
     * @param string $queue The queue name; defaults to
     *   CodeRageQueueTestBasicQueue
     * @return array
     */
    private static function loadQueue(Manager $manager,
        string $queue = 'CodeRageQueueTestBasicQueue')
    {
        $sql = "SELECT * FROM [$queue] ORDER BY RecordID";
        $result = (new Db)->query($sql);
        $tasks = [];
        while ($row = $result->fetchArray())
            $tasks[] = $manager->constructTask($row);
        return $tasks;
    }

    /**
     * Returns the last taks on the given queue an an instance of
     * CodeRage\Queue\Task::encode()
     *
     * @param CodeRage\Queue\Manager $manager The manager
     * @param string $queue The queue name; defaults to
     *   CodeRageQueueTestBasicQueue
     * @return CodeRage\Queue\Task
     * @throws CocdeRage\Error if the queue is empty
     */
    private static function lastTask(Manager $manager,
        string $queue = 'CodeRageQueueTestBasicQueue')
    {
        $sql = "SELECT * FROM [$queue] ORDER BY RecordID DESC";
        $row = (new Db)->fetchFirstArray($sql);
        if ($row === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'message' => 'Queue is empty'
                ]);
        return $manager->constructTask($row);
    }

    /**
     * Executes the given callbacks at the given simulated times
     *
     * @param array $callbacks An associative array mapping timestamps,
     *   represented as textual values or UNIX timestamps, to callables
     * @param mixed $data A value to be passed as an argument to each callable
     * @throws CodeRage\Error
     */
    private static function executeCallbacks(array $callbacks, $data = null)
    {
        // Construct list of steps
        Args::check($callbacks, 'map[callable]', 'callbacks');
        $steps = [];
        foreach ($callbacks as $time => $func) {
            if  (is_numeric($time) && ctype_digit($time)) {
                $steps[] = [(int) $time, $func];
            } elseif (($unix = strtotime($time)) !== false) {
                $steps[] = [$unix, $func];
            } else {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => 'Invalid timestamp'
                    ]);
            }
        }
        usort($steps, function($a, $b) { return $a[0] <=> $b[0]; });

        // Execute
        $current = Config::current();
        try {
            foreach ($steps as [$time, $func]) {
                Time::set($time);
                $offset = (string) ($time - Time::real());
                Config::setCurrent(new ArrayConfig(
                    ['coderage.util.time.offset' => $offset],
                    Config::current()
                ));
                $func($data);
            }
        } finally {
            Time::reset();
            Config::setCurrent($current);
        }
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
}
