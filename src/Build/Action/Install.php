<?php

/**
 * Defines the class CodeRage\Build\Action\Install.
 *
 * File:        CodeRage/Build/Action/Install.php
 * Date:        Tue Jan 06 16:51:57 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Action;

use Exception;
use Throwable;
use CodeRage\Build\CommandLine;
use CodeRage\Build\Resource_;
use CodeRage\Build\Run;
use function CodeRage\Build\Git\clone_;
use CodeRage\Error;
use function CodeRage\File\mkdir;
use CodeRage\Log;
use function CodeRage\Text\split;
use function CodeRage\Util\os;
use const CodeRage\VERSION;

/**
 * @ignore
 */
require_once('CodeRage/Build/addToIncludePath.php');
require_once('CodeRage/Build/addToPath.php');
require_once('CodeRage/Build/Git/clone.php');
require_once('CodeRage/File/getContents.php');
require_once('CodeRage/File/mkdir.php');
require_once('CodeRage/Util/os.php');
require_once('CodeRage/Text/split.php');
require_once('CodeRage/Version.php');

/**
 * Represents the 'install' build action.
 */
class Install implements \CodeRage\Build\Action {

    /**
     * Permissions for the 'Bin' subdirectory of the CodeRage bootstrap files.
     *
     * @var int
     */
    const BINARY_FILE_PERMISSIONS = 0755;

    /**
     * Permissions for the 'Bin' subdirectory of the CodeRage bootstrap files.
     *
     * @var int
     */
    const BINARY_DIRECTORY_PERMISSIONS = 0755;

    /**
     * Permissions for library files.
     *
     * @var int
     */
    const LIBRARY_FILE_PERMISSIONS = 0644;

    /**
     * Directory permissions for library directories.
     *
     * @var int
     */
    const LIBRARY_DIRECTORY_PERMISSIONS = 0755;

    /**
     * One of the strings 'minimal', 'shared', or 'local', indicating where the
     * CodeRage tools should be installed.
     *
     * @var string
     */
    private $type;

    /**
     * Desired path to the CodeRage bootstrap files or tools.
     *
     * @var string
     */
    private $path;

    /**
     * Desired path to the CodeRage bootstrap files or tools.
     *
     * @var string
     */
    private $handler;

    /**
     * Constructs a CodeRage\Build\Action\Install.
     */
    function __construct()
    {
        $this->handler = new \CodeRage\Util\ErrorHandler;
    }

    /**
     * Returns 'install'.
     *
     * @return string
     */
    function name() { return 'install'; }

    /**
     * Checks the command line of the given run for consistency with the
     * 'install' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {
        // Check for forbidden options and arguments
        $commandLine = $run->commandLine();
        if (sizeof($commandLine->arguments()))
            throw new
                Error([
                    'message' =>
                        "Targets cannot be specified with the option " .
                        "--install"
                ]);
        $common = split(CommandLine::COMMON_OPTIONS);
        $repo = split(CommandLine::REPO_OPTIONS);
        $install = split(CommandLine::INSTALL_OPTIONS);
        $options = array_merge($common, $repo, $install);
        foreach ($commandLine->options() as $opt) {
            if ($opt->hasExplicitValue()) {
                $long = $opt->longForm();
                if ($long != 'install' && !in_array($long, $options))
                    throw new
                        Error([
                            'message' =>
                                "The option --$long cannot be combined with " .
                                "the option --install"
                        ]);
            }
        }

        // Set $type
        if ($str = $run->getStream(Log::DEBUG))
            $str->write("Determining installation type");
        foreach ($install as $opt) {
            if ($str = $run->getStream(Log::DEBUG))
                $str->write("Checking for option '$opt'");
            if ($commandLine->hasExplicitValue($opt)) {
                if ($this->type) {
                    throw new
                        Error([
                            'message' =>
                                'At most one of the options ' .
                                CommandLine::printOptions($install) .
                                ' may be specified'
                        ]);
                }
                if ($str = $run->getStream(Log::DEBUG))
                    $str->write("Found option '$opt'");
                $this->type = $commandLine->lookupOption($opt)->longForm();
                if (is_string($path = $commandLine->getValue($opt)))
                    $this->path = $path . '/CodeRage-' . VERSION;
            }
        }
        if (!$this->type)
            $this->type = 'shared';
        if ($str = $run->getStream(Log::VERBOSE))
            $str->write("Build type is '$this->type'");
    }

    /**
     * Returns true.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return boolean
     */
    function requiresProjectConfig(Run $run)
    {
        return false;
    }

