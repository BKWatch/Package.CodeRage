<?php

/**
 * Defines the class CodeRage\Build\Action\Test.
 *
 * File:        CodeRage/Build/Action/Test.php
 * Date:        Thu Aug  5 23:05:08 MDT 2010
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Action;

use const CodeRage\Build\COMMAND_LINE;
use CodeRage\Build\CommandLine;
use CodeRage\Build\Config\Basic;
use CodeRage\Build\Config\Property;
use CodeRage\Build\Config\Reader\File;
use const CodeRage\Build\ISSET_;
use CodeRage\Build\Run;
use const CodeRage\Build\STRING;
use function CodeRage\Build\Git\clone_;
use CodeRage\Error;
use function CodeRage\File\path;
use CodeRage\Log;
use function CodeRage\Text\split;
use function CodeRage\Util\escapeShellArg;

/**
 * @ignore
 */
require_once('CodeRage/Build/Git/clone.php');
require_once('CodeRage/File/path.php');
require_once('CodeRage/File/tempDir.php');
require_once('CodeRage/Text/split.php');
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/randomString.php');
require_once('CodeRage/Util/system.php');

/**
 * Represents the 'test' build action.
 */
class Test implements \CodeRage\Build\Action {

    /**
     * The name of the configuration property that specifies the database name
     *
     * @var string
     */
    const DATABASE_PROPERTY = 'db.database';

    /**
     * Returns 'update'.
     *
     * @return string
     */
    function name() { return 'test'; }

    /**
     * Checks the command line of the given run for consistency the 'test'
     * build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {
        $commandLine = $run->commandLine();
        $common = split(CommandLine::COMMON_OPTIONS);
        $config = split(CommandLine::CONFIG_OPTIONS);
        $repo = split(CommandLine::REPO_OPTIONS);
        $db = split(CommandLine::DB_OPTIONS);
        $test = split(CommandLine::TEST_OPTIONS);
        $options = array_merge($common, $config, $repo, $db, $test);
        foreach ($commandLine->options() as $opt) {
            if ($opt->hasExplicitValue()) {
                $long = $opt->longForm();
                if ($long != 'test' && !in_array($long, $options))
                    throw new
                        Error(['message' =>
                            "The option --$long cannot be combined with " .
                            "the option --test"
                        ]);
            }
        }
        if (sizeof($commandLine->arguments()) > 0)
            throw new
                Error(['message' =>
                    'command-line arguments are not supported with the ' .
                    'option --test'
                ]);
        if (!$commandLine->hasValue('project-url'))
            throw new Error(['message' => 'Missing project URL']);
    }

    /**
     * Returns true.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return boolean
     */
    function requiresProjectConfig(Run $run)
    {
        return true;
    }

    /**
     * Executes the 'update' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        require_once('CodeRage.php');
        $this->checkForBootstrapFiles($run);
        $projectDir = $this->checkoutProject($run);
        $this->checkForProjectConfig($run, $projectDir);
        $this->installTools($run, $projectDir);
        $configFile = $this->createConfigFile($run);
        $this->buildTestProject($run, $projectDir, $configFile);
    }

    /**
     * Verifies that CodeRage bootstrap files are installed.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     */
    private function checkForBootstrapFiles(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Checking for bootstrap files");
        $config = $run->buildConfig();
        $path = $config->bootstrapPath();
        if (!$path)
            throw new
                Error(['message' =>
                    "CodeRage bootstrap files are not installed; run " .
                    "'makeme --install --minimall' or 'makeme --install " .
                    "--shared to install"
                ]);
    }

    /**
     * Checks out the test project and returns the project directory pathname.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     */
    private function checkoutProject(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Checking out test project");
        $commandLine = $run->commandLine();
        $url = $commandLine->getValue('project-url');
        $branch = $commandLine->hasValue('project-branch') ?
            $commandLine->getValue('project-branch') :
            null;
        $projectDir = \CodeRage\File\tempDir();
        clone_($run, $url, $projectDir, $branch);
        if ($commandLine->hasValue('project-path'))
            $projectDir .= '/' . $commandLine->getValue('project-path');
        return $projectDir;
    }

