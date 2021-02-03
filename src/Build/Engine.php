<?php

/**
 * Defines the class CodeRage\Build\Engine
 *
 * File:        CodeRage/Build/Engine.php
 * Date:        Thu Dec 10 23:30:32 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use Throwable;
use CodeRage\Build\Config\Reader\Array_ as ArrayReader;
use CodeRage\Build\Config\Reader\File as FileReader;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Log;
use CodeRage\Text;
use CodeRage\Util\Args;
use CodeRage\Util\ErrorHandler;
use CodeRage\Util\Time;
use CodeRage\Xml;

/**
 * Executes build operations
 */
final class Engine extends \CodeRage\Util\BasicProperties {

    /**
     * @var string
     */
    public const CONFIG_FILE = 'project.xml';

    /**
     * The default log level for console logging.
     *
     * @var int
     */
    private const LOG_CONSOLE_LEVEL = Log::INFO;

    /**
     * Value of the 'stream' parameter to the CodeRage\Log\Provider\Console
     * constructor
     *
     * @var int
     */
    private const LOG_CONSOLE_STREAM = 'stderr';

    /**
     * Value of the 'format' parameter to the CodeRage\Log\Provider\Console
     * constructor
     *
     * @var int
     */
    private const LOG_CONSOLE_FORMAT = 0;

    /**
     * The log level for entry counting.
     *
     * @var int
     */
    private const LOG_COUNTER_LEVEL = Log::WARNING;

    /**
     * Path, relative to the project root directory, of the list of generated
     * files
     *
     * @var string
     */
    private const GENERATED_FILE_LOG = '.coderage/files.log';

    /**
     * Constructs an instance of CodeRage\Build\Engine
     *
     * @param array $options The options array; supports the following options:
     *     log - An instance of CodeRage\Log (optional)
     */
    public function __construct(array $options = [])
    {
        Args::checkKey($options, 'log', 'CodeRage\Log');
        $this->projectRoot = \CodeRage\Config::projectRoot();
        self::initializeLog($options);
    }

    /**
     * Return the log
     *
     * @return CodeRage\Log
     */
    public function log() : Log
    {
        return $this->log;
    }

    /**
     * Return the project configuratio
     *
     * @return CodeRage\Build\ProjectConfig
     */
    public function projectConfig() : ?ProjectConfig
    {
        return $this->projectConfig;
    }

    /**
     * Return the collection of modules
     *
     * @return CodeRage\Build\ModuleStore
     */
    public function moduleStore() : ?ModuleStore
    {
        return $this->moduleStore;
    }

    /**
     * Stores $path in the list of generated files.
     *
     * @param string $path
     */
    public function recordGeneratedFile($path)
    {
        if ($str = $this->log->getStream(Log::DEBUG))
            $str->write("Recording generated file: $path");
        if (!File::isAbsolute($path))
            throw new
                Error([
                    'message' =>
                        "Failed recording generated file: expected absolute " .
                        "path; found $path"
                ]);
        $this->files[] = realpath($path);
    }

    /**
     * Sets and/or unsets configuration variables
     *
     * @param array $options The options array; supports the following opt
     *     setProperties - An associative array of string-valued configuration
     *       variables to set (optional)
     *     unsetProperties - A list of names of configuration variables to unset
     *       (optional):
     * @return boolean
     */
    public function config(array $options)
    {
        self::processOptions($options);
        $set = $options['setProperties'];
        $unset = $options['unsetProperties'];
        if (!empty($set) || !empty($unset)) {
            return $this->execute(function() { return true; }, [
                'setProperties' => $set,
                'unsetProperties' => $unset,
                'logErrorCount' => true
            ]);
        } else {
            $this->log()->logError("Missing configuration variables");
            return false;
        }
    }

    /**
     * Removes generated files
     *
     * @return boolean
     */
    public function clean()
    {
        return
            $this->execute(function($engine, $options) {
                $engine->cleanImpl();
            }, [
                'updateConfig' => false
            ]);
    }

    /**
     * Removes all build artifacts, including the build history and any
     * configuration variables specified on the command line
     *
     * @return boolean
     */
    public function reset()
    {
        return
            $this->execute(function($engine, $options) {
                $engine->cleanImpl();
                File::rm($this->projectRoot . '/.coderage');
            }, [
                'updateConfig' => false
            ]);
    }

    /**
     * Executes the build event "build"
     *
     * @param array $options The options array; supports the following opt
     *     setProperties - An associative array of string-valued configuration
     *       variables to set (optional)
     *     unsetProperties - A list of names of configuration variables to unset
     *       (optional):
     *     logErrorCount - true to log the error count; defaults to true
     * @return boolean
     */
    public function build(array $options = [])
    {
        self::processOptions($options);
        return $this->execute(function($engine, $options) {
            $options['updateConfig'] = true;
            $engine->buildImpl('build', $options);
        }, $options);
    }

