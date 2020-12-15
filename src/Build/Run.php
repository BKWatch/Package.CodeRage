<?php

/**
 * Defines the class CodeRage\Build\Run.
 *
 * File:        CodeRage/Build/Run.php
 * Date:        Tue Feb 26 13:27:45 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use Exception;
use Throwable;
use CodeRage\Build\Config\Basic;
use CodeRage\Build\Config\Property;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Log;
use CodeRage\Text;
use CodeRage\Util\ErrorHandler;
use CodeRage\Xml;

/**
 * Provides a unified interface to various services provided by the build
 * system.
 *
 */
class Run extends \CodeRage\Util\BasicProperties {

    /**
     * Path, relative to the project root directory, of the list of generated
     * files.
     *
     * @var string
     */
    const GENERATED_FILE_LOG = '.coderage/files.log';

    /**
     * The command line.
     *
     * @var CodeRage\Util\CommandLine
     */
    private $commandLine;

    /**
     * The project root directory
     *
     * @var string
     *
     */
    private $projectRoot;

    /**
     * The log used to record build events.
     *
     * @var CodeRage\Log
     */
    private $log;

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
     * The list of files generated during this run.
     *
     * @var array
     */
    private $files = [];

    /**
     * An error handler.
     *
     * @var CodeRage\Util\ErrorHandler
     */
    private $handler;

    /**
     * The time this run of the build system began, as a UNIX timestamp
     *
     * @var int
     */
    private $timestamp;

    /**
     * Constructs a CodeRage\Build\Run
     */
    function __construct()
    {
        $this->projectRoot = Config::projectRoot();
        $this->handler = new ErrorHandler;
        $this->timestamp = \CodeRage\Util\Time::real();
    }

    /**
     * Returns the command line.
     *
     * @return CodeRage\Build\CommandLine
     */
    function commandLine()
    {
        return $this->commandLine;
    }

    /**
     * Returns the project root directory.
     *
     * @return string
     */
    function projectRoot()
    {
        return $this->projectRoot;
    }

    /**
     * Returns the log used to record build events.
     *
     * @return CodeRage\Log
     */
    function log()
    {
        return $this->log;
    }

    /**
     * Returns the log stream, if any, with the given log level.
     *
     * @param int $level
     * @return CodeRage\Log
     */
    function getStream($level)
    {
        return $this->log->getStream($level);
    }

    /**
     * Returns the build configuration.
     *
     * @return CodeRage\Build\BuildConfig
     */
    function buildConfig()
    {
        return $this->buildConfig;
    }

    /**
     * Returns the project configuration.
     *
     * @return CodeRage\Build\ProjectConfig
     */
    function projectConfig()
    {
        return $this->projectConfig;
    }

    /**
     * Returns the collection of targets, if any, in the process being built.
     *
     * @return CodeRage\Build\TargetSet
     */
    function targets()
    {
        return $this->targets;
    }

    /**
     * Returns the path to the PHP command-line executable corresponding to the
     * current PHP installation.
     *
     * @throws CodeRage\Error If the current configuration cannot be located.
     */
    function binaryPath()
    {
        return $this->binaryPath;
    }

    /**
     * Returns the time this run of the build system began, as a UNIX timestamp
     */
    function timestamp()
    {
        return $this->timestamp;
    }

    /**
     * Wrapper for CodeRage\File\generate that stores $path in the list of generated
     * files.
     *
     * @param string $path The file.
     * @param string $content
     * @param string $type One of the strings 'bat', 'css', 'htm', 'html', 'ini',
     * 'js', 'php', 'pl', 'pm', 'py', or 'xml'.
     * @throws Exception
     */
    function generateFile($path, $content, $type = null)
    {
        if ($str = $this->getStream(Log::DEBUG))
            $str->write("Generating file: $path");
        File::generate($path, $content, $type);
        $this->recordGeneratedFile($path);
    }

    /**
     * Stores $path in the list of generated files.
     *
     * @param string $path
     */
    function recordGeneratedFile($path)
    {
        if ($str = $this->getStream(Log::DEBUG))
            $str->write("Recording generated file: $path");
        if (!File::isAbsolute($path))
            throw new
                Error(['message' =>
                    "Failed recording generated file: expected absolute " .
                    "path; found $path"
                ]);
        $this->files[] = realpath($path);
    }

