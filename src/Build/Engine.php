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
use CodeRage\Config;
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
        $this->projectRoot = Config::projectRoot();
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
     * Return the build configuration
     *
     * @return CodeRage\Build\BuildConfig
     */
    public function buildConfig() : ?BuildConfig
    {
        return $this->buildConfig;
    }

    /**
     * Return the project configuratio
     *
     * @return CodeRage\Build\ProjectConfig
     */
    public function projctConfig() : ?ProjectConfig
    {
        return $this->projctConfig;
    }

    /**
     * Removes generated files
     */
    public function clean()
    {
        $this->execute(function($engine, $options) {
            $engine->cleanImpl();
        });
    }

    /**
     * Removes all build artifacts, including the build history and any
     * configuration variables specified on the command line
     */
    public function reset()
    {
        $this->execute(function($engine, $options) {
            $engine->cleanImpl();
            File::rm($this->projectRoot . '/.coderage');
        });
    }

    /**
     * Executes the build event "build"
     */
    public function build()
    {
        $this->execute(function($engine, $options) {
            $engine->buildImpl('build', $options);
        });
    }

    /**
     * Executes the build event "install"
     */
    public function install()
    {
        $this->execute(function($engine, $options) {
            $engine->buildImpl('install', $options);
        });
    }

    /**
     * Executes the build event "sync"
     */
    public function sync()
    {
        $this->execute(function($engine, $options) {
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
     *       (optional)ions:
     *     logErrorCount - true to log the error count; defaults to true
     * @return bool
     */
    public function execute(callable $action, array $options = []) : bool
    {
        $this->processsOptions($options);

        // Clear state
        $this->buildConfig = $this->projectConfig = $this->targets = null;
        $this->files = [];

        // Add counter to log
        $logCount = $options['logErrorCount'];
        $counter = null;
        if ($outputCount) {
            $counter = new \CodeRage\Log\Provider\Counter;
            $this->log->registerProvider($counter, self::LOG_COUNTER_LEVEL);
        }

        // Perform main work
        $status = true;
        try {
            $action($this, $options);
        } catch (Throwable $e) {
            $status = false;
            $this->log->logError($e);
        } finally {

            // Clear state
            $this->buildConfig = $this->projectConfig = $this->targets = null;
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
     * @param array $options The options array passed to the constructor
     */
    private function processOptions(array &$options) : void
    {
        Args::checkKey($options, 'setProperties', 'map[string]');
        Args::checkKey($options, 'unsetProperties', 'list[string]');
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
            $log = new CodeRage\Log;
            $provider =
                new \CodeRage\Log\Provider\Console([
                        'stream' => self::LOG_CONSOLE_STREAM,
                        'format' => self::LOG_CONSOLE_FORMAT
                    ]);
            $log->registerProvider($provide, self::LOG_CONSOLE_LEVEL);
        }
        $this->log = $log;
    }

    /**
     * Removes generated files
     */
    private function cleanImpl() : void
    {
        $path = $run->projectRoot() . '/' . self::GENERATED_FILE_LOG;
        if (!file_exists($path))
            return;
        File::check($path, 0b0110);
        $files = explode("\n", rtrim(file_get_contents($path)));
        for ($z = sizeof($files) - 1; $z != -1; --$z) {
            $f = $files[$z];
            if (file_exists($f) && @unlink($f) === false) {
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
     * Helper method for execute()
     *
     * @param string $buildEvent One of build, install, or sync
     * @param array $options The options array passed to execute(), after
     *   processing
     */
    private function buildImpl(string $buildEvent, array $options) : void
    {
        $this->buildConfig = BuildConfig::load($this->projectRoot);
        $this->updateConfig($options);
        $targets = new TargetSet($this, $targets);
        $targets->execute($buildEvent);
        $this->recordGeneratedFiles();
        $this->buildConfig->save($this->projectRoot);
    }

    /**
     * Updates the build configuration and project configuration
     *
     * @param array $options The options array
     */
    private function updateConfig(array $options) : void
    {
        if ($str = $this->getStream(Log::INFO))
            $str->write("Updating build configuration");

        // Construct a configuration reflecting the current execution
        // environment; this will be stored as the new build configuration
        $oldConfig = $this->buildConfig;
        $newConfig =
            new BuildConfig(
                    Time::get(),
                    true,
                    $oldConfig->commandLineProperties(),
                    null
                );

        // Set the "projectInfo" property of the build configuration
        $path = $newConfig->projectConfigFile()->path();
        if (pathinfo($path, PATHINFO_EXTENSION) == 'xml') {
            $dom = Xml::loadDocument($path);
            $doc = $dom->documentElement;
            $ns = Constants::NAMESPACE_URI;
            if ($doc->localName == 'project' && $doc->namespaceURI == $ns) {
                if ($k = Xml::firstChildElement($doc, 'info', $ns)) {
                   $info = Info::fromXml($k);
                   $newConfig->setProjectInfo($info);
                }
            }
        }

        // Update the project configuration and build configuration
        if ($this->needNewProjectConfig($oldConfig, $options)) {
            $this->updateProjectConfig($newConfig, $options);
        } else {
            $this->loadProjectConfig();
        }
        $newConfig->setCommandLineProperties($this->projectConfig);

        $this->buildConfig = $newConfig;
    }


    /**
     * Returns true if a new project configuration must be generated
     *
     * @param CodeRage\Build\BuildConfig $oldConfig The previous build
     *  configuration
     * @param array $options The options array
     * @return boolean
     */
    private function needNewProjectConfig(BuildConfig $oldConfig, array $options)
        : bool
    {
        if ($options['setProperties'] || $options['unsetProperties'])
            return true;
        $handler = new ErrorHandler;
        $timestamp = $this->handler->_filemtime($oldConfig->projectConfigFile());
        if ($timestamp === false || $handler->errno())
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' =>
                        $handler->formatError('Failed querying file timestamp')
                ]);
        return $timestamp >= $oldConfig->timestamp();
    }

    /**
     * Loads the existing project configuration
     *
     * @return CodeRage\Build\ProjectConfig
     */
    private function loadProjectConfig() : ProjectConfig
    {
        try {
            $this->log->logMessage("Loading project configuration");
            $file = "$this->projectRoot/.coderage/config.xml";
            $reader = new \CodeRage\Build\Config\Reader\File($this, $file);
            return $reader->read();
        } catch (Throwable $e) {
            throw new
                Error([
                    'message' =>
                        'Failed loading project configuration: ' .
                            $e->getMessage()
                ]);
        }
    }

    /**
     * Updates the property $projectConfig
     *
     * @param CodeRage\Build\BuildConfig $newConfig The new build configuration
     * @param array $options The options array
     */
    private function updateProjectConfig(BuildConfig $newConfig, array $options)
    {
        if ($str = $this->getStream(Log::INFO))
            $str->write("Updating project configuration");

        // Generate project configuration
        $config = $this->generateProjectConfig($newConfig);
        $this->projectConfig = $config;

        // Generate runtime configuration
        foreach (['xml', 'php'] as $lang) {
            if ($str = $this->getStream(Log::INFO))
                $str->write("Generating $lang configuration");
            $class = 'CodeRage\\Build\\Config\\Writer\\' . ucfirst($lang);
            $writer = new $class;
            $path = "$this->projectRoot/.coderage/config.$lang";
            $writer->write($config, $path);
            $this->recordGeneratedFile($path);
        }
    }

    /**
     * Returns an instance of CodeRage\Build\ProjectConfig constructed from the
     * given build configuration
     *
     * @param CodeRage\Build\BuildConfig $newConfig The new build configuration
     * @param array $options The options array
     * @return CodeRage\Build\ProjectConfig
     */
    private function generateProjectConfig(BuildConfig $newConfig, array $options)
        : ProjectConfig
    {
        if ($str = $this->getStream(Log::INFO))
            $str->write("Generating project configuration");

        $configs = [];

        // Handle project definition file
        $newConfig->projectConfigFile();
        $reader = new Config\Reader\File($this, $file->path());
        $projectConfig = $reader->read();
        array_unshift($configs, $projectConfig);

        // Handle previous command-line
        $cmdline = new Config\Basic;
        foreach ($this->buildConfig->commandLineProperties() as $n => $v) {
             if (!in_array($n, $options['unsetProperties'])) {
                 $cmdline->addProperty(
                     new Property(
                             $n,
                             Constants::STRING | Constants::ISSET_,
                             $v,
                             Constants::COMMAND_LINE,
                             Constants::COMMAND_LINE
                         )
                 );
             }
        }
        array_unshift($configs, $cmdline);

        // Handle current command-line
        $cmdline = new Config\Basic;
        foreach ($options['setProperties'] as $n => $v) {
              if (!in_array($n, $options['unsetProperties'])) {
                  $cmdline->addProperty(
                      new Property(
                              $n,
                              Constants::ISSET_,
                              $v,
                              0,
                              Constants::COMMAND_LINE
                          )
                  );
              }
        }
        array_unshift($configs, $reader->read());

        // Construct configuration
        $result =  new Config\Compound($configs);
        Basic::validate($result);

        return $result;
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
            File::check(dirname($path), 0b0111);
            touch($path);
        }
        $path = realpath($path);
        if ($str = $this->getStream(Log::VERBOSE))
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
     * The current build configuration.
     *
     * @var CodeRage\Build\BuildConfig
     */
    private $buildConfig;

    /**
     * The project configuration.
     *
     * @var CodeRage\Build\ProjectConfig
     */
    private $projectConfig;

    /**
     * The collection of targets, if any, in the process being built.
     *
     * @var CodeRage\Build\TargetSet
     */
    private $targets;

    /**
     * The list of generated files
     *
     * @var array
     */
    private $files = [];
}
