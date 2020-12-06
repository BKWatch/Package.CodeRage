<?php

/**
 * Defines the class CodeRage\Build\BuildConfig
 * 
 * File:        CodeRage/Build/BuildConfig.php
 * Date:        Thu Jan 01 18:33:48 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use Exception;
use Throwable;
use CodeRage\Error;
use function CodeRage\Text\split;
use function CodeRage\Util\printScalar;

/**
 * @ignore
 */
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/File/find.php');
require_once('CodeRage/File/generate.php');
require_once('CodeRage/File/getContents.php');
require_once('CodeRage/File/isAbsolute.php');
require_once('CodeRage/File/searchIncludePath.php');
require_once('CodeRage/Util/os.php');
require_once('CodeRage/Util/printScalar.php');
require_once('CodeRage/Util/system.php');

/**
 * Stores information about past or current invocations of makeme
 */
class BuildConfig {

    /**
     * Space-separated list of files expected to be found at the bootstrap
     * path.
     *
     * @var string
     */
    const BOOTSTRAP_FILES =
        'Bin CodeRage.php CodeRage-3.0.php CodeRage.pm README';

    /**
     * Space-separated list of files expected to be found at the tools
     * path.
     *
     * @var string
     */
    const TOOLS_FILES = 'CodeRage/Build CodeRage/Util CodeRage/project.xml';

    /**
     * The name of the environment variable used to specify the directory
     * containing the system configuration file.
     *
     * @var string
     */
    const CONFIG_FILE_ENV_VARIABLE = 'CODERAGE_CONFIG';

    /**
     * The directory expected to contain the system configuration file on UNIX,
     * if it is not specified on the command line or in the environment.
     *
     * @var string
     */
    const CONFIG_FILE_DIRECTORY_UNIX = '/etc';

    /**
     * Space-separated list of suitable filenames for system configuration
     * files.
     *
     * @var string
     */
    const SYSTEM_CONFIG_FILES = 'coderage.xml coderage.ini site-config.php';

    /**
     * Space-separated list of suitable filenames for project configuration
     * files.
     *
     * @var string
     */
    const CONFIG_FILES = 'project.xml project.ini';

    /**
     * Date format for use by __toString(), in the format accepted by the
     * built-in date() function.
     *
     *@var string
     */
    const DATE_FORMAT = 'D M j, H:m:s T Y';

    /**
     * The time this configuration was created or saved, as a UNIX timestamp
     *
     * @var int
     */
    private $timestamp;

    /**
     * The most recently completed build action, excluding 'help' and 'info'.
     *
     * @var string
     */
    private $action;

    /**
     * true if the most recently completed build action was successful.
     *
     * @var boolean
     */
    private $status;

    /**
     * Location of the CodeRage bootstrap files
     *
     * @var string
     */
    private $bootstrapPath;

    /**
     * Location of the CodeRage bootstrap files
     *
     * @var string
     */
    private $bootstrapVersion;

    /**
     * Location of the CodeRage tools
     *
     * @var string
     */
    private $toolsPath;

    /**
     * Version of the CodeRage tools
     *
     * @var string
     */
    private $toolsVersion;

    /**
     * The system-wide configuration file
     *
     * @var CodeRage\Build\BuildConfigFile
     */
    private $systemConfigFile;

    /**
     * The project definition file
     *
     * @var CodeRage\Build\BuildConfigFile
     */
    private $projectConfigFile;

    /**
     * The list of additional project-specific configuration files, as an
     * array of instances of CodeRage\Build\BuildConfigFile
     *
     * @var array
     */
    private $additionalConfigFiles;

    /**
     * The repository type; currently only "git" is supported
     *
     * @var string
     */
    private $repositoryType;

    /**
     * The repository URL
     *
     * @var string
     */
    private $repositoryUrl;

    /**
     * The Git ref currently checked out or to be checked out
     *
     * @var string
     */
    private $repositoryBranch;

    /**
     * An associative array of configuration variables specified on the
     * command line
     *
     * @var array
     */
    private $commandLineProperties;

    /**
     * An associative array of configuration variables specified in the
     * environment
     *
     * @var array
     */
    private $environmentProperties;

    /**
     * An instance of CodeRage\Build\Info
     *
     * @var array
     */
    private $projectInfo;

