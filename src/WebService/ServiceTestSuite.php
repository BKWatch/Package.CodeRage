<?php

/**
 * Defines the class CodeRage\WebService\ServiceTestSuite
 *
 * File:        CodeRage/WebService/ServiceTestSuite.php
 * Date:        Mon May 14 14:56:19 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use CodeRage\Build\Config\Array_ as ArrayConfig;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Test\Operations\Case_;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Executes web service tests based on XML operation descriptions
 */
class ServiceTestSuite extends \CodeRage\Test\Suite {

    /**
     * @var array
     */
    const EXTENSIONS = ['xml'];

    /**
     * @var string
     */
    const MATCH_PROTOCOL =
        '/^(soap|xml-post|xml-get|json-post|json-get)$/';

    /**
     * @var string
     */
    const MATCH_MODE = Case_::MATCH_MODE;

    /**
     * @var string
     */
    const DEFAULT_MODE = Case_::MATCH_MODE;

    /**
     * Constructs an instance of CodeRage\WebService\ServiceTestSuite
     *
     * @param string $name The suite name
     * @param string $description The suite description
     * @param array $options the options array; supports the following options:
     *     directory - The directory containing XML documents
     *     extensions - A list of file extensions to process; defaults to a list
     *       with a single item 'xml'
     *     protocol - One of "soap", "xml-post", "xml-post", "json-post", or
     *       "json-get". May only be used if the value of the option
     *       "mode" is "test" (optional)
     *     includeXpath - An XPath expression or list of XPath expressions
     *       evaluating to 0 or 1 when applied to the parsed XML documents; if
     *       any expression evaluates to 0, the the operation will be excluded
     *       from the test suite; the prefix "x" can be used to reference the
     *       operations namespace (optional)
     *     excludeXpath - An XPath expression or list of XPath expressions
     *       evaluating evaluating to 0 or 1 when applied to the parsed XML
     *       documents; if any expression evaluates to 1, the the operation will
     *       be excluded from the test suite; the prefix "x" can be used to
     *       reference the operations namespace (optional)
     *     includePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML documents; if any
     *       expression fails to match, the the operation will be excluded
     *       from the test suite (optional)
     *     excludePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML documents; if any
     *       expression matches, the the operation will be excluded  from the
     *       test suite (optional)
     *     idekey - The IDE key for remote debugging (optional)
     *     mode - One of "test", "generate", or "list"
     */
    public function __construct($name, $description, $options)
    {
        parent::__construct($name, $description);

        // Process options
        Args::checkKey($options, 'directory', 'string', ['required' => true]);
        $directory = $options['directory'];
        if (!file_exists($options['directory']))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "No such directory: $directory"
                ]);
        if (!is_dir($directory))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The file '$directory' is not a directory"
                ]);
        Args::checkKey($options, 'extensions', 'array', [
            'default' => self::EXTENSIONS
        ]);
        Args::checkKey($options, 'mode', 'string', [
            'default' => self::DEFAULT_MODE
        ]);
        $mode = $options['mode'];
        if (!preg_match(self::MATCH_MODE, $mode))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid mode: $mode"
                ]);
        Args::checkKey($options, 'protocol', 'string', [
            'required' => $mode == 'test'
        ]);
        if (isset($options['protocol'])) {
            if (!preg_match(self::MATCH_PROTOCOL, $options['protocol']))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid protocol: {$options['protocol']}"
                    ]);
            if ($mode !== 'test')
                throw new
                    Error([
                       'status' => 'INCONSISTENT_PARAMETERS',
                        'message' =>
                            "The option 'protocol' is incompatible with mode " .
                            "'$mode'"
                    ]);
        }
        Args::checkKey($options, 'idekey', 'string', [
            'label' => 'IDE key', 'default' => null
        ]);

        // Iterate over directory
        $it = new \FilesystemIterator($directory);
        foreach ($it as $path => $info) {
            if ( !$info->isFile() ||
                 !in_array($info->getExtension(),  $options['extensions']) )
            {
                continue;
            }
            $operation =
                \CodeRage\WebService\OperationExecutor::loadOperation([
                    'path' => $path,
                    'protocol' => isset($options['protocol']) ?
                        $options['protocol'] :
                        null,
                    'includeXpath' =>
                        isset($options['includeXpath']) ?
                            $options['includeXpath'] :
                            null,
                    'excludeXpath' =>
                        isset($options['excludeXpath']) ?
                            $options['excludeXpath'] :
                            null,
                    'includeXpath' =>
                        isset($options['includeXpath']) ?
                            $options['includeXpath'] :
                            null,
                    'includePattern' =>
                        isset($options['includePattern']) ?
                            $options['includePattern'] :
                            null,
                    'excludePattern' =>
                        isset($options['excludePattern']) ?
                            $options['excludePattern'] :
                            null,
                    'idekey' => $options['idekey'],
                    'mode' => $mode
                ]);
            if ($operation !== null) {
                $this->add(new Case_($operation, $mode));
            }
        }

        $this->idekey = $options['idekey'];
    }

    /**
     * Deletes any pre-existing resources that could affect test results,
     * using the file resources.xml in the test case directory
     */
    protected function suiteInitialize()
    {
        parent::suiteInitialize();
        if ($this->idekey !== null) {
            $this->initialConfig = Config::current();
            $config =
                new ArrayConfig(
                    [Service::IDEKEY_CONFIG_VARIABLE => $this->idekey],
                    $this->initialConfig
                );
            Config::setCurrent($config);
        }
    }

    protected function suiteCleanup()
    {
        parent::suiteCleanup();
        if ($this->initialConfig !== null)
            Config::setCurrent($this->initialConfig);
    }

    /**
     * The IDE key, for remote debugging
     *
     * @var string
     */
    private $idekey;


    /**
     * The current configuration at the time of suite execution, to be
     * reinstalled as the current configuration when the suite terminates
     *
     * @var CodeRage\Config
     */
    private $initialConfig;
}