    /**
     * Verifies that a project configuration file exists in the specified
     * directory.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     * @param string $projectDir The temporary project directory.
     */
    private function checkForProjectConfig(Run $run, $projectDir)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Checking for project config file");
        $found = false;
        foreach (split(\CodeRage\Build\BuildConfig::CONFIG_FILES) as $f) {
            if (is_file(path($projectDir, $f))) {
                $found = true;
                break;
            }
        }
        if (!$found)
            throw new Error(['message' => "Missing project configuration"]);
    }

    /**
     * Checks out the CodeRage tools to the temporary project directory.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $projectDir The temporary project directory.
     * @return string
     */
    private function installTools(Run $run, $projectDir)
    {
        // Check out CodeRage tools
        if ($str = $run->getStream(Log::INFO))
            $str->write("Checking out CodeRage Tools");
        $commandLine = $run->commandLine();
        $url = $commandLine->hasValue('repo-url') ?
            $commandLine->getValue('repo-url') :
            \CodeRage\Build\REPO_URL;
        $branch = $commandLine->hasValue('repo-branch') ?
            $commandLine->getValue('repo-branch') :
            \CodeRage\Build\REPO_BRANCH;
        $target = path($projectDir, 'CodeRage');
        clone_($run, $url, $target, $branch);
    }

    /**
     * Creates a config file to use in addition to the project configuration
     * file and returns the file pathname.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     */
    private function createConfigFile(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Creating config file");

        $configs = [];

        // Handle environment
        $reader = new \CodeRage\Build\Config\Reader\Environment;
        array_unshift($configs, $reader->read());

        // Handle system-wide configuration
        $buildConfig = $run->buildConfig();
        if ($file = $buildConfig->systemConfigFile()) {
            $reader = new File($this, $file->path());
            array_unshift($configs, $reader->read());
        }

        // Handle additional configurations
        foreach (array_reverse($buildConfig->additionalConfigFiles()) as $f) {
            $reader = new File($this, $f->path());
            array_unshift($configs, $reader->read());
        }

        // Handle current command-line
        $reader = new \CodeRage\Build\Config\Reader\CommandLine($run->commandLine());
        array_unshift($configs, $reader->read());

        // Handle database configurations
        $db = new \CodeRage\Build\Config\Basic;
        foreach (split(CommandLine::DB_OPTIONS) as $opt) {
            if ($run->commandLine()->hasValue($opt))
                $db->addProperty(
                    new Property(
                            str_replace('-', '.', $opt),
                            STRING | ISSET_,
                            $run->commandLine()->getValue($opt),
                            COMMAND_LINE,
                            COMMAND_LINE
                        )
                );
        }
        $db->addProperty(
            new Property(
                    self::DATABASE_PROPERTY,
                    STRING | ISSET_,
                    'testdb_' . \CodeRage\Util\randomString(12),
                    COMMAND_LINE,
                    COMMAND_LINE
                )
        );
        array_unshift($configs, $db);

        // Create and validate compound configuration
        $compound = new \CodeRage\Build\Config\Compound($configs);
        Basic::validate($compound);

        $writer = new \CodeRage\Build\Config\Writer\Xml;
        $file = \CodeRage\File\temp('config', 'xml');
        $writer->write($compound, $file);
        return $file;
    }

    /**
     * Build the test project.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $projectDir The temporary project directory.
     * @param string $configFile The path to the supplemental configuration
     * file.
     */
    private function buildTestProject(
        Run $run, $projectDir, $configFile)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Building test project");

        // Construct command line
        $command = 'makeme --build --non-interactive';
        $commandLine = $run->commandLine();
        $log = split(CommandLine::LOG_OPTIONS);
        $smtp = split(CommandLine::SMTP_OPTIONS);
        $options = array_merge($log, $smtp);
        foreach ($options as $name) {
            if ($commandLine->hasValue($name)) {
                  $opt = $commandLine->lookupOption($name);
                  if ($opt->type() == 'boolean') {
                    if ($opt->value())
                        $command .= ' --' . $opt->longForm();
                  } else {
                    $command .=
                        ' --' . $opt->longForm() . ' ' .
                        escapeShellArg($opt->value());
                  }
            }
        }
        $command .= ' --config ' . escapeShellArg($configFile);
        $arguments = $commandLine->arguments();
        array_shift($arguments);
        foreach ($arguments as $arg)
            $command .= ' ' . escapeShellArg($arg);
        $command .= ' 2>&1';

        // Execute command-line
        if ($str = $run->getStream(Log::INFO))
            $str->write("Executing command: $command");
        if (!@chdir($projectDir))
            throw new Error(['message' => "Failed changing to directory '$projectDir'"]);
        $output = null;
        try {
            $output = \CodeRage\Util\system($command);
        } catch (\Throwable $e) {
            $details = "Failed executing makeme --test";
            if ($output)
                $details .= ': $output';
            throw new
               Error([
                   'details' => $details,
                   'inner' => $e
               ]);
        }
    }
}