    /**
     * Constructs a CodeRage\Build\BuildConfig.
     *
     * @param int $timestamp The time this configuration was created or
     * saved, as a UNIX timestamp
     * @param string $action The most recently completed build action, excluding
     * 'help' and 'info'.
     * @param boolean $status true if the most recently completed build action
     * was successful.
     * @param string $bootstrapPath Location of the CodeRage bootstrap files
     * @param string $bootstrapVersion Version the CodeRage bootstrap files
     * @param string $toolsPath Location of the CodeRage tools
     * @param string $toolsVersion Version of the CodeRage tools
     * @param CodeRage\Build\BuildConfigFile $systemConfigFile The system-wide
     * configuration file
     * @param CodeRage\Build\BuildConfigFile $projectConfigFile The project
     * definition file
     * @param array $additionalConfigFiles The list of additional
     * project-specific configuration files, as an array of instances of
     * CodeRage\Build\BuildConfigFile
     * @param string $repositoryType The repository type; currently only
     * "git" is supported
     * @param string $repositoryUrl The repository URL
     * @param string $repository The Git ref currently checked out, or to be
     * checked out
     * @param array $commandLineProperties An associative array of
     * configuration variables specified on the command line
     * @param array $environmentProperties An associative array of
     * configuration variables specified in the environment
     * @param array $projectInfo An instance of CodeRage\Build\Info
     */
    public function __construct(
                $timestamp, $action, $status, $bootstrapPath, $bootstrapVersion,
                $toolsPath, $toolsVersion, $systemConfigFile,
                $projectConfigFile, $additionalConfigFiles, $repositoryType,
                $repositoryUrl, $repositoryBranch, $commandLineProperties,
                $environmentProperties, $projectInfo)
    {
        $this->timestamp = $timestamp;
        $this->action = $action;
        $this->status = $status;
        $this->bootstrapPath = $bootstrapPath;
        $this->bootstrapVersion = $bootstrapVersion;
        $this->toolsPath = $toolsPath;
        $this->toolsVersion = $toolsVersion;
        $this->systemConfigFile = is_array($systemConfigFile) ?
            new BuildConfigFile(
                    $systemConfigFile[0],
                    $systemConfigFile[1]
                ) :
            $systemConfigFile;
        $this->projectConfigFile = is_array($projectConfigFile) ?
            new BuildConfigFile(
                    $projectConfigFile[0],
                    $projectConfigFile[1]
                ) :
            $projectConfigFile;
        $files = [];
        foreach ($additionalConfigFiles as $f)
            $files[] = is_array($f) ?
                new BuildConfigFile($f[0], $f[1]) :
                $f;
        $this->additionalConfigFiles = $files;
        $this->repositoryType = $repositoryType;
        $this->repositoryUrl = $repositoryUrl;
        $this->repositoryBranch = $repositoryBranch;
        $this->commandLineProperties = $commandLineProperties;
        $this->environmentProperties = $environmentProperties;
        $this->projectInfo = $projectInfo;
    }

    /**
     * Returns the time this configuration was created or saved, as a UNIX
     * timestamp
     *
     * @return int
     */
    function timestamp()
    {
        return $this->timestamp;
    }

    /**
     * Returns the most recently completed build action, excluding 'help' and
     * 'info'.
     *
     * @return string
     */
    function action()
    {
        return $this->action;
    }

    /**
     * Sepcifies the most recently completed build action, excluding 'help' and
     * 'info'.
     *
     * @param string
     */
    function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Returns true if the most recently completed build action was successful.
     *
     * @param boolean
     */
    function status()
    {
        return $this->status;
    }

    /**
     * Sets the status of the most recently completed build action.
     *
     * @param boolean
     */
    function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the location of the CodeRage bootstrap files
     *
     * @return string
     */
    function bootstrapPath()
    {
        return $this->bootstrapPath;
    }

    /**
     * Sets the location of the CodeRage bootstrap files
     *
     * @param string $bootstrapPath
     */
    function setBootstrapPath($bootstrapPath)
    {
        $this->bootstrapPath = $bootstrapPath;
    }

    /**
     * Returns the version of the bootstrap files.
     *
     * @return string
     */
    function bootstrapVersion()
    {
        return $this->bootstrapVersion;
    }

    /**
     * Returns the location of the CodeRage tools
     *
     * @return string
     */
    function toolsPath()
    {
        return $this->toolsPath;
    }