    /**
     * Executes the 'install' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        switch ($this->type) {
        case 'minimal':
            $this->installMinimal($run);
            break;
        case 'local':
            $this->installLocal($run);
            break;
        case 'shared':
        default:
            $this->installShared($run);
            break;
        }
    }

    /**
     * Performs a minimal installation.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    private function installMinimal(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Performing minimal installation");
        $path = $this->path ?
            $this->path :
            self::defaultInstallPath($run);
        self::checkForTools($run);
        self::checkForBootstrapFiles($run);
        if (file_exists($path)) {
            $file = is_dir($path) ? 'directory' : 'file';
            throw new
                Error([
                    'message' =>
                        "The $file '$path' exists; please move it out of the way"
                ]);
        }
        $this->installBootstrapFiles($run, $path);
    }

    /**
     * Performs a shared installation.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    private function installShared(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Performing shared installation");

        // Set installation path
        $path = $this->path ?
            $this->path :
            self::defaultInstallPath($run);

        // Check for existing installations
        self::checkForTools($run);
        $config = $run->buildConfig();
        if ($bootstrapPath = $config->bootstrapPath()) {
            $message =
                self::filesInstalledError(
                    $bootstrapPath, $config->bootstrapVersion(),
                    'bootstrap files'
                );
            throw new Error(['message' => $message]);
        }

        // Instal bootstrap files
        if (!$bootstrapPath)
            $this->installBootstrapFiles($run, $path);

        // Check out CodeRage tools
        if ($str = $run->getStream(Log::INFO))
            $str->write("Checking out CodeRage Tools ...");
        $sep = os() == 'windows' ? '\\' : '/';
        clone_(
            $run,
            $this->toolsUrl($run),
            "$path{$sep}CodeRage",
            $this->toolsBranch($run)
        );
        $run->buildConfig()->setToolsPath($path);
    }

    /**
     * Performs a local installation.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    private function installLocal(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Performing local installation");

        // Make sure that bootstrap files are installed
        $config = $run->buildConfig();
        $path = $config->bootstrapPath();
        if (!$path)
            throw new
                Error([
                    'message' =>
                        "CodeRage bootstrap files are not installed; run " .
                        "'makeme --install --minimall' to install"
                ]);

        // Make sure project root contains a configuration file
        $proj = $config->projectConfigFile();
        if (!$proj)
            throw new Error(['message' => "No project definition file found"]);

        // Check out CodeRage tools
        if ($str = $run->getStream(Log::INFO))
            $str->write("Checking out CodeRage Tools ...");
        $sep = os() == 'windows' ? '\\' : '/';
        $target = dirname($proj->path()) . $sep . 'CodeRage';
        clone_(
            $run,
            $this->toolsUrl($run),
            $target,
            $this->toolsBranch($run)
        );
        $run->buildConfig()->setToolsPath(dirname($proj->path()));
    }

    /**
     * Throws an exception if CodeRage bootstrap files are already instaled.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    private function checkForBootstrapFiles(Run $run)
    {
        if ($str = $run->getStream(Log::VERBOSE))
            $str->write("Checking for bootstrap files");
        $config = $run->buildConfig();
        $path = $config->bootstrapPath();
        if ($path !== null) {
            $message =
                self::filesInstalledError(
                    $path, $config->bootstrapVersion(), 'bootstrap files'
                );
            throw new Error(['message' => $message]);
        }
    }

    /**
     * Throws an exception if CodeRage tools are already instaled.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    private static function checkForTools(Run $run)
    {
        if ($str = $run->getStream(Log::VERBOSE))
            $str->write("Checking for CodeRage tools");
        $config = $run->buildConfig();
        $path = $config->toolsPath();
        if ($path !== null) {
            $message =
                self::filesInstalledError(
                    $path, $config->toolsVersion(), 'tools'
                );
            throw new Error(['message' => $message]);
        }
    }

    /**
     * Throws an exception if CodeRage tools are already instaled.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    private static function filesInstalledError($path, $version, $label)
    {
        $cmp = version_compare($version, VERSION);
        switch ($cmp) {
        case -1:
            return
                "Version $version of CodeRage $label are already " .
                "installed, at '$path'; run makeme --update to update";
        case 0:
            return
                "CodeRage $label already are already installed, at '$path'";
        case 1:
        default:
            return
                "A more recent version of CodeRage $label are already " .
                "installed, at '$path'";
        }
    }

    /**
     * Installs CodeRage bootstrap files to the given directory.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $path
     */
    private function installBootstrapFiles(Run $run, $path)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write("Installing bootstrap files");