    /**
     * Executes the build event "install"
     *
     * @return boolean
     */
    public function install()
    {
        return $this->execute(function($engine, $options) {
            $engine->buildImpl('install', $options);
        });
    }

    /**
     * Executes the build event "sync"
     *
     * @return boolean
     */
    public function sync()
    {
        return $this->execute(function($engine, $options) {
            $engine->buildImpl('sync', $options);
        });
    }

    /**
     * Executes a build action
     *
     * @param callable $action The build action, as a callable taking two
     *   arguments of types CodeRage\Build\Engine and array
     * @param array $options The options array; supports the following opt
     *     setProperties - An associative array of string-valued configuration
     *       variables to set (optional)
     *     unsetProperties - A list of names of configuration variables to unset
     *       (optional):
     *     updateConfig - true to update the configuration; defaults to true
     *     logErrorCount - true to log the error count; defaults to true
     * @return bool
     */
    public function execute(callable $action, array $options = []) : bool
    {
        $this->processOptions($options);

        // Clear state
        $this->projectConfig = $this->moduleStore = null;
        $this->files = [];

        // Add counter to log
        $logCount = $options['logErrorCount'];
        $counter = null;
        if ($logCount) {
            $counter = new \CodeRage\Log\Provider\Counter;
            $this->log->registerProvider($counter, self::LOG_COUNTER_LEVEL);
        }

        // Perform main work
        $status = true;
        try {
            $this->projectConfig = $this->loadProjectConfig();
            $this->moduleStore = ModuleStore::load($this);
            if ($options['updateConfig'])
                $this->updateConfig($options);
            $action($this, $options);
        } catch (Throwable $e) {
            $status = false;
            $this->log->logError($e);
        } finally {

            // Clear state
            $this->projectConfig = $this->moduleStore = null;
            $this->files = [];

            // Remove counter
            if ($counter !== null)
                $this->log->unregisterProvider($counter);
        }

        if ($logCount) {
            $count = $counter->getCount();
            $this->log->logMessage("There were $count error(s)");
        }

        return $status;
    }

    /**
     * Validates and processes options for execute()
     *
     * @param array $options The options array passed to execute()
     */
    private function processOptions(array &$options) : void
    {
        Args::checkKey($options, 'setProperties', 'map[string]', [
            'default' => []
        ]);
        Args::checkKey($options, 'unsetProperties', 'list[string]', [
            'default' => []
        ]);
        Args::checkBooleanKey($options, 'updateConfig', [
            'default' => true
        ]);
        Args::checkBooleanKey($options, 'logErrorCount', [
            'default' => true
        ]);
    }

    /**
     * Initializes the log
     *
     * @param array $options The options array passed to the constructor, after
     *   processing
     */
    private function initializeLog(array $options) : void
    {
        $log = $options['log'] ?? null;
        if ($log === null) {
            $log = new Log;
            $provider =
                new \CodeRage\Log\Provider\Console([
                        'stream' => self::LOG_CONSOLE_STREAM,
                        'format' => self::LOG_CONSOLE_FORMAT
                    ]);
            $log->registerProvider($provider, self::LOG_CONSOLE_LEVEL);
        }
        $this->log = $log;
    }

    /**
     * Removes generated files
     */
    private function cleanImpl() : void
    {
        foreach ($this->moduleStore->modules() as $module) {
            $this->log->logMessage('Processing module ' . $module->name());
            $module->clean($this);
        }
        $this->log->logMessage('Removing generated files');
        $path = $this->projectRoot . '/' . self::GENERATED_FILE_LOG;
        if (!file_exists($path))
            return;
        File::checkFile($path, 0b0110);
        $files = explode("\n", rtrim(file_get_contents($path)));
        for ($z = sizeof($files) - 1; $z != -1; --$z) {
            $f = $files[$z];
            if (file_exists($f) && unlink($f) === false) {
                $this->log->logError("Failed removing file: $f");
            } else {
                array_splice($files, $z, 1);
            }
        }
        if (count($files)) {
            file_put_contents($path, join("\n", $files));
        } else {
            File::rm($path);
        }
    }

    /**
     * Helper method for config(), build(), install(), and sync()
     *
     * @param string $event One of build, install, or sync
     * @param array $options The options array passed to execute(), after
     *   processing
     */
    private function buildImpl(string $event, array $options) : void
    {
        try {
            foreach ($this->moduleStore->modules() as $module) {
                $this->log->logMessage('Processing module ' . $module->name());
                $module->$event($this);
            }
        } finally {
            $this->recordGeneratedFiles();
        }
    }