    /**
     * Returns the version of the CodeRage tools
     *
     * @return string
     */
    function toolsVersion()
    {
        return $this->toolsVersion;
    }

    /**
     * Sets the location of the CodeRage tools
     *
     * @param string $toolsPath
     */
    function setToolsPath($toolsPath)
    {
        $this->toolsPath = $toolsPath;
    }

    /**
     * Returns the system-wide configuration file
     *
     * @return CodeRage\Build\BuildConfigFile
     */
    function systemConfigFile()
    {
        return $this->systemConfigFile;
    }

    /**
     * Sets the system-wide configuration file
     *
     * @param CodeRage\Build\BuildConfigFile $systemConfigFile
     */
    function setSystemConfigFile(BuildConfigFile $systemConfigFile)
    {
        $this->systemConfigFile = $systemConfigFile;
    }

    /**
     * Returns the project definition file
     *
     * @return CodeRage\Build\BuildConfigFile
     */
    function projectConfigFile()
    {
        return $this->projectConfigFile;
    }

    /**
     * Sets the project definition file
     *
     * @param CodeRage\Build\BuildConfigFile $projectConfigFile
     */
    function setProjectConfigFile(BuildConfigFile $projectConfigFile)
    {
        $this->projectConfigFile = $projectConfigFile;
    }

    /**
     * Returns the list of additional project-specific configuration files,
     * as an array of instances of CodeRage\Build\BuildConfigFile
     *
     * @return array
     */
    function additionalConfigFiles()
    {
        return $this->additionalConfigFiles;
    }

    /**
     * Sets the list of additional project-specific configuration files, as
     * an array of instances of CodeRage\Build\BuildConfigFile
     *
     * @param array $additionalConfigFiles
     */
    function setAdditionalConfigFiles($additionalConfigFiles)
    {
        $this->additionalConfigFiles = $additionalConfigFiles;
    }

    /**
     * Sets the system-wide configuration file and the list of additional
     * project-specific configuration files to match that of the given
     * configuration, if they are not currently set.
     *
     * @param CodeRage\Build\BuildConfig $config
     */
    function inheritConfigFiles(BuildConfig $config)
    {
        if (!$this->systemConfigFile)
            $this->systemConfigFile = $config->systemConfigFile;
        if (!sizeof($this->additionalConfigFiles))
            $this->additionalConfigFiles = $config->additionalConfigFiles;
    }

    /**
     * Returns the repository type; currently only "git" is supported
     *
     * @return string
     */
    function repositoryType()
    {
        return $this->repositoryType;
    }

    /**
     * Sets the repository type; currently only "git" is supported
     *
     * @param string $repositoryType
     */
    function setRepositoryType($repositoryType)
    {
        $this->repositoryType = $repositoryType;
    }

    /**
     * Returns the repository URL
     *
     * @return string
     */
    function repositoryUrl()
    {
        return $this->repositoryUrl;
    }

    /**
     * Sets the repository URL
     *
     * @param string $repositoryUrl
     */
    function setRepositoryUrl($repositoryUrl)
    {
        $this->repositoryUrl = $repositoryUrl;
    }

    /**
     * Returns the repository branch
     *
     * @return string
     */
    function repositoryBranch()
    {
        return $this->repositoryBranch;
    }

    /**
     * Sets the repository branch
     *
     * @param string $repositoryBranch
     */
    function setRepositoryBranch($repositoryBranch)
    {
        $this->repositoryBranch = $repositoryBranch;
    }

    /**
     * Sets repository properties that are currently null to match those of the
     * given configuration.
     *
     * @param CodeRage\Build\BuildConfig $config
     */
    function inheritRepositoryInfo(BuildConfig $config)
    {
        if ($this->repositoryType === null)
            $this->repositoryType = $config->repositoryType;
        if ($this->repositoryUrl === null)
            $this->repositoryUrl = $config->repositoryUrl;
        if ($this->repositoryBranch === null)
            $this->repositoryBranch = $config->repositoryBranch;
    }

    /**
     * Returns an associative array of configuration variables specified on
     * the command line
     *
     * @return array
     */
    function commandLineProperties()
    {
        return $this->commandLineProperties;
    }

    /**
     * Sets the associative array of configuration variables specified on the
     * command line.
     *
     * @param CodeRage\Build\ProjectConfig $config
     */
    function setCommandLineProperties(ProjectConfig $config)
    {
        $properties = [];
        foreach ($config->propertyNames() as $name) {
            $p = $config->lookupProperty($name);
            if ($p->setAt() == COMMAND_LINE)
                $properties[$name] = $p->value();
        }
        $this->commandLineProperties = $properties;
    }