        // Create install directory and Bin subdirectory
        mkdir($path, self::BINARY_FILE_PERMISSIONS);
        mkdir("$path/Bin", self::BINARY_FILE_PERMISSIONS);

        // Classify resource files
        $os = os();
        $resources = Resource_::listFiles($run);
        $libraries = [];
        $tools = [];
        foreach ($resources as $file) {
            if (preg_match('/^CodeRage(-|$)/', pathinfo($file, PATHINFO_FILENAME))) {
                $libraries[] = $file;
            } else {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ( $ext == 'php' || $ext == 'pl' ||
                     ($os == 'windows' && $ext == 'bat') ||
                     ($os == 'posix' && $ext == '') )
                {
                    $tools[] = $file;
                }
            }
        }

        // Copy libraries files
        $managers = $run->loadPackageManagers();
        $found = false;
        foreach ($libraries as $file) {

              // Load file and copy it to $path
            $src = Resource_::loadFile($run, $file);
            $dest = "$path/$file";
            if (!$this->handler->callUserFunction('copy', $src, $dest))
                if ($str = $run->getStream(Log::ERROR))
                    $str->write(
                        $this->handler->formatError(
                            "Failed copying library file '$file' to '$path'"
                        )
                    );
            $mode = self::LIBRARY_FILE_PERMISSIONS;
            if (!$this->handler->callUserFunction('chmod', $dest, $mode))
                if ($str = $run->getStream(Log::ERROR))
                    $str->write(
                        $this->handler->formatError(
                            "Failed setting permissions of file '$dest'"
                        )
                    );

            // Replace palceholder __TOOLS_ROOT__
            $content = \CodeRage\File\getContents($dest);
            $content = str_replace('__TOOLS_ROOT__', realpath($path), $content);
            file_put_contents($dest, $content);

            // Install file as local package, if appropriate
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $found = false;
            foreach ($managers as $man) {
                  if ($man->libraryFileExtension() == $ext) {
                      $found = true;
                      $configs = $man->lookupConfigurations();
                      foreach ($configs as $con) {
                          try {
                            $con->addToLibrarySearchPath($path);
                          } catch (Throwable $ignore) {
                              try {
                                $con->installLocalPackage([$dest], true);
                              } catch (Throwable $e) {
                                  if ($str = $run->getStream(Log::WARNING)) {
                                        $framework = $man->name();
                                        $id = $con->configurationId();
                                        $str->write(
                                           "Failed adding file '$path' to " .
                                           "library search path for framework " .
                                           "'$framework' ($id)"
                                                        );
                                    $str->write(
                                       "Failed installing file '$file' " .
                                       "as a local package in framework " .
                                       "'$framework' ($id): " .
                                        $e->getMessage()
                                    );
                                  }
                              }
                          }
                      }
                  }
            }
        }
        if (!$found) {
            if ($str = $run->getStream(Log::ERROR))
                $str->write("No package manager found for extension '$ext'");
        }