    /**
     * Performs the build action specified on the command-line
     *
     * @return boolean true for success
     */
    function execute()
    {
        $level = E_ALL & ~(E_NOTICE | E_USER_NOTICE | E_STRICT);
        if (defined('E_DEPRECATED'))
            $level &= ~(E_DEPRECATED | E_USER_DEPRECATED);
        set_error_handler([$this, 'handleError'], $level);
        try {
            $result = $this->doExecute();
            restore_error_handler();
            return $result;
        } catch (Throwable $e) {
            restore_error_handler();
            $this->log->logError($e);
            return false;
        }
    }

    /**
     * Performs the build action specified on the command-line
     *
     * @return boolean true for success
     */
    protected function doExecute()
    {
        // Prepare build
        try {

            // Initialize project root, command line, build config, and log
            $this->commandLine = new CommandLine;
            $this->commandLine->parse();
            $this->commandLine->setAction();
            if ($this->commandLine->action()->name() == 'help') {
                $this->commandLine->action()->execute($this);
                return true;
            }
            $this->buildConfig = BuildConfig::load($this->projectRoot);
            $this->log = $this->commandLine->createLog($this->projectRoot);

            // Set timezone
            if (!ini_get('date.timezone')) {
                $zone = DEFAULT_TIMEZONE;
                date_default_timezone_set($zone);
                if ($str = $this->getStream(Log::INFO))
                    $str->write("Using timezone $zone");
            }

            // Validate command line
            if ($str = $this->getStream(Log::INFO))
                $str->write("Validating command line");
            $this->commandLine->action()->checkCommandLine($this);


            if ($this->commandLine->action()->name() == 'info') {
                $this->commandLine->action()->execute($this);
                return true;
            }

            // Update build and project config
            $this->updateConfig();

        } catch (Throwable $e) {
            $this->logError($e);
            return false;
        }

        // Execute build action
        $status = true;
        try {
            $action = $this->commandLine()->action();
            if ($str = $this->getStream(Log::INFO))
                $str->write("Executing build action '" . $action->name() . "'");
            $action->execute($this);
        } catch (Throwable $e) {
            $status = false;
            $this->logError($e);
        }

        // Save build metadata
        try {
            $this->recordGeneratedFiles();
        } catch (Throwable $e) {
            $status = false;
            $this->logError($e);
        }
        if (self::errorCount())
            $status = false;
        $this->buildConfig->setStatus($status);
        try {
            if ($this->buildConfig->projectConfigFile()) {
                if ($str = $this->getStream(Log::INFO))
                    $str->write("Saving build configuration");
                $this->buildConfig->save($this->projectRoot);
            }
        } catch (Throwable $e) {
            $status = false;
            $this->logError($e);
        }

        $count = self::errorCount();
        if ($str = $this->getStream(Log::INFO))
            $str->write("There were $count error(s)");
        return $status;
    }

    /**
     * Build the collection of targets specified on the command line.
     */
    function build()
    {
        \CodeRage\Config::clear();
        $targets = sizeof($this->commandLine()->arguments()) ?
            $this->commandLine()->arguments() :
            [];
        $this->targets = new TargetSet($this, $targets);
        try {
            $this->targets->execute();
        } catch (Throwable $e) {
            $this->targets = null;
            throw $e;
        }
        $this->targets = null;
    }

    /**
     * Handles PHP errors triggered during the build process.
     *
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    function handleError($errno, $errstr, $errfile, $errline)
    {
        throw new
            Error([
                'message' =>
                    ErrorHandler::errorCategory($errno) .
                    ": $errstr in $errfile on line $errline"
            ]);
    }

    /**
     * Updates the build configuration and project configuration. When this
     * method is called, $this->buildConfig is a configuration loaded from
     * the project root, or a configuration with empty values if no stored
     * configuration exists.
     */
    private function updateConfig()
    {
        if ($str = $this->getStream(Log::INFO))
            $str->write("Updating build configuration");

        // Construct a configuration reflecting the current execution
        // environment; this will be stored as the new build configuration
        $oldConfig = $this->buildConfig;
        $newConfig =
            BuildConfig::create(
                $this->commandLine,
                $this->projectRoot
            );
        $newConfig->inheritCommandLineProperties($oldConfig);
        $this->buildConfig = $newConfig;

        // If there is a project configuration involved, we must generate a
        // new project configuration or update the existing configuration
        if ( $this->commandLine->action()->requiresProjectConfig($this) &&
             $this->buildConfig->projectConfigFile())
        {
            // Set the "projectInfo" property of the build configuration
            $path = $this->buildConfig->projectConfigFile()->path();
            if (pathinfo($path, PATHINFO_EXTENSION) == 'xml') {
                $dom = Xml::loadDocument($path);
                $doc = $dom->documentElement;
                $ns = NAMESPACE_URI;
                if ($doc->localName == 'project' && $doc->namespaceURI == $ns) {
                    if ($k = Xml::firstChildElement($doc, 'info', $ns)) {
                       $info = Info::fromXml($k);
                       $this->buildConfig->setProjectInfo($info);
                    }
                }
            }

            // Update the project configuration and build configuration
            if ($this->needNewProjectConfig($oldConfig)) {
                $this->updateProjectConfig($newConfig);
            } else {
                $this->loadProjectConfig();
            }
            $newConfig->setCommandLineProperties($this->projectConfig);
        }
    }