    /**
     * Sets the associative array of configuration variables specified on the
     * command line based on the given build configuration.
     *
     * @param CodeRage\Build\BuildConfig $config
     */
    function inheritCommandLineProperties(BuildConfig $config)
    {
        $this->commandLineProperties = $config->commandLineProperties;
    }

    /**
     * Returns an associative array of configuration variables specified in
     * the environment.
     *
     * @return array
     */
    function environmentProperties()
    {
        return $this->environmentProperties;
    }

    /**
     * Sets the associative array of configuration variables specified in the
     * environment.
     *
     * @param CodeRage\Build\ProjectConfig $config
     */
    function setEnvironmentProperties(ProjectConfig $config)
    {
        $properties = [];
        foreach ($config->propertyNames() as $name) {
            $p = $config->lookupProperty($name);
            if ($p->setAt() == ENVIRONMENT)
                $properties[$name] = $p->value();
        }
        $this->environmentProperties = $properties;
    }

    /**
     * Sets the associative array of configuration variables specified in the
     * environment based on the given build configuration.
     *
     * @param CodeRage\Build\BuildConfig $config
     */
    function inheritEnvironmentProperties(BuildConfig $config)
    {
        $this->environmentProperties = $config->environmentProperties;
    }

    /**
     * Returns an instance of CodeRage\Build\Info
     *
     * @return CodeRage\Build\Info
     */
    function projectInfo()
    {
        return $this->projectInfo;
    }

    /**
     * Sets the instance o0f CodeRage\Build\Info, if any, associated with the current
     * run of the build system.
     *
     * @return CodeRage\Build\Info
     */
    function setProjectInfo($info)
    {
        $this->projectInfo = $info;
    }

    /**
     * Constructs and returns a CodeRage\Build\BuildConfig based on the current
     * build environment.
     *
     * @param CodeRage\Build\CommandLine $commandLine A parsed command line.
     * @param string $projectRoot The project root directory.
     */
    static function create($commandLine, $projectRoot)
    {
        $bootstrapPath = self::getBootstrapPath();
        $toolsPath = self::getToolsPath($projectRoot, $bootstrapPath);
        return new BuildConfig(
                       time(), $commandLine->action()->name(), false,
                       $bootstrapPath,
                       self::getBootstrapVersion($bootstrapPath),
                       $toolsPath,
                       self::getToolsVersion($toolsPath),
                       self::getSystemConfigFile($commandLine),
                       self::getProjectConfigFile($projectRoot),
                       self::getAdditionConfigFiles($commandLine, $projectRoot),
                       'git', $commandLine->getValue('repo-url'),
                       $commandLine->getValue('repo-branch'),
                       [], [], null
                   );
    }

    /**
     * Returns the location of the CodeRage bootstrap files.
     *
     * @return string
     */
    static function getBootstrapPath()
    {
        $coderage = \CodeRage\File\searchIncludePath('CodeRage.php', true);
        if (!$coderage)
            return null;
        $bootstrapPath = dirname($coderage);
        foreach (split(self::BOOTSTRAP_FILES) as $f)
            if (!file_exists("$bootstrapPath/$f"))
                return null;
        return realpath($bootstrapPath);
    }

    /**
     * Returns the version of the CodeRage bootstrap files.
     *
     * @param string $path The location of the CodeRage bootstrap files.
     * @return string
     */
    static function getBootstrapVersion($path)
    {
        if (!file_exists($path))
            return null;
        $readme = "$path/README";
        $content = \CodeRage\File\getContents($readme);
        $match = null;
        if (!preg_match('/Version:\\s+([0-9]+(?:\.[0-9])*)/', $content, $match))
            throw new
                Error(['message' =>
                    "Failed determining version of bootstrap files"
                ]);
        return $match[1];
    }

    /**
     * Returns the location of the CodeRage tools.
     *
     * @param string $projectRoot The project root directory.
     * @param string $bootstrapPath The location of the CodeRage bootstrap
     * files.
     * @return string
     */
    private static function getToolsPath($projectRoot, $bootstrapPath)
    {
        foreach ([$projectRoot, $bootstrapPath] as $path)
            if (file_exists("$path/CodeRage/project.xml"))
                return realpath($path);
    }

