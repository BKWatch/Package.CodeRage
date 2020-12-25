<?php

/**
 * Defines the class CodeRage\Util\Factory
 *
 * File:        CodeRage/Util/Factory.php
 * Date:        Thu Sep 17 12:28:44 UTC 2020
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Util\Args;

/**
 * Container for static create, for creating class instances
 */
final class Factory {

    /**
     * @var string
     */
    private const MATCH_CLASS =
        '/^[_a-zA-Z][_a-zA-Z0-9]*([.\\][_a-zA-Z][_a-zA-Z0-9]*)*$/';

    /**
     * Attemps to load the named class if it doesn't exist and returns a boolean
     * indicating whether it exists
     *
     * @param string $lcass The claaa name
     * @return bool
     */
    public static function classExists(string $class) : bool
    {
        self::splAutoloadCall($class);
        return class_exists($class);
    }

    /**
     * Attemps to load the named class if it doesn't exist and returns a boolean
     * indicating whether it exists and has a method with the given name
     *
     * @param string $lcass The class name
     * @param string $method The method name
     * @return bool
     */
    public static function methodExists(string $class, string $method) : bool
    {
        self::splAutoloadCall($class);
        return method_exists($class, $method);
    }

    /**
     * Returns a newly constructed instance of the specified class
     *
     * @param array $options The options array; supports the following options:
     *   class - A class name, specified as a namespace-qualified class name or
     *     as sequence of identifiers separated by dots (required)
     *   params - An associative array or list of constructor parameters
     *     (optional)
     * @throws CodeRage\Error if the component class cannot be located or if
     *   the constructor throws an exception
     */
    public static function create(array $options)
    {
        $class =
            Args::checkKey($options, 'class', 'string', ['required' => true]);
        if (!preg_match(self::MATCH_CLASS, $class))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid class name: $class"
                ]);
        $class = str_replace('.', '\\', $class);
        $params =
            Args::checkKey($options, 'params', 'array', ['default' => []]);
        return isset($params[0]) ?
            new $class(...$params) :
            new $class($params);
    }

    /**
     * Wrapper for spl_autoload_call() that never calls the autoloader twice for
     * the same class
     *
     * @param string $class
     */
    private static function splAutoloadCall(string $class) : void
    {
        static $classes = [];
        if (!class_exists($class) && !isset($classes[$class])) {
            spl_autoload_call($class);
            $classes[$class] = 1;
        }
    }
}
