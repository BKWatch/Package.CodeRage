<?php

/**
 * Defines the class CodeRage\Util\Test\CommandLineSuite
 *
 * File:        CodeRage/Util/Test/CommandLineSuite.php
 * Date:        Sun Nov  1 02:43:17 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Test\Assert;
use CodeRage\Util\CommandLine;

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Util\CommandLine
 */
class CommandLineSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Util\Test\NativeDataEncoderSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "Command-Line Test Suite",
            "Tests the class CodeRage\Util\CommandLine"
        );
    }

    public function testLookup1()
    {
        $options =
            [
                ['b', 'boolean'],
                ['i', 'int'],
                ['f', 'float'],
                ['s', 'string'],
                ['m', 'multiple']
            ];
        $command = $this->exampleCommand;
        foreach ($options as list($o1, $o2)) {
            Assert::isTrue(
                $command->lookupOption($o1) === $command->lookupOption($o2),
                "Lookup mismatch for -$o1, --$o2"
            );
        }
    }

    public function testLookupFail1()
    {
        $this->setExpectedException();
        $this->exampleCommand->lookupOption('lollapalooza');
    }

    public function testOptionFail1()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'i',
            'longForm' => 'hello',
            'type' => 'int',
            'required' => true
        ]);
        $command->addOption([
            'shortForm' => 'i',
            'longForm' => 'goodbye',
            'type' => 'string'
        ]);
    }

    public function testOptionFail2()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'i',
            'longForm' => 'hello',
            'type' => 'int',
            'required' => true
        ]);
        $command->addOption([
            'shortForm' => 'j',
            'longForm' => 'hello',
            'type' => 'string'
        ]);
    }

    public function testOptionFail3()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption(['lollapalooza' => true]);
    }

    public function testOptionFail4()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([]);
    }

    public function testOptionFail5()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'i',
            'longForm' => 'hello',
            'type' => 'int',
            'multiple' => true,
            'default' => 6.0
        ]);
    }

    public function testOptionFail6()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'i',
            'longForm' => 'hello',
            'type' => 'bool',
            'valueOptional' => true
        ]);
    }

    public function testOptionFail7()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'i',
            'longForm' => 'hello',
            'type' => 'int',
            'multiple' => true,
            'valueOptional' => true
        ]);
    }

    public function testOptionFail8()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'hello'
        ]);
    }

    public function testOptionFail9()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'longForm' => 'i'
        ]);
    }

    public function testOptionFail10()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'i',
            'type' => 'lollapalooza'
        ]);
    }

    public function testEmptyCommandLine()
    {
        $this->parseExample([]);
        foreach (['i', 'f', 's', 'm', 'x', 'optional-int'] as $name) {
            $opt = $this->exampleCommand->lookupOption($name);
            Assert::isFalse($opt->hasValue(), "Found value for option '$name'");
        }
    }

    public function testBoolean1()
    {
        $this->parseExample('-b');
        $opt = $this->exampleCommand->lookupOption('b');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isBool($value);
        Assert::isTrue($value);
    }

    public function testBoolean2()
    {
        $this->parseExample('--boolean');
        $opt = $this->exampleCommand->lookupOption('b');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isBool($value);
        Assert::isTrue($value);
    }

    public function testBoolean3()
    {
        $this->parseExample('-bs xxx');
        $opt = $this->exampleCommand->lookupOption('b');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isBool($value);
        Assert::isTrue($value);
    }

    public function testBooleanFail1()
    {
        $this->setExpectedException();
        $this->parseExample('-b -b');
    }

    public function testInt1()
    {
        $this->parseExample('-i 33');
        $opt = $this->exampleCommand->lookupOption('i');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 33);
    }

    public function testInt2()
    {
        $this->parseExample('--int 33');
        $opt = $this->exampleCommand->lookupOption('i');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 33);
    }

    public function testInt3()
    {
        $this->parseExample('--int=-33');
        $opt = $this->exampleCommand->lookupOption('i');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, -33);
    }

    public function testIntFail1()
    {
        $this->setExpectedException();
        $this->parseExample('--int 33.33');
    }

    public function testIntFail2()
    {
        $this->setExpectedException();
        $this->parseExample('--int hello');
    }

    public function testIntFail3()
    {
        $this->setExpectedException();
        $this->parseExample('-i 9 -i 8');
    }

    public function testFloat1()
    {
        $this->parseExample('-f 3.3');
        $opt = $this->exampleCommand->lookupOption('f');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isFloat($value);
        Assert::equal($value, 3.3);
    }

    public function testFloat2()
    {
        $this->parseExample('--float 3.3');
        $opt = $this->exampleCommand->lookupOption('f');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isFloat($value);
        Assert::equal($value, 3.3);
    }

    public function testFloat3()
    {
        $this->parseExample('--float=-3.3');
        $opt = $this->exampleCommand->lookupOption('f');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isFloat($value);
        Assert::equal($value, -3.3);
    }

    public function testFloat4()
    {
        $this->parseExample('--float 33');
        $opt = $this->exampleCommand->lookupOption('f');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isFloat($value);
        Assert::equal($value, 33.0);
    }

    public function testFloatFail1()
    {
        $this->setExpectedException();
        $this->parseExample('--float hello');
    }

    public function testFloatFail2()
    {
        $this->setExpectedException();
        $this->parseExample('-f 3.3 -f 33');
    }

    public function testString1()
    {
        $this->parseExample('-s hello');
        $opt = $this->exampleCommand->lookupOption('s');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isString($value);
        Assert::equal($value, 'hello');
    }

    public function testString2()
    {
        $this->parseExample('--string hello');
        $opt = $this->exampleCommand->lookupOption('s');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isString($value);
        Assert::equal($value, 'hello');
    }

    public function testString3()
    {
        $this->parseExample('--string=-o-o-o-');
        $opt = $this->exampleCommand->lookupOption('s');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isString($value);
        Assert::equal($value, '-o-o-o-');
    }

    public function testString4()
    {
        $this->parseExample('--string=--o--o--o--');
        $opt = $this->exampleCommand->lookupOption('s');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isString($value);
        Assert::equal($value, '--o--o--o--');
    }

    public function testString5()
    {
        $this->parseExample('--string=');
        $opt = $this->exampleCommand->lookupOption('s');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isString($value);
        Assert::equal($value, '');
    }

    public function testStringFail1()
    {
        $this->setExpectedException();
        $this->parseExample('-s hello -s goodbye');
    }

    public function testMultiple1()
    {
        $this->parseExample('-m hello');
        $opt = $this->exampleCommand->lookupOption('m');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value(true);
        Assert::isArray($value);
        Assert::equal($value, ['hello']);
    }

    public function testMultiple2()
    {
        $this->parseExample('-m hello --multiple goodbye --multiple=');
        $opt = $this->exampleCommand->lookupOption('m');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value(true);
        Assert::isArray($value);
        Assert::equal($value, ['hello', 'goodbye', '']);
    }

    public function testMultipleInt1()
    {
        $this->parseExample('--multiple-int 22');
        $opt = $this->exampleCommand->lookupOption('multiple-int');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value(true);
        Assert::isArray($value);
        Assert::equal($value, [22]);
    }

    public function testMultipleInt2()
    {
        $this->parseExample('--multiple-int 22 --multiple-int 0 --multiple-int=-22');
        $opt = $this->exampleCommand->lookupOption('multiple-int');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value(true);
        Assert::isArray($value);
        Assert::equal($value, [22, 0, -22]);
    }

    public function testOptionalInt1()
    {
        $this->parseExample('--optional-int');
        $opt = $this->exampleCommand->lookupOption('optional-int');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isBool($value);
        Assert::isTrue($value);
    }

    public function testOptionalInt2()
    {
        $this->parseExample('--optional-int 1');
        $opt = $this->exampleCommand->lookupOption('optional-int');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 1);
    }

    public function testOptionalInt3()
    {
        $this->parseExample('--optional-int 0');
        $opt = $this->exampleCommand->lookupOption('optional-int');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 0);
    }

    public function testDefault1()
    {
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'd',
            'longForm' => 'default',
            'label' => 'default int',
            'description' => 'Example int with default value',
            'type' => 'int',
            'default' => 77
        ]);
        $command->parse(['argv' => ['tool', '-d', '555']]);
        $opt = $command->lookupOption('d');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 555);
    }

    public function testDefault2()
    {
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'd',
            'longForm' => 'default',
            'label' => 'default int',
            'description' => 'Example int with default value',
            'type' => 'int',
            'default' => 77
        ]);
        $command->parse(['argv' => ['tool']]);
        $opt = $command->lookupOption('d');
        Assert::isTrue($opt->hasValue());
        Assert::isFalse($opt->hasExplicitValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 77);
    }

    public function testRequired1()
    {
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'r',
            'type' => 'int',
            'required' => true
        ]);
        $command->parse(['argv' => ['tool', '-r', '200']]);
        $opt = $command->lookupOption('r');
        Assert::isTrue($opt->hasValue());
        $value = $opt->value();
        Assert::isInt($value);
        Assert::equal($value, 200);
    }

    public function testRequiredFail1()
    {
        $this->setExpectedException();
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'r',
            'type' => 'int',
            'required' => true
        ]);
        $command->parse(['argv' => ['tool']]);
    }

    public function testHelplessFail()
    {
        $this->setExpectedException('CodeRage\Error', 'STATE_ERROR');
        $command = new CommandLine(['name' => 'command']);
        $command->parse(['argv' => ['command']]);
        $command->setHelpless(true);
    }

    public function testVersionFail()
    {
        $this->setExpectedException('CodeRage\Error', 'STATE_ERROR');
        $command = new CommandLine(['name' => 'command']);
        $command->parse(['argv' => ['command']]);
        $command->setVersion('2.1.3');
    }

    public function testLookupSubcommandFail()
    {
        $this->setExpectedException('CodeRage\Error', 'OBJECT_DOES_NOT_EXIST');
        $command = new CommandLine(['name' => 'command']);
        $command->lookupSubcommand('popcorn');
    }

    public function testAddSubcommandFail()
    {
        $this->setExpectedException('CodeRage\Error', 'OBJECT_EXISTS');
        $command = new CommandLine(['name' => 'command']);
        $command->addSubcommand(['name' => 'pop']);
        $command->addSubcommand(['name' => 'pop']);
    }

    public function testParseArgumentcountFail()
    {
        $this->setExpectedException('TypeError');
        $command = new CommandLine(['name' => 'command']);
        $command->parse(1, 2, 3);
    }

    public function testActiveSwitchWithSubcommandFail()
    {
        $this->setExpectedException('CodeRage\Error', 'INCONSISTENT_PARAMETERS');
        $parent = new Command('parent');
        $parent->addSubcommand(new Command('kid'));
        $parent->parse(['argv' => ['parent', '-z', 'kid']]);
    }

    public function testMultipleActiveSwitchesFail()
    {
        $this->setExpectedException('CodeRage\Error', 'INCONSISTENT_PARAMETERS');
        $command = new CommandLine(['name' => 'command']);
        $command->addOption([
            'shortForm' => 'x',
            'type' => 'switch',
            'executor' => function($cmd) { }
        ]);
        $command->addOption([
            'shortForm' => 'y',
            'type' => 'switch',
            'executor' => function($cmd) { }
        ]);
        $command->parse(['argv' => ['command', '-x', '-y']]);
    }

    public function testParse1()
    {
        $this->checkParse(
            '-s hello -b -i 33 --float=-33.3 this is your life',
            [
                'help' => false,
                'boolean' => true,
                'int' => 33,
                'string' => 'hello',
                'float' => -33.3,
                'y' => false,
                'z' => false
            ],
            ['this', 'is', 'your', 'life']
        );
    }

    public function testParse2()
    {
        $this->checkParse(
            '-xhello -m I -m am -sgoodbye -m hungry today',
            [
                'help' => false,
                'boolean' => false,
                'string' => 'goodbye',
                'multiple' => ['I', 'am', 'hungry'],
                'x' => 'hello',
                'y' => false,
                'z' => false
            ],
            ['today']
        );
    }

    public function testParse3()
    {
        $this->checkParse(
            '-byzx hello world',
            [
                'help' => false,
                'boolean' => true,
                'x' => 'hello',
                'y' => true,
                'z' => true
            ],
            ['world']
        );
    }

    public function testParse4()
    {
        $this->checkParse(
            '-byzo -f 5.0 hello world',
            [
                'help' => false,
                'boolean' => true,
                'float' => 5.0,
                'optional-int' => true,
                'y' => true,
                'z' => true
            ],
            ['hello', 'world']
        );
    }

    public function testParse5()
    {
        $this->checkParse(
            '-byzo -- hello world',
            [
                'help' => false,
                'boolean' => true,
                'optional-int' => true,
                'y' => true,
                'z' => true
            ],
            ['hello', 'world']
        );
    }

    public function testExecute1()
    {
        $command = new Command('commmand');
        $this->checkExecute(
            $command,
            'commmand -s hello -b -i 33 -f 5.0 hello world',
            [
                (object) [
                    'name' => 'commmand',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'float' => 5.0,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => ['hello', 'world']
                ]
            ]
        );
    }

    public function testExecute2()
    {
        $parent = new Command('parent');
        $parent->addSubcommand(new Command('kid1'));
        $parent->addSubcommand(new Command('kid2'));
        $this->checkExecute(
            $parent,
            'parent -s hello -b -i 33 kid1 -f 5.0 hello world',
            [
                (object) [
                    'name' => 'parent',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => []
                ],
                (object) [
                    'name' => 'kid1',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => false,
                            'float' => 5.0,
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => ['hello', 'world']
                ]
            ]
        );
    }

    public function testExecute3()
    {
        $parent = new Command('parent');
        $parent->addSubcommand(new Command('kid1'));
        $parent->addSubcommand(new Command('kid2'));
        $this->checkExecute(
            $parent,
            'parent -s hello -b -i 33 kid2 -f 5.0 hello world',
            [
                (object) [
                    'name' => 'parent',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => []
                ],
                (object) [
                    'name' => 'kid2',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => false,
                            'float' => 5.0,
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => ['hello', 'world']
                ]
            ]
        );
    }

    public function testExecute4()
    {
        $parent = new Command('parent');
        $kid1 = new Command('kid1');
        $kid1->addSubcommand(new Command('grandkid'));
        $parent->addSubcommand($kid1);
        $parent->addSubcommand(new Command('kid2'));
        $this->checkExecute(
            $parent,
            'parent -s hello -b -i 33 kid1 -f 5.0 grandkid -m I -m am ' .
                '-sgoodbye -m hungry hello world',
            [
                (object) [
                    'name' => 'parent',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => []
                ],
                (object) [
                    'name' => 'kid1',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => false,
                            'float' => 5.0,
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => []
                ],
                (object) [
                    'name' => 'grandkid',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => false,
                            'string' => 'goodbye',
                            'multiple' => ['I', 'am', 'hungry'],
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => ['hello', 'world']
                ]
            ]
        );
    }

    public function testExecute5()
    {
        $command = new Command('commmand', true);
        $this->checkExecute(
            $command,
            'commmand -s hello -b -i 33 -f 5.0 hello world',
            [
                (object) [
                    'name' => 'commmand',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'float' => 5.0,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => ['hello', 'world']
                ]
            ],
            'executor'
        );
    }

    public function testExecute6()
    {
        $parent = new Command('parent');
        $parent->addSubcommand(new Command('kid1'));
        $parent->addSubcommand(new Command('kid2', true));
        $this->checkExecute(
            $parent,
            'parent -s hello -b -i 33 kid2 -f 5.0 hello world',
            [
                (object) [
                    'name' => 'parent',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => []
                ],
                (object) [
                    'name' => 'kid2',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => false,
                            'float' => 5.0,
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => ['hello', 'world']
                ]
            ],
            'executor'
        );
    }

    public function testExecute7()
    {
        $parent = new Command('parent');
        $parent->addSubcommand(new Command('kid1'));
        $parent->addSubcommand(new Command('kid2'));
        $this->checkExecute(
            $parent,
            'parent -s hello -b -i 33 kid2 -z',
            [
                (object) [
                    'name' => 'parent',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => true,
                            'int' => 33,
                            'string' => 'hello',
                            'y' => false,
                            'z' => false
                        ],
                    'arguments' => []
                ],
                (object) [
                    'name' => 'kid2',
                    'options' => (object)
                        [
                            'help' => false,
                            'boolean' => false,
                            'y' => false,
                            'z' => true
                        ],
                    'arguments' => []
                ]
            ],
            'switch executor'
        );
    }

    public function testVersion()
    {
        $command =
            new CommandLine([
                    'name' => 'command',
                    'version' => '2.1.3'
                ]);
        ob_start();
        $command->execute(['argv' => ['command', '--version']]);
        $contents = ob_get_contents();
        ob_end_clean();
        Assert::equal($contents, '2.1.3');
    }

    /**
     * @param string $argv
     */
    private function parseExample($argv)
    {
        if (is_string($argv))
            $argv = preg_split('/\s+/', trim($argv));
        array_unshift($argv, 'tool');
        $this->exampleCommand->parse(['argv' => $argv]);
    }

    /**
     * @param string $argv
     * @throws Exception
     */
    private function checkParse($argv, $options, $arguments)
    {
        if (is_string($argv))
            $argv = preg_split('/\s+/', trim($argv));
        array_unshift($argv, 'tool');
        $command = $this->exampleCommand;
        $command->parse(['argv' => $argv]);
        Assert::equal((object)$command->values(), (object)$options);
        Assert::equal($command->arguments(), $arguments);
    }

    /**
     * @param CodeRage\Util\CommandLine $command
     * @param string $argv
     * @param array $execution a list of instances of stdClass with properties
     *   "name", "options", and "arguments", representing the executed command
     *   and its sequence of active subcommands
     * @param string $comment Additional information about the command execution
     * @throws Exception
     */
    private function checkExecute(
        CommandLine $command, $argv, $execution, $comment = '')
    {
        if (is_string($argv))
            $argv = preg_split('/\s+/', trim($argv));
        $command->execute(['argv' => $argv]);
        CommandLineExecution::check($execution, $comment);
    }

    public function beforeCase()
    {
        $this->exampleCommand = new Command("example");
        CommandLineExecution::clear();
    }

    /**
     * Example command line used for testing
     *
     * @var CodeRage\Util\CommandLine
     */
    private $exampleCommand;
}