    /**
     * Returns the version of the CodeRage tools.
     *
     * @param string $path The location of the CodeRage tools.
     * @return string
     */
    private static function getToolsVersion($path)
    {
        if (!file_exists($path))
            return null;
        $file = addslashes("$path/CodeRage/Version.php");
        $command = "php -nr \"require_once('$file'); echo CodeRage" . "\\VERSION;\"";
        $version = null;
        try {
              $version = \CodeRage\Util\system($command);
        } catch (Throwable $e) {
            throw new
                Error(['message' =>
                    'Failed determining version of CodeRage tools: ' .
                    $e->getMessage()
                ]);
        }
        if (!preg_match('/[0-9]+(?:\.[0-9])*/', $version))
            throw new
                Error(['message' =>
                    "Failed determining version of CodeRage tools: invalid " .
                    "version string '$version'"
                ]);
        return $version;
    }

    /**
     * Returns the system configuration file.
     *
     * @param CodeRage\Build\CommandLine The command line.
     * @return CodeRage\Build\BuildConfigFile
     */
    private static function getSystemConfigFile($commandLine)
    {
        if ( ($directory = $commandLine->getValue('sys-config')) &&
             ($config = self::getSystemConfigFileImpl($directory)) )
        {
            return $config;
        }
        if ( ($directory = getenv(self::CONFIG_FILE_ENV_VARIABLE)) &&
             ($config = self::getSystemConfigFileImpl($directory)) )
        {
            return $config;
        }
        $directory = \CodeRage\Util\os() == 'Windows' ?
            getenv('WINDIR') :
            self::CONFIG_FILE_DIRECTORY_UNIX;
        return self::getSystemConfigFileImpl($directory);
    }

    /**
     * Searches for the systme configuration file in the given directory.
     *
     * @param CodeRage\Build\CommandLine The command line.
     * @return CodeRage\Build\BuildConfigFile
     */
    private static function getSystemConfigFileImpl($directory)
    {
        if (!\CodeRage\File\isAbsolute($directory))
            throw new
                Error([
                    'message' =>
                        "Invalid location of system-wide CodeRage " .
                        "configuration file: expected an absolute path; " .
                        "found '$directory'"
                ]);
        foreach (split(self::SYSTEM_CONFIG_FILES ) as $f)
            if (is_file("$directory/$f"))
                return new BuildConfigFile("$directory/$f");
        return null;
    }

    /**
     * Returns the project configuration file.
     *
     * @param string $projectRoot The project root directory.
     * @return CodeRage\Build\BuildConfigFile
     */
    private static function getProjectConfigFile($projectRoot)
    {
        foreach (split(self::CONFIG_FILES) as $f)
            if (is_file("$projectRoot/$f"))
                return new BuildConfigFile("$projectRoot/$f");
        return null;
    }

    /**
     * Returns an array of instance of CodeRage\Build\BuildConfigFile.
     *
     * @param CodeRage\Build\CommandLine The command line.
     * @param string $projectRoot The project root directory.
     * @return array
     */
    private static function getAdditionConfigFiles($commandLine, $projectRoot)
    {
        $config = $commandLine->getValue('config');
        if ($config !== null) {
            $path =
                \CodeRage\File\find(
                    $config, [$projectRoot], false, true
                );
            return [new BuildConfigFile($path)];
        } else {
            return [];
        }
    }

    /**
     * Loads and returns the stored build configuration associated with the
     * given project; if no build configuration has been stored, returns an
     * instance of CodeRage\Build\BuildConfig will empty values.
     *
     * @param string $projectRoot The project root directory
     * @return CodeRage\Build\BuildConfig
     */
    static function load($projectRoot)
    {
        $file = "$projectRoot/.coderage/history.php";
        if (!file_exists($file))
            return new BuildConfig(
                           0, null, false, null, null, null, null, null, null,
                           [], 'git', null, null, [], [], null
                       );
        \CodeRage\File\checkReadable($file);
        global $config;  // set in $file
        include($file);
        if (!isset($config) || !is_array($config) || sizeof($config) != 15)
            throw new
                Error(['message' =>
                    "The file '$file' contains no build configuration"
                ]);
        return new
            BuildConfig(
                $config[0], $config[1], $config[2], $config[3], $config[4],
                $config[5], $config[6], $config[7], $config[8], $config[9],
                $config[10], $config[11], $config[12], $config[13], $config[14],
                null
            );
    }