        // Copy executable files
        foreach ($tools as $file) {
            $src = Resource_::loadFile($run, $file);
            $dest = "$path/Bin/$file";
            if (!$this->handler->callUserFunction('copy', $src, $dest))
                if ($str = $run->getStream(Log::ERROR))
                    $str->write(
                        $this->handler->formatError(
                            "Failed copying tool '$file' to '$path'"
                        )
                    );
            $mode = self::BINARY_FILE_PERMISSIONS;
            if (!$this->handler->callUserFunction('chmod', $dest, $mode))
                if ($str = $run->getStream(Log::ERROR))
                    $str->write(
                        $this->handler->formatError(
                            "Failed setting permissions of file '$dest'"
                        )
                    );
        }

        // Copy makeme.php
        $src = __FILE__;
        $dest = "$path/Bin/makeme.php";
        if (!$this->handler->callUserFunction('copy', $src, $dest))
            if ($str = $run->getStream(Log::ERROR))
                $str->write(
                    $this->handler->formatError(
                        "Failed copying tool '$file' to '$path'"
                    )
                );
        $mode = self::BINARY_FILE_PERMISSIONS;
        if (!$this->handler->callUserFunction('chmod', $dest, $mode))
            if ($str = $run->getStream(Log::ERROR))
                $str->write(
                    $this->handler->formatError(
                        "Failed setting permissions of file '$dest'"
                    )
                );

        // Generate README
        $nl = $os == 'windows' ? "\r\n" : "\n";
        $now = time();
        $date = date(\CodeRage\Build\BuildConfig::DATE_FORMAT, $now);
        $year = date('Y', $now);
        $version = VERSION;
        $content =
            "#################################################$nl" .
            "#$nl" .
            "# CodeRage Bootstrap Files$nl" .
            "#$nl" .
            "# Date:          $date$nl" .
            "# Version:       $version$nl" .
            "# Copyright:     $year CodeRage$nl" .
            "# License:       All rights reserved$nl" .
            "#$nl" .
            "#################################################$nl";
        if ( !$this->handler->callUserFunction(
                 'file_put_contents',
                 "$path/README",
                 $content) )
        {
            if ($str = $run->getStream(Log::ERROR))
                $str->write(
                    $this->handler->formatError(
                        "Failed generating README in directory '$path'"
                    )
                );
        }

        // Make sure command-line tools are in PATH
        $files = [];
        foreach ($tools as $t)
            $files[] = "$path/Bin/$t";
        \CodeRage\Build\addToPath($files);
    }

    /**
     * Returns the default location of the CodeRage tools or bootstrap files.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     */
    private function defaultInstallPath(Run $run)
    {
        $base = 'CodeRage-' . VERSION;
        return os() == 'windows' ?
            getenv('PROGRAMFILES') . "\\$base" :
            "/usr/share/$base";
    }

    /**
     * Returns the URL of the CodeRage Git repository.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     */
    private function toolsUrl(Run $run)
    {
        $config = $run->buildconfig();
        return $config->repositoryUrl() ?
            $config->repositoryUrl() :
            \CodeRage\Build\REPO_URL;
    }

    /**
     * Returns the Git ref of the CodeRage tools
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return string
     */
    private function toolsBranch(Run $run)
    {
        $config = $run->buildconfig();
        return $config->repositoryBranch() ?
            $config->repositoryBranch() :
            \CodeRage\Build\REPO_BRANCH;
    }
}
