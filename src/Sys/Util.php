<?php

/**
 * Defines the class CodeRage\Sys\Util
 *
 * File:        CodeRage/Sys/Util.php
 * Date:        Wed Nov 11 16:33:32 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use InvalidArgumentException;
use ReflectionClass;
use UnexpectedValueException;
use Composer\Semver\VersionParser;
use CodeRage\Error;
use CodeRage\Util\Args;

use function CodeRage\Util\Args\isIndexed;

/**
 * Provides static utility methods
 */
final class Util
{
    /**
     * @var array
     */
    public const EVENT_MODES = [
        Event\BuildEventInterface::class,
        Event\InstallEventInterface::class,
        Event\RunEventInterface::class
    ];

    /**
     * @var string
     */
    private const MATCH_IDENTIFIER = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';

    /**
     * @var array
     */
    private const DATASOURCE_PROPERTIES = [
        'name' => 1,
        'type' => 1,
        'schemaVersion' => 1
    ];

    /**
     * Returns the project root directory
     *
     * @return string
     */
    public static function getProjectRoot(): string
    {
        for ($dir = getcwd() ; ; $dir = $parent) {
            if (file_exists(File::join($dir, 'composer.json'))) {
                return $dir;
            }
            if (($parent = dirname($dir)) == $dir) {
                break;
            }
        }
        throw new \Exception(
            "Can't determine project root: no composer.json found in " .
                "current directory or its ancestors"
        );
    }

    /**
     * Throws an exception if the given value is not a legal identifier
     *
     * @param string $value The value to test
     * @param string $message The error message
     */
    public static function validateIdentifier(string $value, string $message): void
    {
        if (!preg_match(self::MATCH_IDENTIFIER, $value)) {
            throw new InvalidArgumentException("$message: invalid identifier '$value'");
        }
    }

    /**
     * Throws an exception if the given string is not the name of a class that
     * is already loaded or can be found by autoloading
     *
     * @param string $class The value to be tested
     * @param string $message The error message
     * @throws InvalidArgumentException
     */
    public static function validateClass(string $class, string $message): void
    {
        $parts = explode('\\', $class);
        foreach ($parts as $i => $p) {
            if (strlen($p) == 0) {
                if ($i > 0 || count($parts) == 1) {
                    throw new InvalidArgumentException("$message: invalid class name '$class'");
                }
            } else {
                self::validateIdentifier($p);
            }
        }
        spl_autoload_call($class);
        if (!class_exists($class)) {
            throw new InvalidArgumentException("$message: class '$class' not found");
        }
    }

    /**
     * Throws an exception if the return values of the methods of the given
     * module declared in the interface CodeRage\Sys\Module are well-formed.
     * Does not verify that bindings are available in the service container
     * for event handler parameterw.
     *
     *
     * @param CodeRage\Sys\Module
     * @throws InvalidArgumentException
     */
    public static function validateModule(Module $module): void
    {
        $name = get_class($module);
        self::validateVersion($module->getVersion(), $name);
        self::validateDependencies($module->getDependencies(), $name);
        self::validateReplaces($module->getReplaces(), $name);
        self::validateEventHandlers($module->getEventHandlers(), $name);
        self::validateDataSources($module->getDataSources(), $name);
    }

    /**
     * Throws an exception if the given value is not the name of a class
     * implementing module
     *
     * @param string $version The value to test
     * @throws InvalidArgumentException
     */
    public static function validateModuleName(string $class): void
    {
        spl_autoload_call($class);
        if (!class_exists($class)) {
            throw new InvalidArgumentException(
                "Invalid module: class '$class' not found"
            );
        }
        $reflect = ReflectionClass($class);
        if (!$reflect->implementsInterface(ModuleInterface::class)) {
            throw new InvalidArgumentException(
                "Invalid module: the class '$class' is not a module"
            );
        }
        $constr = $reflect->getConstructor();
        if ($constr !== null && $constr->getNumberOfRequiredParameters() > 0) {
            throw new InvalidArgumentException(
                "Invalid module: the class '$class' is not default constructible"
            );
        }
    }