    /**
     * Updates the build parameters and project configuration
     *
     * @param array $options The options array
     */
    private function updateConfig(array $options) : void
    {
        if ($str = $this->log->getStream(Log::INFO))
            $str->write("Updating project configuration");

        // Update configuration
        $this->projectConfig = $this->generateProjectConfig($options);
        $this->moduleStore->update();

        // Store configuration
        foreach (['xml', 'php'] as $lang) {
            if ($str = $this->log->getStream(Log::INFO))
                $str->write("Generating $lang configuration");
            $class = 'CodeRage\\Build\\Config\\Writer\\' . ucfirst($lang);
            $writer = new $class;
            $path = "$this->projectRoot/.coderage/config.$lang";
            $writer->write($this->projectConfig, $path);
            $this->recordGeneratedFile($path);
        }
        $this->moduleStore->save();
    }

    /**
     * Returns an instance of CodeRage\Build\ProjectConfig constructed from the
     * given build parameters
     *
     * @param CodeRage\Build\BuildParams $new The new build parameters
     * @param array $options The options array
     * @return CodeRage\Build\ProjectConfig
     */
    private function generateProjectConfig(array $options)
        : ProjectConfig
    {
        if ($str = $this->log->getStream(Log::INFO))
            $str->write("Generating project configuration");

        $configs = [];

        // Handle CodeRage project definition file
        $reader = new FileReader(dirname(__DIR__) . '/' . self::CONFIG_FILE);
        $configs[] = $reader->read();

        // Handle module configurations
        foreach ($this->moduleStore->modules() as $module) {
            if ($path = $module->configFile()) {
                if ($str = $this->log->getStream(Log::VERBOSE)) {
                    $str->write("Processing configuration file $path");
                }
                $reader = new FileReader($path, '[' . $module->name() . ']');
                $configs[] = $reader->read();
            }
            if (($config = $module->config()) !== null) {
                if ($str = $this->log->getStream(Log::VERBOSE)) {
                    $str->write(
                        'Processing configuration for module ' . $module->name()
                    );
                }
                $reader = new ArrayReader($config, '[' . $module->name() . ']');
                $configs[] = $reader->read();
            }
        }

        // Handle project definition file
        $path = \CodeRage\Config::projectRoot() . '/' . self::CONFIG_FILE;
        $reader = new FileReader($path);
        $configs[] = $reader->read();

        // Handle previous command-line
        $prev = $this->loadProjectConfig();
        $cmdline = new Config\Basic;
        foreach ($prev->propertyNames() as $n) {
            $p = $prev->lookupProperty($n);
            if ( $p->setAt() == '[cli]' &&
                 !in_array($n, $options['unsetProperties']) )
            {
                $cmdline->addProperty($n, $p);
            }
        }
        $configs[] = $cmdline;

        // Handle current command-line
        $cmdline = new Config\Basic;
        foreach ($options['setProperties'] as $n => $v) {
            if (!in_array($n, $options['unsetProperties'])) {
                $cmdline->addProperty($n, Property::decode($v, '[cli]'));
            }
        }
        $configs[] = $cmdline;

        // Construct configuration
        $result =  new Config\Compound(array_reverse($configs));

        return $result;
    }

    /**
     * Returns the existing project configuration, if any, and and empty
     * configuration otherwise
     *
     * @return CodeRage\Build\ProjectConfig
     */
    private function loadProjectConfig() : BuildConfig
    {
        $this->log->logMessage("Loading project configuration");
        $file = "$this->projectRoot/.coderage/config.xml";
        if (file_exists($file)) {
            $reader = new FileReader($file);
            return $reader->read();
        } else {
            return new Config\Basic;
        }
    }

    /**
     * Stores the pathnames of files generates during the current execution
     */
    private function recordGeneratedFiles()
    {
        if (sizeof($this->files) == 0)
            return;
        $path = $this->projectRoot .'/' . self::GENERATED_FILE_LOG;
        if (!file_exists($path)) {
            File::checkDirectory(dirname($path), 0b0111);
            touch($path);
        }
        $path = realpath($path);
        if ($str = $this->log->getStream(Log::VERBOSE))
            $str->write("Saving list of generated files to '$path'");
        $content = file_get_contents($path);
        foreach (explode("\n", rtrim($content)) as $f)
            $this->files[] = $f;
        $this->files = array_unique($this->files);
        $content = join("\n", $this->files);
        file_put_contents($path, $content);
    }

    /**
     * The log
     *
     * @var CodeRage\Log
     */
    private $log;

    /**
     * The path to the project root
     *
     * @var string
     */
    private $projectRoot;

    /**
     * The project configuration.
     *
     * @var CodeRage\Build\ProjectConfig
     */
    private $projectConfig;

    /**
     * The collection of modules
     *
     * @var CodeRage\Build\ModuleStore
     */
    private $moduleStore;

    /**
     * The list of generated files
     *
     * @var array
     */
    private $files = [];
}