    /**
     * Saves this build configuration.
     *
     * @param string $projectRoot The project root directory
     */
    function save($projectRoot)
    {
        $file = "$projectRoot/.coderage/history.php";
        $definition = $this->definition();
        $content = "\$config = $definition;\n";
        \CodeRage\File\generate($file, $content, 'php');
    }

    /**
     * Returns a PHP definition of this instance.
     *
     * @return string
     */
    function definition()
    {
        return "array($this->timestamp," .
               printScalar($this->action) . ',' .
               printScalar($this->status) . ',' .
               printScalar($this->bootstrapPath) . ',' .
               printScalar($this->bootstrapVersion) . ',' .
               printScalar($this->toolsPath) . ',' .
               printScalar($this->toolsVersion) . ',' .
               $this->printObject($this->systemConfigFile) . ',' .
               $this->printObject($this->projectConfigFile) . ',' .
               $this->printObject($this->additionalConfigFiles) . ',' .
               printScalar($this->repositoryType) . ',' .
               printScalar($this->repositoryUrl) . ',' .
               printScalar($this->repositoryBranch) . ',' .
               $this->printObject($this->commandLineProperties) . ',' .
               $this->printObject($this->environmentProperties) . ')';
    }

    function __toString()
    {
        $result =
            "Last build: " . date(self::DATE_FORMAT) . "\n" .
            "Build action: $this->action\n" .
            "Status: " . ($this->status ? 'success' : 'failure') . "\n" .
            "Bootstrap path: " . $this->printInfo($this->bootstrapPath)  .
            "Bootstrap version: " . $this->printInfo($this->bootstrapVersion)  .
            "Tools path: " . $this->printInfo($this->toolsPath)  .
            "Tools version: " . $this->printInfo($this->toolsVersion)  .
            "CodeRage config file: " .
                ( $this->systemConfigFile() ?
                      $this->printInfo($this->systemConfigFile()->path()) :
                      "<none>\n" ) .
            "Project config file: " .
                ( $this->projectConfigFile() ?
                      $this->printInfo($this->projectConfigFile()->path()) :
                      "<none>\n" );
        if (sizeof($this->additionalConfigFiles)) {
            $result .= "Additional configuration files: \n";
            foreach ($this->additionalConfigFiles as $config)
                $result .= "  * " . $this->priprintInfontPath($config->path());
        }
        if (sizeof($this->commandLineProperties)) {
            $result .= "Command-line configuration: \n";
            foreach ($this->commandLineProperties as $n => $v)
                $result .= "  $n=" . printScalar($v) . "\n";
        }
        return $result;
    }

    /**
     * formats the given path for use by __toString().
     *
     * @param string $path
     * @return string
     */
    function printInfo($value)
    {
        return $value !== null ? "$value\n" : "n/a\n";
    }

    /**
     * Returns a PHP definition of the specified value. Arrays must be pure
     * associative or pure indexed; objects must support a 'defintion' method.
     *
     * @param mixed $value A scalar, array, or object.
     * @return string
     */
    private function printObject($value)
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $n => $v)
                $items[] = is_int($n) ?
                    $this->printObject($v) :
                    $this->printObject($n) . '=>' . $this->printObject($v);
            return 'array(' . join(',', $items) . ')';
        } elseif (is_object($value)) {
            return $value->definition();
        } elseif (is_string($value)) {
            return ctype_print($value) ?
                "'" . addcslashes($value, "\\'") . "'" :
                "base64_decode('" . base64_encode($value) . "')";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif ($value === null) {
            return 'null';
        } else {
            return strval($value);
        }
    }

    /**
     * Returns a PHP expression evaluating to the given value
     *
     * @param mixed $value A scalar or indexed array of scalars.
     */
    private static function printLiteral($value)
    {
        switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
        case 'integer':
        case 'double':
            return strval($value);
        case 'string':
            return ctype_print($value) ?
                "'" . addcslashes($value, "\\'") . "'" :
                "base64_decode('" . base64_encode($value) . "')";
        case 'array':
            $literals = [];
            foreach ($value as $v)
                $literals[] = self::printLiteral($v);
            return 'array(' . join(',', $literals) . ')';
        default:
            throw new
                Exception(
                    "Invalid property value: " . printScalar($value)
                );
        }
    }
}
