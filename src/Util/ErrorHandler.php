<?php

/**
 * Defines the class CodeRage\Util\ErrorHandler
 * 
 * File:        CodeRage/Util/ErrorHandler.php
 * Date:        Thu Oct 25 11:07:48 MDT 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use Exception;
use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Simplifies handling PHP errors, warnings, and notices
 */
final class ErrorHandler {

    /**
     * @var int
     */
    private const NOTICE = E_NOTICE | E_USER_NOTICE | E_STRICT;

    /**
     * @var int
     */
    private const WARNING =
        E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING;

    /**
     * @var int
     */
    private const ERROR =
        E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR |
        E_RECOVERABLE_ERROR | E_USER_ERROR;

    /**
     * A bitwise OR of zero or more E_XXX constants
     *
     * @var int
     */
    private $level;

    /**
     * A regular expressions matching error messages that should be ignored
     *
     * @var string
     */
    private $ignorePattern;

    /**
     * A callback taking an error message returning true for errors that should
     * be ignored
     *
     * @var callable
     */
    private $ignoreCallback;

    /**
     * An associative array with keys 'errno', 'errstr', 'errfile', 'errline',
     * and 'errcontext', or null if no error has been handled.
     *
     * @var array
     */
    private $properties;

    /**
     * Constructs a CodeRage\Util\ErrorHandler.
     *
     * @param mixed $options a bitwise OR of E_XXX constants (see below), or an
     *     associative array of options with keys among:
     *   level - A bitwise OR of zero or more E_XXX constants, indicating which
     *     PHP errors, warnings, and notices should cause the error handler
     *     under construction to set its errno property to a non-zero value
     *     (defaults to E_ALL & ~E_DEPRECATED)
     *   ignorePattern - A regular expressions matching error messages that
     *     should be ignored
     *   ignoreCallback - A callback taking an error message returning true
     *     for errors that should be ignored
     */
    public function __construct($options = null)
    {
        if (!is_array($options))
            $options = ['level' => $options];
        if ( isset($options['ignorePattern']) &&
             isset($options['ignoreCallback']) )
        {
            throw new
                \CodeRage\Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'ignorePattern' and " .
                        "'ignoreCallback'"
                ]);
        }
        $this->level = isset($options['level']) ?
            $options['level'] :
            ( defined('E_DEPRECATED') ?
                E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED :
                E_ALL );
        $this->ignorePattern = isset($options['ignorePattern']) ?
            $options['ignorePattern'] :
            null;
        $this->ignoreCallback = isset($options['ignoreCallback']) ?
            $options['ignoreCallback'] :
            null;
    }

    /**
     * Returns the level of the error raised.
     *
     * @return int
     */
    public function errno()
    {
        return $this->properties ? $this->properties['errno'] : null;
    }

    /**
     * Returns the error message.
     *
     * @return string
     */
    public function errstr()
    {
        return $this->properties ? $this->properties['errstr'] : null;
    }

    /**
     * Returns the filename that the error was raised in.
     *
     * @return string
     */
    public function errfile()
    {
        return $this->properties ? $this->properties['errfile'] : null;
    }

    /**
     * Returns the line number the error was raised at.
     *
     * @return int
     */
    public function errline()
    {
        return $this->properties ? $this->properties['errline'] : null;
    }

    /**
     * Returns an array that points to the active symbol table at the point
     * the error occurred.
     *
     * @return string
     */
    public function errcontext()
    {
        return $this->properties ? $this->properties['errcontext'] : null;
    }

    /**
     * Returns true if the underlying error number represents an error.
     *
     * @return boolean
     */
    public function hasError()
    {
        return ($this->errno & self::ERROR) != 0;
    }

    /**
     * Returns true if the underlying error number represents a warning.
     *
     * @return boolean
     */
    public function hasWarning()
    {
        return ($this->errno & self::WARNING) != 0;
    }

    /**
     * Returns true if the underlying error number represents a notice.
     *
     * @return boolean
     */
    public function hasNotice()
    {
        return ($this->errno & self::NOTICE) != 0;
    }

    /**
     * Sets this handlers error fields to null.
     */
    public function reset()
    {
        $this->properties = null;
    }

    /**
     * Alias for callUserFunction()
     */
    public function call()
    {
        $args = func_get_args();
        return $this->callUserFunction(...$args);
    }

    /**
     * Calls the function given as the first parameter. If no error occurs,
     * returns the result of calling the specified function; otherwise, returns
     * false and sets the properties of this CodeRage\Util\ErrorHandler to match the
     * first error, warning, or notice generated during the execution of the
     * function.
     */
    public function callUserFunction()
    {
        $args = func_get_args();
        $func = array_shift($args);
        return $this->callUserFunctionArray($func, $args);
    }

    /**
     * Calls the function given as the first parameter, with the argument
     * list given by the second parameter. If no error occurs,
     * returns the result of calling the specified function; otherwise, returns
     * false and sets the properties of this CodeRage\Util\ErrorHandler to match the
     * first error, warning, or notice generated during the execution of the
     * function.
     *
     * @param callback $func
     * @param array $args
     * @param int $level The error level; defaults to
     *   E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED
     */
    public function callUserFunctionArray($func, $args)
    {
        // Set handler
        $this->reset();
        set_error_handler(
            function(...$args)
            {
                $this->handleError(...$args);
            },
            $this->level
        );

        // Call function
        $result = null;
        try {
            try {
                $result = @call_user_func_array($func, $args);
            } catch (\Error $e) {
                $this->handleError(
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    []
                );
            }
        } catch (ErrorHandlerException $e) {
            $result = false;
        } finally {
            restore_error_handler();
        }

        // Return result
        return $result;
    }

    /**
     * Returns a formatted error message formed by combining the given error
     * message, if any, with an error message based on the values of this
     * error handler's properties.
     *
     * @param string $message An error message
     * @return string
     */
    public function formatError($message = null)
    {
        return $this->errno() ?
            ($message ?  "$message: " : '') .
                $this->errorCategory($this->errno()) .
                ": {$this->errstr()} in {$this->errfile()} " .
                "on line {$this->errline()}" :
            $message;
    }

    /**
     * Handles method calls of the form $handler->_xxx(...) where xxx is a
     * function or method.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws CodeRage\Error
     */
    public function __call($name, $arguments)
    {
        if (strlen($name) < 2 || $name[0] != '_')
            throw new
                \CodeRage\Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'details' => "Invalid method: '$name'"
                ]);
        $func = substr($name, 1);
        if (function_exists($func)) {
            return $this->callUserFunctionArray($func, $arguments);
        } elseif (sizeof($arguments) > 0) {
            $callback = [array_shift($arguments), $func];
            if (is_callable($callback)) {
                return $this->callUserFunctionArray($callback, $arguments);
            } else {
                throw new
                    \CodeRage\Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The array [" . Error::formatValue($callback[0]) .
                            ",'{$callback[1]}'] is not a valid callback"
                    ]);
            }
        } else {
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "'$name' is not callable"
                ]);
        }
    }

    /**
     * Translates the given E_XXX constant into a human readable label.
     *
     * @param int $errno An error level
     * @return string
     */
    public static function errorCategory($errno)
    {
        $category =
            [ // From pearcmd.php
                E_ERROR => 'Error',
                E_WARNING => 'Warning',
                E_PARSE => 'Parsing Error',
                E_NOTICE => 'Notice',
                E_CORE_ERROR => 'Core Error',
                E_CORE_WARNING => 'Core Warning',
                E_COMPILE_ERROR => 'Compile Error',
                E_COMPILE_WARNING => 'Compile Warning',
                E_USER_ERROR => 'User Error',
                E_USER_WARNING => 'User Warning',
                E_USER_NOTICE => 'User Notice',
                E_STRICT => 'Strict Standards',
                E_RECOVERABLE_ERROR => 'Recoverable Error',
            ];
        if (defined('E_DEPRECATED')) {
            $category[E_DEPRECATED] = 'Deprecation Warning';
            $category[E_USER_DEPRECATED] = 'User Deprecation Warning';
        }
        return $category[$errno];
    }

    /**
     * Registers an error handler that throws instances of CodeRage\Error
     *
     * @param int $errno The error level; defaults to
     *   E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED
     * @return string
     */
    public static function register($level = null)
    {
        if ($level === null)
            $level = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;
        set_error_handler(
            function($errno, $errstr, $errfile, $errline) use($level)
            {
                if (($errno & $level) != 0) {
                    $category = self::errorCategory($errno);
                    throw new
                        \CodeRage\Error([
                            'status' => 'PHP_ERROR',
                            'details' =>
                                "PHP $category: $errstr in $errfile on line " .
                                $errline
                        ]);
                }
                return true;
            },
            $level
        );
    }

    /**
     * Sets the properties of this error handler to match the given values.
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
     * @param string $errcontext An array that points to the active symbol
     * table at the point the error occurred.
     */
    private function handleError($errno, $errstr, $errfile, $errline, $errcontext)
    {
        if ($this->ignorePattern && preg_match($this->ignorePattern, $errstr))
            return;
        if (($callback = $this->ignoreCallback) && $callback($errstr))
            return;
        if ($errno & $this->level) {
            $this->properties =
                [
                    'errno' => $errno,
                    'errstr' => $errstr,
                    'errfile' => $errfile,
                    'errline' => $errline,
                    'errcontext' => $errcontext
                ];
            throw new ErrorHandlerException;
        }
    }
}
