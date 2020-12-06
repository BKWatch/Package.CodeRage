<?php

/**
 * Test suite for CodeRage\Build\ProjectConfig
 *
 * File:        CodeRage/Build/Test/ConfigSuite.php
 * Date:        Tue Mar 17 11:19:04 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test;

use const CodeRage\Build\BOOLEAN;
use const CodeRage\Build\COMMAND_LINE;
use CodeRage\Build\Config\Basic;
use CodeRage\Build\Config\Compound;
use CodeRage\Build\Config\Property;
use CodeRage\Build\Config\Reader\CommandLine;
use CodeRage\Build\Config\Reader\Environment;
use CodeRage\Build\Config\Reader\File;
use const CodeRage\Build\ENVIRONMENT;
use const CodeRage\Build\FLOAT;
use const CodeRage\Build\INT;
use const CodeRage\Build\ISSET_;
use const CodeRage\Build\LIST_;
use const CodeRage\Build\NAMESPACE_URI;
use const CodeRage\Build\REQUIRED;
use const CodeRage\Build\STRING;
use CodeRage\Config;
use CodeRage\Error;
use function CodeRage\File\generate;
use function CodeRage\File\temp;
use function CodeRage\Util\escapeShellArg;
use function CodeRage\Util\printScalar;

/**
 * @ignore
 */
require_once('CodeRage/Build/Config/Writer/Perl.php');
require_once('CodeRage/Build/Constants.php');
require_once('CodeRage/File/generate.php');
require_once('CodeRage/File/temp.php');
require_once('CodeRage/Util/printScalar.php');

/**
 * Test suite for project configuration.
 */
class ConfigSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs a CodeRage\Build\Test\Suite.
     */
    public function __construct()
    {
        parent::__construct('Project Config Test Suite');
    }

    /**
     * Tests CodeRage\Build\Config\Property with an invalid property name.
     */
    public function testPropertyFail1()
    {
        $this->setExpectedException();
        new Property(28, 0, null, null, null);
    }

    /**
     * Tests CodeRage\Build\Config\Property with a value but without CodeRage\Build\ISSET_
     * set.
     */
    public function testPropertyFail2()
    {
        $this->setExpectedException();
        new Property('test', 0, 1000.0, null, null);
    }

    /**
     * Tests CodeRage\Build\Config\Property with a list value but without
     * CodeRage\Build\LIST_ set.
     */
    public function testPropertyFail3()
    {
        $this->setExpectedException();
        new Property(
                'test', INT | ISSET_, [1, 2, 3],
                null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a scalar value but with
     * CodeRage\Build\LIST_ set.
     */
    public function testPropertyFail4()
    {
        $this->setExpectedException();
        new Property(
                'test', INT | LIST_ | ISSET_, 1,
                null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a boolean value but CodeRage\Build\INT
     * set.
     */
    public function testPropertyFail5()
    {
        $this->setExpectedException();
        new Property(
                'test', INT | ISSET_, true, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a boolean value but CodeRage\Build\FLOAT
     * set.
     */
    public function testPropertyFail6()
    {
        $this->setExpectedException();
        new Property(
                'test', FLOAT | ISSET_, true, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a boolean value but CodeRage\Build\STRING
     * set.
     */
    public function testPropertyFail7()
    {
        $this->setExpectedException();
        new Property(
                'test', STRING | ISSET_, true, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with an integer value but CodeRage\Build\BOOLEAN
     * set.
     */
    public function testPropertyFail8()
    {
        $this->setExpectedException();
        new Property(
                'test', BOOLEAN | ISSET_, 15, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with an integer value but CodeRage\Build\STRING
     * set.
     */
    public function testPropertyFail9()
    {
        $this->setExpectedException();
        new Property(
                'test', STRING | ISSET_, 15, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a float value but CodeRage\Build\BOOLEAN
     * set.
     */
    public function testPropertyFail10()
    {
        $this->setExpectedException();
        new Property(
                'test', BOOLEAN | ISSET_, -0.9, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a float value but CodeRage\Build\INT
     * set.
     */
    public function testPropertyFail11()
    {
        $this->setExpectedException();
        new Property(
                'test', INT | ISSET_, -0.9, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Property with a float value but CodeRage\Build\STRING
     * set.
     */
    public function testPropertyFail12()
    {
        $this->setExpectedException();
        new Property(
                'test', STRING| ISSET_, -0.9, null, null
            );
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine.
     */
    public function testConfigReaderCommandLine()
    {
        $vars =
            [
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'hello.how_are_you.really' => 'ok',
                'howdy_pardner' => 'Greetings!'
            ];
        $cmd = self::newCommandLine($vars);
        $reader = new CommandLine($cmd);
        $config = $reader->read();
        self::checkConfigurations($config, $vars);
        foreach ($config->propertyNames() as $n) {
            $p = $config->lookupProperty($n);
            assert('$p->isList() == false');
            assert('$p->required() == false');
            assert('$p->sticky() == false');
            assert('$p->obfuscate() == false');
            assert('$p->specifiedAt() == 0');
            assert('$p->setAt() == CodeRage\Build\COMMAND_LINE');
        }
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine with mailformed variable names.
     */
    public function testConfigReaderCommandLineFail1()
    {
        $this->setExpectedException();
        $src = ['123' => 'xyz'];
        $cmd = self::newCommandLine($src);
        $reader = new CommandLine($cmd);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine with mailformed variable names.
     */
    public function testConfigReaderCommandLineFail2()
    {
        $this->setExpectedException();
        $src = ['hello.123' => 'xyz'];
        $cmd = self::newCommandLine($src);
        $reader = new CommandLine($cmd);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine with mailformed variable names.
     */
    public function testConfigReaderCommandLineFail3()
    {
        $this->setExpectedException();
        $src = ['hello..123' => 'xyz'];
        $cmd = self::newCommandLine($src);
        $reader = new CommandLine($cmd);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine with mailformed variable names.
     */
    public function testConfigReaderCommandLineFail4()
    {
        $this->setExpectedException();
        $src = ['hello.how_are_you.' => 'xyz'];
        $cmd = self::newCommandLine($src);
        $reader = new CommandLine($cmd);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\Environment.
     */
    public function testConfigReaderEnvironment()
    {
        $src =
            [
                'CODERAGE_HELLO' => 'hi',
                'CODERAGE_HELLO___HOW_ARE_YOU' => 'fine, thanks',
                'CODERAGE_HELLO___HOW_ARE_YOU__REALLY' => 'ok',
                'CODERAGE_Howdy_Pardner' => 'Greetings!'
            ];
        $target =
            [
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'hello.how_are_you.really' => 'ok',
                'howdy_pardner' => 'Greetings!'
            ];
        self::setEnvironment($src);
        $reader = new Environment;
        $config = $reader->read();
        self::checkConfigurations($config, $target);
        foreach ($config->propertyNames() as $n) {
            $p = $config->lookupProperty($n);
            assert('$p->isList() == false');
            assert('$p->required() == false');
            assert('$p->sticky() == false');
            assert('$p->obfuscate() == false');
            assert('$p->specifiedAt() == 0');
            assert('$p->setAt() == CodeRage\Build\ENVIRONMENT');
        }
    }

    /**
     * Tests CodeRage\Build\Config\Reader\Environment with mailformed variable names.
     */
    public function testConfigReaderEnvironmentFail1()
    {
        $this->setExpectedException();
        $src = ['CODERAGE_123' => 'xyz'];
        self::setEnvironment($src);
        $reader = new Environment;
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\Environment with mailformed variable names.
     */
    public function testConfigReaderEnvironmentFail2()
    {
        $this->setExpectedException();
        $src = ['CODERAGE_HELLO__123' => 'xyz'];
        self::setEnvironment($src);
        $reader = new Environment;
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\Environment with mailformed variable names.
     */
    public function testConfigReaderEnvironmentFail3()
    {
        $this->setExpectedException();
        $src = ['CODERAGE_HELLO____123' => 'xyz'];
        self::setEnvironment($src);
        $reader = new Environment;
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\Environment with mailformed variable names.
     */
    public function testConfigReaderEnvironmentFail4()
    {
        $this->setExpectedException();
        $src = ['CODERAGE_HELLO__HOW_ARE_YOU__' => 'xyz'];
        self::setEnvironment($src);
        $reader = new Environment;
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine.
     */
    public function testConfigReaderFileIni()
    {
        $run = self::newRun();
        $vars =
            [
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'hello.how_are_you.really' => 'ok',
                'howdy_pardner' => 'Greetings!'
            ];
        $ini = self::newIniFile($vars);
        $reader = new File(self::newRun(), $ini);
        $config = $reader->read();
        self::checkConfigurations($config, $vars);
        foreach ($config->propertyNames() as $n) {
            $p = $config->lookupProperty($n);
            assert('$p->isList() == false');
            assert('$p->required() == false');
            assert('$p->sticky() == false');
            assert('$p->obfuscate() == false');
            assert('$p->specifiedAt() == 0');
            assert("realpath(\$p->setAt()) == realpath('$ini')");
        }
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFileIniFail1()
    {
        $this->setExpectedException();
        $src = ['123' => 'xyz'];
        $ini = self::newIniFile($src);
        $reader = new File(self::newRun(), $ini);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFileIniFail2()
    {
        $this->setExpectedException();
        $src = ['hello.123' => 'xyz'];
        $ini = self::newIniFile($src);
        $reader = new File(self::newRun(), $ini);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFileIniFail3()
    {
        $this->setExpectedException();
        $src = ['hello..123' => 'xyz'];
        $ini = self::newIniFile($src);
        $reader = new File(self::newRun(), $ini);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFileIniFail4()
    {
        $this->setExpectedException();
        $src = ['hello.how_are_you.' => 'xyz'];
        $ini = self::newIniFile($src);
        $reader = new File(self::newRun(), $ini);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\CommandLine.
     */
    public function testConfigReaderFilePhp()
    {
        $vars =
            [
                'hello' => 'hi',
                'hello_how_are_you' => 'fine, thanks',
                'hello_how_are_you_really' => 'ok',
                'howdy_pardner' => 'Greetings!'
            ];
        foreach (['php', ''] as $ext) {
            $php = self::newPhpConfigFile($vars, $ext);
            $reader = new File(self::newRun(), $php);
            $config = $reader->read();
            self::checkConfigurations($config, $vars);
            foreach ($config->propertyNames() as $n) {
                $p = $config->lookupProperty($n);
                assert('$p->isList() == false');
                assert('$p->required() == false');
                assert('$p->sticky() == false');
                assert('$p->obfuscate() == false');
                assert('$p->specifiedAt() == 0');
                assert("realpath(\$p->setAt()) == realpath('$php')");
            }
        }
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFilePhpFail1()
    {
        $this->setExpectedException();
        $src = ['123' => 'xyz'];
        $php = self::newPhpConfigFile($src);
        $reader = new File(self::newRun(), $php);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFilePhpFail2()
    {
        $this->setExpectedException();
        $src = ['hello.123' => 'xyz'];
        $php = self::newPhpConfigFile($src);
        $reader = new File(self::newRun(), $php);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFilePhpFail3()
    {
        $this->setExpectedException();
        $src = ['hello..123' => 'xyz'];
        $php = self::newPhpConfigFile($src);
        $reader = new File(self::newRun(), $php);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with mailformed variable names.
     */
    public function testConfigReaderFilePhpFail4()
    {
        $this->setExpectedException();
        $src = ['hello.how_are_you.' => 'xyz'];
        $php = self::newPhpConfigFile($src);
        $reader = new File(self::newRun(), $php);
        $config = $reader->read();
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with an XML config file.
     */
    public function testConfigReaderFileXml1()
    {
        $xml = self::newSampleConfigFile();
        $reader = new File(self::newRun(), $xml);
        $config = $reader->read();
        self::checkSampleConfiguration($config);
    }

    /**
     * Tests CodeRage\Build\Config\Reader\File with an XML config file using base 64
     * and a custom separator character.
     */
    public function testConfigReaderFileXml2()
    {
        $vars =
            [
                'one' =>  "A\nB\nC\n",
                'two' => ["A\n", "B\n", "C\n"],
                'three' => ['a,b,c', 'd;e;f', 'g:h:i']
            ];
        $namespace = NAMESPACE_URI;
        $content =
            "<config xmlns='$namespace'>" .
            "<property name='one' list='1' encoding='base64' " .
            "  value='" . base64_encode($vars['one']) . "'/>" .
            "<property name='two' list='1' encoding='base64' " .
            "  value='" .
                 join(' ', array_map('base64_encode', $vars['two'])) . "'/>" .
            "<property name='three' list='1' separator='|' " .
            "  value='" . join('|', $vars['three']) . "'/>" .
            "</config>";
        $xml = self::newXmlFile($content);
        $reader = new File(self::newRun(), $xml);
        $config = $reader->read();
        self::checkConfigurations($config, $vars);
    }

    /**
     * Tests CodeRage\Build\Config\Writer\Perl.
     */
    public function testConfigWriterPerl()
    {
        // Generate conrfiguration
        $vars =
            [
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'hello.how_are_you.really' => 'ok',
                'howdy_pardner' => 'Greetings!',
                'greetings' => ["how\n", "are\n", "you?\n"],
            ];
        $config = self::newConfiguration($vars);
        $writer = new \CodeRage\Build\Config\Writer\Perl;
        $perl = temp();
        $writer->write($config, $perl);

        // Run Perl script to read the configuration and transalte it to XML
        $script = dirname(__FILE__) . '/dump-config.pl';
        ob_start();
        $status = null;
        $command = 'perl' . ' ' . escapeShellArg($script) . ' ' .
            escapeShellArg($perl);
        @system($command, $status);
        $content = ob_get_contents();
        ob_end_clean();
        if ($status != 0)
            throw new Error(['message' => "Failed executing command '$command'"]);

        // Parse XML configuration
        $xml = temp('', 'xml');
        file_put_contents($xml, $content);
        $reader = new File(self::newRun(), $xml);

        self::checkConfigurations($vars, $reader->read());
    }

    /**
     * Tests CodeRage\Build\Config\Writer\Php.
     */
    public function testConfigWriterPhp()
    {
        $vars =
            [
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'hello.how_are_you.really' => 'ok',
                'howdy_pardner' => 'Greetings!',
                'a' => true,
                'b' => false,
                'c' => 5,
                'd' => -99,
                'e' => -0.0,
                'f' => .7384,
                'g' => [5, -99],
                'h' => [-0.0, .7384],
                'i' => ["A\n", "B\n", "C\n"],
            ];
        $props = self::newConfiguration($vars);
        $writer = new \CodeRage\Build\Config\Writer\Php;
        $php = temp();
        $writer->write($props, $php);
        include($php);
        if (!isset($config))
            throw new Error(['message' => "Missing runtime configuration"]);
        self::checkConfigurations($props, $config);
    }

    /**
     * Tests CodeRage\Build\Config\Writer\Xml.
     */
    public function testConfigWriterXml()
    {
        // Generate conrfiguration
        $vars =
            [
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'hello.how_are_you.really' => 'ok',
                'howdy_pardner' => 'Greetings!',
                'a' => true,
                'b' => false,
                'c' => 5,
                'd' => -99,
                'e' => -0.0,
                'f' => .7384,
                'g' => [5, -99],
                'h' => [-0.0, .7384],
                'i' => ["A\n", "B\n", "C\n"],
            ];
        $config = self::newConfiguration($vars);
        $writer = new \CodeRage\Build\Config\Writer\Xml;
        $xml = temp('', 'xml');
        $writer->write($config, $xml);

        // Run Perl script to read the configuration and transalte it to XML
        $reader = new File(self::newRun(), $xml);

        self::checkConfigurations($vars, $reader->read());
    }

    /**
     * Tests CodeRage\Build\Config\Compound.
     */
    public function testCompoundConfig1()
    {
        // Generate conrfiguration
        $first =
            [
                'a' => 1,
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks'
            ];
        $second =
            [
                'b' => 2,
                'hello' => 'thank you',
                'howdy_pardner' => 'Greetings!'
            ];
        $third =
            [
                'c' => 3,
                'hello.how_are_you' => 'ok',
                'howdy_pardner' => 'Good evening!'
            ];
        $fourth =
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
                'hello' => 'hi',
                'hello.how_are_you' => 'fine, thanks',
                'howdy_pardner' => 'Greetings!'
            ];
        $compound =
            new Compound([
                    self::newConfiguration($first),
                    self::newConfiguration($second),
                    self::newConfiguration($third)
                ]);
        self::checkConfigurations($compound, $fourth);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a string in the
     * environment and specified as an int in a configuration file.
     */
    public function testCompoundConfig2()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', INT,
                    null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_, '12345',
                    ENVIRONMENT, ENVIRONMENT
                )
        );
        $compound = new Compound([$first, $second]);
        assert('$compound->lookupProperty("test")->value() === 12345');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a string in the
     * environment and specified as a list in a configuration file.
     */
    public function testCompoundConfig3()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', LIST_,
                    null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_, 'hello world',
                    ENVIRONMENT, ENVIRONMENT
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === "hello" && $value[1] === "world"');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a string in the
     * environment and specified as an int and a list in a configuration file.
     */
    public function testCompoundConfig4()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', INT | LIST_,
                    null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_, '1 2',
                    ENVIRONMENT, ENVIRONMENT
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === 1 && $value[1] === 2');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a string in the
     * environment and specified and supplied as an int in a configuration file.
     */
    public function testCompoundConfig5()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', INT | ISSET_,
                    54321, 'path1', 'path1'
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_, '12345',
                    ENVIRONMENT, ENVIRONMENT
                )
        );
        $compound = new Compound([$first, $second]);
        assert('$compound->lookupProperty("test")->value() === 54321');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a string in the
     * environment and specified and supplied as a list in a configuration file.
     */
    public function testCompoundConfig6()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', LIST_ | ISSET_,
                    ['thank', 'you'], 'path1', 'path1'
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_, 'hello world',
                    ENVIRONMENT, ENVIRONMENT
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === "thank" && $value[1] === "you"');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value specified as an int in a
     * configuration file but supplied on the command line.
     */
    public function testCompoundConfig7()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_, '12345',
                    COMMAND_LINE, COMMAND_LINE
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', INT, null, 'path1', null
                )
        );
        $compound = new Compound([$first, $second]);
        assert('$compound->lookupProperty("test")->value() === 12345');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value specified as a list in a
     * configuration file but supplied on the command line.
     */
    public function testCompoundConfig8()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_, 'hello world',
                    COMMAND_LINE, COMMAND_LINE
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', LIST_, null, 'path1', null
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === "hello" && $value[1] === "world"');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value specified as an int and a
     * list in a configuration file but supplied on the command line.
     */
    public function testCompoundConfig9()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_, '1 2',
                    COMMAND_LINE, COMMAND_LINE
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', INT | LIST_, null, 'path1', null
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === 1 && $value[1] === 2');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a list in a
     * configuration file and overridden on the command line.
     */
    public function testCompoundConfig10()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_, 'hello world',
                    COMMAND_LINE, COMMAND_LINE
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', LIST_ | ISSET_,
                    ['thank', 'you'], 'path1', 'path1'
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === "hello" && $value[1] === "world"');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a value supplied as a list of ints
     * in a configuration file but overridden on the command line.
     */
    public function testCompoundConfig11()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_, '1 2',
                    COMMAND_LINE, COMMAND_LINE
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', INT | LIST_ | ISSET_,
                    [5, 4, 3, 2, 1], 'path1', 'path1'
                )
        );
        $compound = new Compound([$first, $second]);
        $value = $compound->lookupProperty("test")->value();
        assert('$value[0] === 1 && $value[1] === 2');
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a required value specified in a
     * configuration file and set in the environment.
     */
    public function testCompoundConfig12()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', INT | REQUIRED,
                    null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_, '12345',
                    ENVIRONMENT, ENVIRONMENT
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with a required value specified in a
     * configuration file and set on the command line.
     */
    public function testCompoundConfig13()
    {
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_, '12345',
                    COMMAND_LINE, COMMAND_LINE
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', INT | REQUIRED,
                    null, 'path1', null
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail1()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', INT | LIST_,
                    null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', INT, null, 'path2', null
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail2()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', INT, null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', INT | LIST_,
                    null, 'path2', null
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail3()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_ | INT | LIST_,
                    [1, 2, 3], 'path1', 'path1'
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_ | INT, 1, 'path2', 'path2'
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail4()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_ | INT, 1, 'path1', 'path1'
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_ | INT | LIST_,
                    [1, 2, 3], 'path2', 'path2'
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail5()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', BOOLEAN, null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', STRING, null, 'path2', null
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail6()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', STRING, null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', BOOLEAN, null, 'path2', null
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail7()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_ | INT,
                    1, 'path1', 'path1'
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_ | FLOAT,
                    1.0, 'path2', 'path2'
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with inconsistent specifications.
     */
    public function testCompoundConfigFail8()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', ISSET_ | FLOAT,
                    1.0, 'path1', 'path1'
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', ISSET_ | INT,
                    1, 'path2', 'path2'
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Tests CodeRage\Build\Config\Compound with missing required property.
     */
    public function testCompoundConfigFail9()
    {
        $this->setExpectedException();
        $first = new Basic;
        $first->addProperty(
            new Property(
                    'test', BOOLEAN, null, 'path1', null
                )
        );
        $second = new Basic;
        $second->addProperty(
            new Property(
                    'test', BOOLEAN | REQUIRED,
                    null, 'path1', null
                )
        );
        $compound = new Compound([$first, $second]);
    }

    /**
     * Throws an exception if the given collections of properties
     * are not equivalent.
     *
     * @param mixed $lhs An instance of CodeRage\Build\ProjectConfig or an associative
     * array
     * @param mixed $rhs An instance of CodeRage\Build\ProjectConfig or an associative
     * array
     * @throws CodeRage\Error
     */
    private function checkConfigurations($lhs, $rhs, $message = null)
    {
        if (!$message)
            $message = 'Configurations differ';
        if (is_object($lhs) && is_object($rhs)) {
            self::checkConfigurations(
                self::extractProperties($lhs),
                self::extractProperties($rhs),
                $message
            );
            foreach ($lhs->propertyNames() as $n) {
                self::checkProperties(
                    $lhs->lookupProperty($n),
                    $rhs->lookupProperty($n),
                    $message
                );
            }
        } else {
            if (is_object($lhs))
                $lhs = self::extractProperties($lhs);
            else if (is_object($rhs))
                $rhs = self::extractProperties($rhs);
            if ( sizeof($lhs) != sizeof($rhs) ||
                 sizeof(array_diff($lhs, $rhs)) > 0 ||
                 sizeof(array_diff($lhs, $rhs)) > 0 )
            {
                echo "ERROR: Configurations differ:\n";
                self::printConfiguration('first', $lhs);
                self::printConfiguration('second', $rhs);
                throw new Error(['message' => $message]);
            }
        }
    }

    /**
     * Throws an exception if the given properties are not equivalent; $lsh and
     * $rhs are assumed to have the same name.
     *
     * @param CodeRage\Build\Config\Property $lhs
     * @param CodeRage\Build\Config\Property $rhs
     * @throws CodeRage\Error
     */
    private function checkProperties($lhs, $rhs, $message)
    {
        if ($lhs->type() != $rhs->type()) {
            echo "Difference detected in property '" . $lhs->name() .
                 "': first = " .
                 Property::translateType($lhs->type()) .
                 '; second = ' .
                 Property::translateType($rhs->type()) . "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->isList() != $rhs->isList()) {
            echo "Difference detected in property '" . $lhs->name() .
                 "': " .
                 ( $lhs->isList() ?
                       "first is a list but second is not" :
                       "second is a list but first is not" ) .
                 "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->required() != $rhs->required()) {
            echo "Difference detected in property '" . $lhs->name() .
                 "': " .
                 ( $lhs->required() ?
                       "first is required but second is not" :
                       "second is required but first is not" ) .
                 "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->sticky() != $rhs->sticky()) {
            echo "Difference detected in property '" . $lhs->name() . "': " .
                 ( $lhs->sticky() ?
                       "first is sticky but second is not" :
                       "second is sticky but first is not" ) .
                 "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->obfuscate() != $rhs->obfuscate()) {
            echo "Difference detected in property '" . $lhs->name() . "': " .
                 ( $lhs->obfuscate() ?
                       "first is obfuscated but second is not" :
                       "second is obfuscated but first is not" ) .
                 "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->specifiedAt() !== $rhs->specifiedAt()) {
            echo "Difference detected in property '" . $lhs->name() . "': " .
                 "first is specified at " .
                 Property::translateLocation(
                     $lhs->specifiedAt()
                 ) .
                 "; second is specified at " .
                 Property::translateLocation(
                     $rhs->specifiedAt()
                 ) .
                 "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->isSet() != $rhs->isSet()) {
             echo "Difference detected in property '" . $lhs->name() . "': " .
                 ( $lhs->isSet() ?
                       "first is set but second is not" :
                       "second is set but first is not" ) .
                 "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->setAt() !== $rhs->setAt()) {
             echo "Difference detected in property '" . $lhs->name() . "': " .
                  "first is set at " .
                  Property::translateLocation($lhs->setAt()) .
                  "; second is specified at " .
                  Property::translateLocation($rhs->setAt()) .
                  "\n";
            throw new Error(['message' => $message]);
        }
        if ($lhs->isSet() && $lhs->value() !== $rhs->value()) {
             echo "Difference detected in property '" . $lhs->name() . "': " .
                  "first=" . self::printValue($lhs->value) . '; second=' .
                  self::printValue($rhs->value()) . "\n";
            throw new Error(['message' => $message]);
        }
    }

    /**
     * Prints the contents of the given collection of properties to standard
     * output.
     *
     * @param string $label
     * @param mixed $properties
     */
    private static function printConfiguration($label, $config)
    {
        if (is_object($config))
            $config = self::extractProperties($config);
        $items = [];
        foreach ($config as $n => $v)
            $items[] = "$n: " . self::printValue($v);
        echo "$label = [" . join('; ', $items) . "]\n";
    }

    /**
     * Returns a string representation of the given property value.
     *
     * @param mixed $value A scalar, indexed array, or instance of
     * CodeRage\Build\Config\Property.
     * @return string
     */
    private static function printValue($value)
    {
        if (is_object($value))
            $value = $value->value();
        return is_array($value) ?
            join(',', array_map('CodeRage\Util\printScalar', $value)) :
            printScalar($value);
    }

    /**
     * Returns an associative array consisting of the properties of the given
     * configuration.
     *
     * @param CodeRage\Build\ProjectConfig $config
     * @return array
     */
    private static function extractProperties(\CodeRage\Build\ProjectConfig $config)
    {
        $properties = [];
        foreach ($config->propertyNames() as $n)
            if ($p = $config->lookupProperty($n))
                $properties[$n] = $p->value();
        return $properties;
    }

    /**
     * Returns an instance of CodeRage\Build\CommandLine with an -s option for each
     * variable in the given collection.
     *
     * @param array $vars An associative array.
     */
    private static function newCommandLine($vars)
    {
        $argv = ['program'];
        foreach ($vars as $n => $v) {
            $argv[] = '--set';
            $argv[] = "$n=$v";
        }
        $cmd = new \CodeRage\Build\CommandLine;
        $cmd->parse(false, $argv);
        return $cmd;
    }

    /**
     * Updates $_SERVER and the environment to reflect the given collection of
     * values.
     *
     * @param array $vars An associative array.
     */
    private static function setEnvironment($vars)
    {
        foreach ($_SERVER as $n => $v)
            if (($e = getenv($n)) !== false && $e === $v)
                unset($_SERVER[$n]);
        foreach ($vars as $n => $v) {
            $_SERVER[$n] = $v;
            putenv("$n=$v");
        }
    }

    /**
     * Writes the given array of variables to a temporary ini file and returns
     * the file pathname.
     *
     * @param array $vars An associative array.
     */
    private static function newIniFile($vars)
    {
        $content = '';
        foreach ($vars as $n => $v)
            $content .= "$n=" . printScalar($v) . "\n";
        $ini = temp('', 'ini');
        generate($ini, $content, 'ini');
        return $ini;
    }

    /**
     * Writes the given array of variables to a temporary PHP project config
     * file and returns the file pathname.
     *
     * @param array $vars An associative array.
     * @param string $ext The file extension, if any.
     */
    private static function newPhpConfigFile($vars, $ext = null)
    {
        $content = '';
        $count = 0;
        foreach ($vars as $n => $v) {
            $v = printScalar($v);
            $p1 = ($count % 2) == 0 ? '' : ' ';
            $p2 = ($count / 2 % 2) == 0 ? '' : ' ';
            $content .= "\$CFG_$n$p1=$p2$v;\n";
            ++$count;
        }
        $php = temp('', $ext);
        generate($php, $content, 'php');
        return $php;
    }

    /**
     * Writes the given XML to a temporary file and returns the file pathname.
     *
     * @param string $content. The document element.
     */
    private static function newXmlFile($content)
    {
        $xml = temp('', 'xml');
        generate($xml, $content, 'xml');
        return $xml;
    }

    /**
     * Generates a temporary XML config file containing sample values and
     * returns the file pathname.
     *
     * @param array $vars An associative array.
     * @param string $ext The file extension, if any.
     */
    private static function newSampleConfigFile()
    {
        $namespace = NAMESPACE_URI;
        $content =
            "<config xmlns='$namespace'>" .
            "<property name='bool1' type='boolean' value='1'/>" .
            "<property name='bool2' type='boolean' value='true'/>" .
            "<property name='bool3' type='boolean' value='yes'/>" .
            "<property name='bool4' type='boolean' value='0'/>" .
            "<property name='bool5' type='boolean' value='FALSE'/>" .
            "<property name='bool6' type='boolean' value='No'/>" .
            "<property name='int1' type='int' value='99'/>" .
            "<property name='int2' type='int' value='-5'/>" .
            "<property name='float1' type='float' value='1028.88'/>" .
            "<property name='float2' type='float' value='-0'/>" .
            "<property name='float3' type='float' value='1.'/>" .
            "<property name='float4' type='float' value='-799.83'/>" .
            "<property name='float5' type='float' value='.2202381'/>" .
            "<property name='float6' type='float' value='10000'/>" .
            "<property name='string1' value='hello'/>" .
            "<property name='string2' type='string' value='hello'/>" .
            "<property name='list1' list='1' type='boolean' value='FALSE'/>" .
            "<property name='list2' list='1' type='boolean' value='1 0  1 '/>" .
            "<property name='list3' list='1' type='int' value='-55 76 1000'/>" .
            "<property name='list4' list='1' type='float' value='-.88 10.5'/>" .
            "<property name='list5' list='1' value='  thank you  very much'/>" .
            "<property name='list6' list='1' type='string' value='1 2 3'/>" .
            "<group name='outer'>" .
            "  <property name='inner' value='1'/>" .
            "</group>" .
            "<group name='outer.middle'>" .
            "  <property name='inner' value='2'/>" .
            "</group>" .
            "<group name='outer'>" .
            "  <property name='middle.inner2' value='3'/>" .
            "</group>" .
            "<group name='outer'>" .
            "  <group name='middle'>" .
            "    <property name='inner3' value='4'/>" .
            "  </group>" .
            "</group>" .
            "</config>";
        return self::newXmlFile($content);
    }

    /**
     * Throws an exception if the given configuration does not consist of the
     * properties defined in the sample XML config fle returned
     * by newSampleConfigFile().
     *
     * @param CodeRage\Build\ProjectConfig $config
     * @throws CodeRage\Error
     */
    private static function checkSampleConfiguration($config)
    {
        $lookup = '$config->lookupProperty';
        assert("$lookup('bool1')->value() === true");
        assert("$lookup('bool2')->value() === true");
        assert("$lookup('bool3')->value() === true");
        assert("$lookup('bool4')->value() === false");
        assert("$lookup('bool5')->value() === false");
        assert("$lookup('bool6')->value() === false");
        assert("$lookup('int1')->value() === 99");
        assert("$lookup('int2')->value() === -5");
        assert("$lookup('float1')->value() === 1028.88");
        assert("$lookup('float2')->value() === -0.0");
        assert("$lookup('float3')->value() === 1.");
        assert("$lookup('float4')->value() === -799.83");
        assert("$lookup('float5')->value() === .2202381");
        assert("$lookup('float6')->value() === 10000.0");
        assert("$lookup('string1')->value() === 'hello'");
        assert("$lookup('string2')->value() === 'hello'");
        assert("$lookup('list1')->value() === array(false)");
        assert("$lookup('list2')->value() === array(true, false, true)");
        assert("$lookup('list3')->value() === array(-55, 76, 1000)");
        assert("$lookup('list4')->value() === array(-.88, 10.5)");
        assert(
            "$lookup('list5')->value() === " .
            "array('thank', 'you', 'very', 'much')"
        );
        assert("$lookup('list6')->value() === array('1', '2', '3')");
        assert("$lookup('outer.inner')->value() === '1'");
        assert("$lookup('outer.middle.inner')->value() === '2'");
        assert("$lookup('outer.middle.inner2')->value() === '3'");
        assert("$lookup('outer.middle.inner3')->value() === '4'");
    }

    /**
     * Returns an instance of CodeRage\Build\ProjectConfig based on the given
     * array of variables.
     *
     * @param array $vars An associative array.
     */
    private static function newConfiguration($vars)
    {
        $config = new Basic;
        foreach ($vars as $name => $val) {
            $flags = ISSET_;
            if (is_array($val))
                $flags |= LIST_;
            if (is_array($val) && sizeof($val) == 0) {
                $flags |= STRING;
            } else {
                switch (is_array($val) ? gettype($val[0]) : gettype($val)) {
                case 'boolean':
                    $flags |= BOOLEAN;
                    break;
                case 'integer':
                    $flags |= INT;
                    break;
                case 'double':
                    $flags |= FLOAT;
                    break;
                case 'string':
                default:
                    $flags |= STRING;
                    break;
                }
            }
            $p = new Property($name, $flags, $val, null, null);
            $config->addProperty($p);
        }
        return $config;
    }

    /**
     * Returns a newly constructed instance of CodeRage\Build\Run.
     *
     * @return CodeRage\Build\Run $run
     */
    private static function newRun()
    {
        // Create command line
        $cmd = new \CodeRage\Build\CommandLine;
        $cmd->parse(false, ['program']);

        // Create log
        $log = $cmd->createLog('');

        // Create build configuration
        $config = Config::current();
        $buildConfig =
            new \CodeRage\Build\BuildConfig(
                    time(), 'build', null, null, null,
                    $config->getRequiredProperty('tools_root'), null,
                    null, null, [], null, null, null,
                    null, null, [], [], null
                );

        // Create run
        $run =
            new \CodeRage\Build\Run(
                    $config->getRequiredProperty('project_root'), $cmd, $log,
                    new \CodeRage\Build\NullDialog, $buildConfig,
                    new Basic([])
                );

        return $run;
    }
}