    /**
     * Throws an exception if the given value is not the name of a valid event
     * class
     *
     * @param string $version The value to test
     * @throws InvalidArgumentException
     */
    public static function validateEventName(string $class): void
    {
        spl_autoload_call($class);
        if (!class_exists($class)) {
            throw new InvalidArgumentException(
                "Invalid event: class '$class' not found"
            );
        }
        $reflect = new ReflectionClass($class);
        $count = 0;
        foreach (self::EVENT_MODES as $mode) {
            if ($reflect->implementsInterface($mode)) {
                ++$count;
            }
        }
        if ($count == 0) {
            throw new InvalidArgumentException(
                "Invalid event: class '$class' does not implement any " .
                    "of the mode interfaces " . join(', ', self::EVENT_MODES)
            );
        } elseif ($count > 1) {
            throw new InvalidArgumentException(
                "Invalid event: class '$class' implements more than one " .
                    "of the mode interfaces " . join(', ', self::EVENT_MODES)
            );
        }
    }

    /**
     * Throws an exception if the given value is not a well-formed semantic
     * version number
     *
     * @param string $version The value to test
     * @param string $module The module name, for use in error messages
     * @throws InvalidArgumentException
     */
    public static function validateVersion(string $version, ?string $module = null): void
    {
        try {
            self::versionParser()->normalize($version);
        } catch (UnexpectedValueException $e) {
            $forModule = $module !== null ? "for module '$module'" : '';
            throw new InvalidArgumentException("Invalid version$forModule: $version");
        }
    }

    /**
     * Throws an exception if the given value is not a well-formed version
     * constraints specifier
     *
     * @param string $constraints The value to test
     * @param string $module The module name, for use in error messages
     * @throws InvalidArgumentException
     */
    public static function validateVersionConstraints(string $constraints, ?string $module = null): void
    {
        try {
            self::versionParser()->parseConstraints($version);
        } catch (UnexpectedValueException $e) {
            $forModule = $module !== null ? "for module '$module'" : '';
            throw new InvalidArgumentException(
                "Invalid version constaints specifier$forModule: $constraints"
            );
        }
    }

    /**
     * Throws an exception if the given value is not a well-formed dependency
     * list
     *
     * @param string $version The value to test
     * @param string $module The module name, for use in error messages
     * @throws InvalidArgumentException
     */
    public static function validateDependencies(array $dependencies, ?string $module = null): void
    {
        $forModule = $module !== null ? "for module '$module'" : '';
        self::validateDependenciesImpl(
            $replaces,
            "Invalid dependency list$forModule"
        );
    }

    /**
     * Throws an exception if the given value is not a well-formed replacement
     * list
     *
     * @param string $replaces The value to test
     * @param string $module The module name, for use in error messages
     * @throws InvalidArgumentException
     */
    public static function validateReplaces(array $replaces, ?string $module = null): void
    {
        $forModule = $module !== null ? "for module '$module'" : '';
        self::validateDependenciesImpl(
            $replaces,
            "Invalid replacement list$forModule"
        );
    }

