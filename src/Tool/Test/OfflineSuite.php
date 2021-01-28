<?php

/**
 * Defines the class CodeRage\Tool\Test\OfflineSuite
 *
 * File:        CodeRage/Tool/Test/OfflineSuite.php
 * Date:        Fri Feb 17 11:22:16 MDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

use DateTime;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Test\Assert;
use CodeRage\Tool\Offline;
use CodeRage\Util\Factory;
use CodeRage\Util\Time;

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Test\Assert
 */
class OfflineSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * A path to the directory containing the offline data of OfflineTool
     *
     * @var string
     */
    const OFFLINE_TOOL_TEST_DIR =
        'CodeRage/Tool/Test/OfflineData/CodeRage/Tool/Test/OfflineTool';

    /**
     * Dot-separated class name of OfflineTool
     *
     * @var string
     */
    const OFFLINE_TOOL = 'CodeRage.Tool.Test.OfflineTool';

    /**
     * Dot-separated class name of HarryPotterLister
     *
     * @var string
     */
    const HARRY_POTTER_LISTER = 'CodeRage.Tool.Test.HarryPotterLister';

    /**
     * Options array containing the parameters for OfflineTool for testing
     *
     * @var string
     */
    const OFFLINE_TOOL_TEST_OPTIONS =
        [
            [
                'encodedToolOptions' => 'firstName=JAMES;from=2012-06-29',
                'toolOptions' =>
                    [
                        'from' => '2012-06-29',
                        'firstName' => 'James'
                    ]
            ],
            [
                'encodedToolOptions' => 'age=12;number=111',
                'toolOptions' =>
                    [
                        'age' => '12',
                        'number' => '111',
                    ]
            ],
            [
                'encodedToolOptions' => 'age=12;from=2015-01-01;number=1',
                'toolOptions' =>
                    [
                        'age' => '12',
                        'number' => '1',
                        'from' => '2015-01-01'
                    ]
            ]
        ];

    /**
     * Constructs an instance of CodeRage\Tool\Test\OfflineSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "Offline Suite",
            "Tests for the class CodeRage\Tool\Offline"
        );
    }

    /**
     * Tests throwing exceptions read from XML error documents
     */
    public function testPhpOfflineDataWithOptionList1Date2011_12_30()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $options = self::OFFLINE_TOOL_TEST_OPTIONS;
        $date = new DateTime('12/30/2011');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => $options[0]['toolOptions'],
                'time' => $time,
                'expectedOutput' =>
                    self::OFFLINE_TOOL_TEST_DIR . '/' .
                    $options[0]['encodedToolOptions'] .
                    '/0000-00-00-00.00.00-students.json',
                'offlineDataDirectory' => __DIR__ . '/OfflineData',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    public function testPhpOfflineDataWithOptionList1Date2012_12_30()
    {
        $options = self::OFFLINE_TOOL_TEST_OPTIONS;
        $date = new DateTime('12/30/2012');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => $options[0]['toolOptions'],
                'time' => $time,
                'expectedOutput' =>
                    self::OFFLINE_TOOL_TEST_DIR . '/' .
                    $options[0]['encodedToolOptions'] .
                    '/2012-12-29-00.00.00-students.json',
                'offlineDataDirectory' => __DIR__ . '/OfflineData',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    public function testPhpOfflineDataWithOptionList1Date2013_12_30()
    {
        $options = self::OFFLINE_TOOL_TEST_OPTIONS;
        $date = new DateTime('12/30/2013');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => $options[0]['toolOptions'],
                'time' => $time,
                'expectedOutput' =>
                    self::OFFLINE_TOOL_TEST_DIR . '/' .
                    $options[0]['encodedToolOptions'] .
                    '/2013-12-29-00.00.00-students.json',
                'offlineDataDirectory' => __DIR__ . '/OfflineData',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes an offline tool with a missing offline data directory
     */
    public function testPhpMissingOfflineDataDirectoryFailure()
    {
        $this->setExpectedStatusCode('CONFIGURATION_ERROR');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => [],
                'offlineDataDirectory' => 'no/such/directory',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes an offline tool with a plain file as the offline data directory
     */
    public function testPhpInvalidOfflineDataDirectoryFailure()
    {
        $this->setExpectedStatusCode('CONFIGURATION_ERROR');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => [],
                'offlineDataDirectory' =>
                    __DIR__ . '/OfflineData/file-not-directory.txt',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes an offline tool with missing offline options directory. This is
     * the directory formed by appending directories named after the components
     * of the tool's class name to the offline data directory path.
     */
    public function testPhpMissingOfflineOptionsDirectoryFailure()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => [],
                'offlineDataDirectory' => __DIR__ .
                    '/OfflineData/empty-directory',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes an offline tool with a plain file at the location of the offline
     * files directory. This is the directory containing timestamped data files
     * and XML error documents.
     */
    public function testPhpOfflineFilesDirectoryIsFileFailure()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $options = self::OFFLINE_TOOL_TEST_OPTIONS;
        $this->executePhpOfflineTool(
            [
                'toolOptions' => $options[2]['toolOptions'],
                'offlineDataDirectory' => __DIR__ . '/OfflineData',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes an offline tool with a time offset for which no appropriately
     * timestamped data file exists.
     */
    public function testPhpMissingOfflineFileBeforeDate2015_12_30Failure()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $options = self::OFFLINE_TOOL_TEST_OPTIONS;
        $date = new DateTime('12/30/2014');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' => $options[1]['toolOptions'],
                'time' => $time,
                'expectedOutput' => null,
                'offlineDataDirectory' =>  __DIR__ . '/OfflineData',
                'toolName' => self::OFFLINE_TOOL,
                'mode' => 'offline'
            ]
        );
    }

    public function testPhpWritingOfflineDataToDirectoryWithoutPermissionFailure()
    {
        $this->setExpectedStatusCode('FILESYSTEM_ERROR');
        $toolPath = str_replace('.', '/', self::HARRY_POTTER_LISTER);
        $path =  __DIR__ . "/OfflineData/$toolPath/from=0000-00-01";
        chmod($path, 0444);
        try {
            $this->executePhpOfflineTool(
                [
                    'toolOptions' =>
                        [
                           'from' => '0000-00-01',
                        ],
                    'offlineDataDirectory' =>  __DIR__ . '/OfflineData',
                    'toolName' => self::HARRY_POTTER_LISTER,
                    'mode' => 'record'
                ]
            );
        } catch (Error $e) {
            chmod($path, 0777);
            throw $e;
        }
    }

    /**
     * Executes a tool in 'record' mode to generate offline data and then
     * executes the tool in 'offline' mode with the recorded files used as
     * offline data
     */
    public function testPhpToolInRecordAndOfflineMode1()
    {
        $date = new DateTime('12/29/2014');
        $time = $date->format('U');
        $tmpDir = File::tempDir();
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '2001-12-01',
	                   'to' => '2007-12-01'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Order of the Phoenix',
                        'Harry Potter and the Half-Blood Prince',
                        'Harry Potter and the Deathly Hallows'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'record'
            ]
        );
        $date = new DateTime('12/30/2014');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '2001-12-01',
	                   'to' => '2007-12-01'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Order of the Phoenix',
                        'Harry Potter and the Half-Blood Prince',
                        'Harry Potter and the Deathly Hallows'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes a tool in 'record' mode to generate offline data and then
     * executes the tool in 'offline' mode with the recorded files used as
     * offline data
     */
    public function testPhpToolInRecordAndOfflineMode2()
    {
        $date = new DateTime('12/30/2014');
        $time = $date->format('U');
        $tmpDir = File::tempDir();
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '2005-12-01'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Deathly Hallows'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'record'
            ]
        );
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '2005-12-01'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Deathly Hallows'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes a tool in 'record' mode twice to generate multiple offline data
     * files and then executes the tool in 'offline' mode with the more recently
     * recorded file used as offline data
     */
    public function testPhpToolInRecordAndOfflineMode3()
    {
        $date = new DateTime('12/30/1997');
        $time = $date->format('U');
        $tmpDir = File::tempDir();
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'to' => '2007-12-30'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        "Harry Potter and the Sorcerer's Stone"
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'record'
            ]
        );
        $date = new DateTime('12/30/2000');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'to' => '2007-12-30'
                    ],
                'time' => $time,
                    [
                        "Harry Potter and the Sorcerer's Stone",
                        'Harry Potter and the Chamber of Secrets',
                        'Harry Potter and the Prisoner of Azkaban',
                        'Harry Potter and the Goblet of Fire'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'record'
            ]
        );
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'to' => '2007-12-30'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        "Harry Potter and the Sorcerer's Stone",
                        'Harry Potter and the Chamber of Secrets',
                        'Harry Potter and the Prisoner of Azkaban',
                        'Harry Potter and the Goblet of Fire'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'offline'
            ]
        );
    }

    /**
     * Executes a tool in 'record' mode twice to generate multiple offline data
     * files and then executes the tool in 'offline' mode with the more recently
     * recorded file used as offline data
     */
    public function testPhpToolInRecordAndOfflineMode4()
    {
        $date = new DateTime('12/30/1999');
        $time = $date->format('U');
        $tmpDir = File::tempDir();
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '1997-12-30',
                       'to' => '2000-12-30'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Chamber of Secrets',
                        'Harry Potter and the Prisoner of Azkaban',
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'record'
            ]
        );
        $date = new DateTime('12/30/2000');
        $time = $date->format('U');
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '1997-12-30',
                       'to' => '2000-12-30'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Chamber of Secrets',
                        'Harry Potter and the Prisoner of Azkaban',
                        'Harry Potter and the Goblet of Fire'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'record'
            ]
        );
        $this->executePhpOfflineTool(
            [
                'toolOptions' =>
                    [
                       'from' => '1997-12-30',
                       'to' => '2000-12-30'
                    ],
                'time' => $time,
                'expectedOutput' =>
                    [
                        'Harry Potter and the Chamber of Secrets',
                        'Harry Potter and the Prisoner of Azkaban',
                        'Harry Potter and the Goblet of Fire'
                    ],
                'offlineDataDirectory' => $tmpDir,
                'toolName' => self::HARRY_POTTER_LISTER,
                'mode' => 'offline'
            ]
        );
    }

    public function testPhpInvalidToolParameterToFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $tmpDir = File::tempDir();
        try {
            $this->executePhpOfflineTool(
                [
                    'toolOptions' =>
                        [
	                       'to' => 'invalid-to-date'
                        ],
                    'offlineDataDirectory' => $tmpDir,
                    'toolName' => self::HARRY_POTTER_LISTER,
                    'mode' => 'record'
                ]
            );
        } catch (Error $error1) {
            Assert::equal(
                $error1->status(), 'INVALID_PARAMETER', 'Error status'
            );
            try {
                $this->executePhpOfflineTool(
                    [
                        'toolOptions' =>
                            [
       	                       'to' => 'invalid-to-date'
                            ],
                        'offlineDataDirectory' => $tmpDir,
                        'toolName' => self::HARRY_POTTER_LISTER,
                        'mode' => 'offline'
                    ]
                );
            } catch (Error $error2) {
                Assert::equal(
                    $error2->message(), $error1->message(), 'Error message'
                );
                throw $error2;
            }
        }
    }

    public function testPhpInvalidToolParameterFromFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $tmpDir = File::tempDir();
        try {
            $this->executePhpOfflineTool(
                [
                    'toolOptions' =>
                        [
                           'from' => 'invalid-from-date',
                        ],
                    'offlineDataDirectory' => $tmpDir,
                    'toolName' => self::HARRY_POTTER_LISTER,
                    'mode' => 'record'
                ]
            );
        } catch (Error $error1) {
            Assert::equal(
                $error1->status(), 'INVALID_PARAMETER', 'Error status'
            );
            try {
                $this->executePhpOfflineTool(
                    [
                        'toolOptions' =>
                            [
                               'from' => 'invalid-from-date',
                            ],
                        'offlineDataDirectory' => $tmpDir,
                        'toolName' => self::HARRY_POTTER_LISTER,
                        'mode' => 'offline'
                    ]
                );
            } catch (Error $error2) {
                Assert::equal(
                    $error2->message(), $error1->message(), 'Error message'
                );
                throw $error2;
            }
        }
    }

    public function testPhpToolInconsistentParameterFailure()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $tmpDir = File::tempDir();
        try {
            $this->executePhpOfflineTool(
                [
                    'toolOptions' =>
                        [
                           'from' => '2000-12-30',
    	                   'to' => '2000-12-20'
                        ],
                    'offlineDataDirectory' => $tmpDir,
                    'toolName' => self::HARRY_POTTER_LISTER,
                    'mode' => 'record'
                ]
            );
        } catch (Error $error1) {
            Assert::equal(
                $error1->status(), 'INCONSISTENT_PARAMETERS', 'Error status'
            );
            try {
                $this->executePhpOfflineTool(
                    [
                        'toolOptions' =>
                            [
                               'from' => '2000-12-30',
        	                   'to' => '2000-12-20'
                            ],
                        'offlineDataDirectory' => $tmpDir,
                        'toolName' => self::HARRY_POTTER_LISTER,
                        'mode' => 'offline'
                    ]
                );
            } catch (Error $error2) {
                Assert::equal(
                    $error2->message(), $error1->message(), 'Error message'
                );
                throw $error2;
            }
        }
    }

    protected function suiteInitialize()
    {
        $this->config = Config::current();
        $this->time = Time::get();
    }

    protected function componentCleanup($component)
    {
        Config::setCurrent($this->config);
        Time::set($this->time);
    }

    /**
     * Executes a tool with the provided options and the given times offset, and
     * validates the output by comparing it the expected output
     *
     * @param array $options Supports the following options:
     *     toolName - The tool name, in dot-separated form
     *     toolOptions - An array for tools options
     *     offlineDataDirectory - A path to the offline data directory
     *     time - a timestamp indicating the time at which the tool should be
     *       executed, for use with CodeRage\Util\Time::set() (optional)
     *     expectedOutput - The path to file contains expected result or
     *       an array with expected result (optional)
     */
    private function executePhpOfflineTool($options)
    {
        // Set coderage.tool.offline.data_directory configuration variable
        $properties  =
            [
                'coderage.tool.offline.data_directory' =>
                    $options['offlineDataDirectory'],
                'coderage.tool.offline.mode' => $options['mode']
            ];
        $config = new Config($properties, Config::current());
        Config::setCurrent($config);

        // Set current time
        if (isset($options['time']))
            Time::set((int) $options['time']);

        // Run tool
        $tool = Factory::create(['class' => $options['toolName']]);
        $result = $tool->execute($options['toolOptions']);

        // Compare result
        if (isset($options['expectedOutput'])) {
            $expected = is_array($options['expectedOutput']) ?
                $options['expectedOutput'] :
                json_decode(
                    file_get_contents(
                        Config::projectRoot() . '/' . $options['expectedOutput']
                    )
                );
            Assert::equivalentData($result, $expected);
        }
    }

    /**
     * The inital configuration to be restored at componentCleanup()
     *
     * @var CodeRage\Config
     */
    private $config;

    /**
     * The inital time to be restored at componentCleanup()
     *
     * @var int
     */
    private $time;
}
