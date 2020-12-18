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
use CodeRage\Build\Config\Reader\File as FileReader;
use const CodeRage\Build\{COMMAND_LINE, ISSET_, NAMESPACE_URI, STRING};
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
     * @return boolean
     */
    public function build()
    {
        return $this->execute(function($engine, $options) {
            $engine->buildImpl('build', $options);
        });
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
        $this->buildConfig = $this->projectConfig = $this->moduleStore = null;
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
            $this->buildConfig = BuildConfig::load($this->projectRoot);
            $this->moduleStore =
                new ModuleStore($this, $this->buildConfig->modules());
            if ($options['updateConfig'])
                $this->updateConfig($options);
            $action($this, $options);
            if ($options['updateConfig'])
                $this->buildConfig->save($this->projectRoot);
        } catch (Throwable $e) {
            $status = false;
            $this->log->logError($e);
        } finally {

            // Clear state
            $this->buildConfig = $this->projectConfig = $this->moduleStore = null;
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
     * Updates the build configuration and project configuration
     *
     * @param array $options The options array
     */
    private function updateConfig(array $options) : void
    {
        if ($str = $this->log->getStream(Log::INFO))
            $str->write("Updating build configuration");
        $old = $this->buildConfig;
        $new =
            new BuildConfig(
                    Time::get(),
                    $old->commandLineProperties(),
                    $old->modules()
                );
        if ($this->needNewProjectConfig($options)) {
            $this->updateProjectConfig($new, $options);
            $this->moduleStore->load();
            $names =
                array_map(
                    function($m) { return $m->name(); },
                    $this->moduleStore->modules()
                );
            $new->setModules($names);
        } else {
            $this->projectConfig = $this->loadProjectConfig();
        }
        $new->setCommandLineProperties($this->projectConfig);

        $this->buildConfig = $new;
    }


    /**
     * Returns true if a new project configuration must be generated
     *
     * @param array $options The options array
     * @return boolean
     */
    private function needNewProjectConfig(array $options)
        : bool
    {
        if ($options['setProperties'] || $options['unsetProperties'])
            return true;
        $path = $this->buildConfig->projectConfigFile();
        return filemtime($path) >= $this->buildConfig->timestamp();
    }

    /**
     * Updates the property $projectConfig
     *
     * @param CodeRage\Build\BuildConfig $new The new build configuration
     * @param array $options The options array
     */
    private function updateProjectConfig(BuildConfig $new, array $options)
    {
        if ($str = $this->log->getStream(Log::INFO))
            $str->write("Updating project configuration");

        // Generate project configuration
        $config = $this->generateProjectConfig($new, $options);
        $this->projectConfig = $config;

        // Generate runtime configuration
        foreach (['xml', 'php'] as $lang) {
            if ($str = $this->log->getStream(Log::INFO))
                $str->write("Generating $lang configuration");
            $class = 'CodeRage\\Build\\Config\\Writer\\' . ucfirst($lang);
            $writer = new $class;
            $path = "$this->projectRoot/.coderage/config.$lang";
            $writer->write($config, $path);
            $this->recordGeneratedFile($path);
        }
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
            $reader = new FileReader($this, $file);
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
     * Returns an instance of CodeRage\Build\ProjectConfig constructed from the
     * given build configuration
     *
     * @param CodeRage\Build\BuildConfig $new The new build configuration
     * @param array $options The options array
     * @return CodeRage\Build\ProjectConfig
     */
    private function generateProjectConfig(BuildConfig $new, array $options)
        : ProjectConfig
    {
        if ($str = $this->log->getStream(Log::INFO))
            $str->write("Generating project configuration");

        $configs = [];

        // Handle CodeRage project definition file
        $reader = new FileReader($this, dirname(__DIR__) . '/project.xml');
        $config = $reader->read();
        $configs[] = $config;

        // Handle module configurations
        foreach ($this->moduleStore->modules() as $module) {
            if ($path = $module->configFile()) {
                if ($str = $this->log->getStream(Log::VERBOSE))
                    $str->write("Processing configuration file $path");
                $reader = new FileReader($this, $path);
                $config = $reader->read();
                $configs[] = $config;
            }
        }

        // Handle project definition file
        $reader = new FileReader($this, $new->projectConfigFile());
        $config = $reader->read();
        $configs[] = $config;

        // Handle previous command-line
        $cmdline = new Config\Basic;
        foreach ($this->buildConfig->commandLineProperties() as $n => $v) {
             if (!in_array($n, $options['unsetProperties'])) {
                 $cmdline->addProperty(
                     new Config\Property(
                             $n,
                             STRING | ISSET_,
                             $v,
                             COMMAND_LINE,
                             COMMAND_LINE
                         )
                 );
             }
        }
        $configs[] = $cmdline;

        // Handle current command-line
        $cmdline = new Config\Basic;
        foreach ($options['setProperties'] as $n => $v) {
              if (!in_array($n, $options['unsetProperties'])) {
                  $cmdline->addProperty(
                      new Config\Property(
                              $n,
                              ISSET_,
                              $v,
                              0,
                              COMMAND_LINE
                          )
                  );
              }
        }
        $configs[] = $cmdline;

        // Construct configuration
        $result =  new Config\Compound(array_reverse($configs));
        Config\Basic::validate($result);

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