    /**
     * Returns true if a new project configuration must be generated. The member
     * variable $buildConfig may be null when this method is called.
     *
     * @param CodeRage\Build\BuildConfig $oldConfig The previous build configuration.
     * @return boolean.
     */
    private function needNewProjectConfig(BuildConfig $oldConfig)
    {
        if ( $this->commandLine->hasValue('set') ||
             $this->commandLine->hasValue('unset')  ||
             $this->commandLine->hasValue('config') ||
             !file_exists("$this->projectRoot/.coderage/config.xml") )
        {
            return true;
        }
        $timestamp = $this->handler->_filemtime($oldConfig->projectConfigFile());
        if ($timestamp === false || $this->handler->errno())
            throw new \RuntimeException("Failed querying file timestamp");
        return $timestamp >= $oldConfig->timestamp();
    }

    /**
     * Loads the existing project configuration.
     */
    private function loadProjectConfig()
    {
        try {
            if ($str = $this->getStream(Log::INFO))
                $str->write("Loading project configuration");
            $file = "$this->projectRoot/.coderage/config.xml";
            $reader = new \CodeRage\Build\Config\Reader\File($this, $file);
            $this->projectConfig = $reader->read();
        } catch (Throwable $e) {
            throw new
                Error(['message' =>
                    'Failed loading project configuration: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Updates the member variable $projectConfig and writes the new
     * configuration to the filesystem. The member variable $buildConfig may
     * have empty values when this method is called.
     *
     * @param CodeRage\Build\BuildConfig $newConfig  The new build configuration; its
     * arrays of configuration variables have not been set yet.
     */
    private function updateProjectConfig(BuildConfig $newConfig)
    {
        if ($str = $this->getStream(Log::INFO))
            $str->write("Updating project configuration");

        // Generate project configuration
        $config = $this->generateProjectConfig($newConfig);
        $this->projectConfig = $config;

        // Construct list of backend languages
        $backend = ($prop = $config->lookupProperty('backend_language')) ?
            $prop->value() :
            '';
        $languages = Text::split($backend);
        $languages[] = 'xml';
        if (!in_array('php', $languages))
            $languages[] = 'php';

        // Generate runtime configuration
        foreach ($languages as $lang) {
            if ($str = $this->getStream(Log::INFO))
                $str->write("Generating $lang configuration");
            $file = 'CodeRage/Build/Config/Writer/' . ucfirst($lang) . '.php';
            if ($search = File::searchIncludePath($file))
                require_once($search);
            $class = 'CodeRage\\Build\\Config\\Writer\\' . ucfirst($lang);
            if (!class_exists($class))
                if ($str = $this->log->getStream(Log::ERROR)) {
                    $str->write("Invalid backend language: $lang");
                    continue;
                }
            $writer = new $class;
            $path = "$this->projectRoot/.coderage/config.$lang";
            $writer->write($config, $path);
            $this->recordGeneratedFile($path);
        }
    }

    /**
     * Returns an instance of CodeRage\Build\ProjectConfig constructed from the
     * given build configuration. The member variable $buildConfig may be null
     * when this methid is called.
     *
     * @param CodeRage\Build\BuildConfig $newConfig  The new build configuration; its
     * arrays of configuration variables have not been set yet.
     * @return CodeRage\Build\ProjectConfig
     */
    private function generateProjectConfig(BuildConfig $newConfig)
    {
        if ($str = $this->getStream(Log::INFO))
            $str->write("Generating project configuration");

        $configs = [];

        // Handle environment
        $reader = new Config\Reader\Environment;
        array_unshift($configs, $reader->read());

        // Handle system-wide configuration
        if ($file = $newConfig->systemConfigFile()) {
            $reader =
                new \CodeRage\Build\Config\Reader\File($this, $file->path());
            array_unshift($configs, $reader->read());
        }

        // Handle project definition file
        if ($file = $newConfig->projectConfigFile()) {
            $reader =
                new \CodeRage\Build\Config\Reader\File($this, $file->path());
            $projectConfig = $reader->read();

            // Add a property for each child of the "info" element
            if ($info = $newConfig->projectInfo())
                foreach (Text::split(Info::PROPERTIES) as $p) {
                      if ($info->$p())
                            $projectConfig->addProperty(
                                new Property(
                                        "project.$p",
                                        STRING | ISSET_,
                                        $info->$p(), $file->path(), $file->path()
                                    )
                            );
                }
            array_unshift($configs, $projectConfig);
        }

        // Handle additional project-specific configurations
        foreach (array_reverse($newConfig->additionalConfigFiles()) as $file) {
            $reader =
                new \CodeRage\Build\Config\Reader\File($this, $file->path());
            array_unshift($configs, $reader->read());
        }

        // Handle previous command-line
        $unset = $this->unsetCommandLineVariables();
        if ($this->buildConfig) {
              $cmdline = new Config\Basic;
              foreach ($this->buildConfig->commandLineProperties() as $n => $v)
              {
                  if (!in_array($n, $unset)) {
                      $cmdline->addProperty(
                          new Property(
                                  $n,
                                  STRING | ISSET_,
                                  $v,
                                  COMMAND_LINE,
                                  COMMAND_LINE
                              )
                      );
                  }
              }
            array_unshift($configs, $cmdline);
        }

        // Handle current command-line
        $reader = new Config\Reader\CommandLine($this->commandLine);
        array_unshift($configs, $reader->read());

        // Assign tools_root property
        $result =  new Config\Compound($configs);
        if ($result->lookupProperty('tools_root') === null)
            $result->addProperty(
                new Property(
                        'tools_root', STRING | ISSET_,
                                    $newConfig->toolsPath(), null, null
                    )
            );

        // Check for required properties
        Basic::validate($result);

        return $result;
    }

    /**
     * Stores the pathnames of files generates during this run of the build
     * system.
     */
    private function recordGeneratedFiles()
    {
        if (sizeof($this->files) == 0)
            return;
        $path = $this->projectRoot .'/' . self::GENERATED_FILE_LOG;
        if (!file_exists($path))
            touch($path);
        $path = realpath($path);
        if ($str = $this->getStream(Log::VERBOSE))
            $str->write("Saving list of generated files to '$path'");
        $content = File::getContents($path);
        foreach (explode("\n", rtrim($content)) as $f)
            $this->files[] = $f;
        $this->files = array_unique($this->files);
        $content = join("\n", $this->files);
        if (!$this->handler->_file_put_contents($path, $content))
            if ($str = $this->log->getStream(Log::ERROR))
                $str->write(
                    $this->handler->formatError(
                        'Failed saving list of generated files'
                    )
                );
    }

    /**
     * Returns a list of property names passed on the command line using the
     * option --unset
     */
    private function unsetCommandLineVariables()
    {
        $properties = [];
        if ($values = $this->commandLine->getValue('u', true)) {
            $pattern = '/^[_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*$/i';
            foreach ($values as $v) {
                if (preg_match($pattern, $v)) {
                    $properties[] = $v;
                } else {
                    throw new Error(['message' => "Invalid option: -u $v"]);
                }
            }
        }
        return $properties;
    }

    /**
     * Logs the given error, if the log is initialized, and echos it otherwise.
     *
     * @param Throwable $e
     */
    private function logError(Throwable $e)
    {
        if ($this->log) {
            if ($e instanceof Error)
                $e->log($this->log);
            elseif ($stream = $this->log->getStream(Log::ERROR))
                $stream->write($e->getMessage());
        } else {
            echo $e->message() . "\n";
        }
    }

    /**
     * Returns the number of build errors that have occurred.
     */
    private static function errorCount()
    {
        return
            \CodeRage\Log\Provider\Counter::getCount([
                'tag' => 'Build',
                'maxLevel' => Log::ERROR
            ]);
    }
}