    /**
     * Throws an exception if the given value is not a well-formed event
     * handlers collection
     *
     * @param string $handlers The value to test
     * @param string $module The module name; if supplied, more validation is
     *   performed
     * @throws InvalidArgumentException
     */
    public static function validateEventHandlers(array $handlers, ?string $module = null): void
    {
        if ($module !== null) {
            validateModuleName($module);
        }
        $forModule = $module !== null ? "for module '$module'" : '';
        if (isIndexed($handlers)) {
            throw new InvalidArgumentException(
                "Invalid event handler collection$forModule: expected " .
                    "associative array; found list"
            );
        }
        foreach ($handlers as $event => $spec) {
            self::validateClass(
                $event,
                "Invalid event handler collection$forModule"
            );
            if (!(new \ReflectionClass($event))->isFinal()) {
                throw new InvalidArgumentException(
                    "Invalid event handler collection$forModule: the class " .
                        "'$event' is not final"
                );
            }
            $messsage = "Invalid handler for event '$event'$forModule";
            if (is_string($spec)) {
                self::validateIdentifier($spec, $messsage);
                if ($module !== null && !method_exists($module, $spec)) {
                    throw new InvalidArgumentException(
                        "$messsage: module has no '$spec' method"
                    );
                }
            } elseif (is_array($spec)) {
                self::validateList($spec, 2, $message);
                [$class, $method] = $spec;
                self::validateClass($class, $message);
                self::validateIdentifier($method, $message);
                if (!method_exists($class, $method)) {
                    throw new InvalidArgumentException(
                        "$message: class '$class' has no '$method' method"
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    "$message: expected string or list; found " .
                        Error::formatValue($spec)
                );
            }
        }
    }

    /**
     * Throws an exception if the given value is not a well-formed list of data
     * source specifiers
     *
     * @param string $dataSources The value to test
     * @param string $module The module name, for use in error messages
     *   performed
     * @throws InvalidArgumentException
     */
    public static function validateDataSources(array $dataSources, ?string $module = null): void
    {
        $forModule = $module !== null ? "for module '$module'" : '';
        if (!empty($dataSources) && !isIndexed($dataSources)) {
            throw new InvalidArgumentException(
                "Invalid list of data source specifiers$forModule: expected " .
                    "list; found associative array"
            );
        }
        foreach ($dataSources as $spec) {
            if (!is_array($spec)) {
                throw new InvalidArgumentException(
                    "Invalid data source specifier$forModule: expected " .
                        "associative array; found " . Error::formatValue($spec)
                );
            }
            if (!empty($spec) && IsIndexed($spec)) {
                throw new InvalidArgumentException(
                    "Invalid data source specifier$forModule: expected " .
                        "associative array; found list"
                );
            }
            foreach (array_keys(self::DATASOURCE_PROPERTIES) as $n) {
                if (!isset($spec[$n])) {
                    throw new InvalidArgumentException(
                        "Invalid data source specifier$forModule: " .
                            "missing '$n' property"
                    );
                }
            }
            foreach ($spec as $n => $v) {
                if (!array_key_exists($n, self::DATASOURCE_PROPERTIES)) {
                    throw new InvalidArgumentException(
                        "Invalid data source specifier$forModule: " .
                            "unsuppported property '$n'"
                    );
                }
            }
            ['name' => $name, 'type' => $type, 'schemaVersion' => $version] = $spec;
            if (!is_string($name)) {
                throw new InvalidArgumentException(
                    "Invalid data source name$forModule: expected string; " .
                        "found " . Error::formatValue($name)
                );
            }
            if (!ctype_alnum($name) || !preg_match(self::MATCH_IDENTIFIER, $name)) {
                throw new InvalidArgumentException(
                    "Invalid data source name$forModule: expected " .
                        "identifier; found '$name'"
                );
            }
            if (!is_string($type)) {
                throw new InvalidArgumentException(
                    "Invalid data source type$forModule: expected string; " .
                        "found " . Error::formatValue($name)
                );
            }
            if (!ctype_alnum($type)) {
                throw new InvalidArgumentException(
                    "Invalid data source type$forModule: $type"
                );
            }
            if (!is_int($schemaVersion)) {
                throw new InvalidArgumentException(
                    "Invalid data source schema version$forModule: expected " .
                        "positive integer; found " . Error::formatValue($name)
                );
            }
            if ($schemaVersion <= 0) {
                throw new InvalidArgumentException(
                    "Invalid data source schema version$forModule: expected " .
                        "positive integer; found $schemaVersion"
                );
            }
        }
    }

    /**
     * Returns a version parser
     *
     * @return Composer\Semver\VersionParser
     */
    private static function versionParser()
    {
        static $parser;
        if ($parser === null) {
            $parser = new VersionParser;
        }
        return $parser;
    }

    /**
     * Helper method for validateDependencies() and validateReplaces()
     */
    private static function validateDependenciesImpl($list, string $message)
    {
        if (!empty($list) && !isIndexed($list)) {
            throw new InvalidArgumentException(
                "$message: expected list; found associative array"
            );
        }
        foreach ($list as $item) {
            self::validateList($list, 2, $message);
            [$m, $c] = $item;
            validateModuleName($m);
            validateVersionConstraints($c);
        }
    }

    /**
     * Throws an exception if the given value is not an indexed array of the
     * specified length
     *
     * @param mixed $list The value to be tested
     * @param int $length The expected length
     * @param string $message The error message
     */
    private static function validateList($list, int $length, string $message): void
    {
        if (!is_array($list)) {
            throw new InvalidArgumentException(
                "$message: expected array; found " . Error::formatValue($list)
            );
        }
        if (!isIndexed($item)) {
            throw new InvalidArgumentException(
                "$message: expected list; found associative array"
            );
        }
        if (count($item) != $length) {
            throw new InvalidArgumentException(
                "$message: expected list of length $length; found list of length " . count($item)
            );
        }
    }
}
