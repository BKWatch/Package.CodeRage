<?php

/**
 * Defines the class CodeRage\Error.
 *
 * File:        CodeRage/Error.php
 * Date:        Mon Jan 28 20:32:32 MST 2008
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use Exception;
use Throwable;

/**
 * General pupose exception class
 */
class Error extends Exception {

    /**
     * Error message for CodeRage\Error instances constructed without a message.
     *
     * @var string
     */
    private const DEFAULT_MESSAGE = 'An error occurred';

    /**
     * Status code for CodeRage\Error instances constructed without a status.
     *
     * @var string
     */
    private const DEFAULT_STATUS = 'INTERNAL_ERROR';

    /**
     * Constructs a CodeRage\Error.
     *
     * @param array $options The options array; supports the following options:
     *     status - A string containing a status code. Defaults to the status
     *       code of the error passed as the value of the option 'inner', if
     *       available, and to the string 'INTERNAL_ERROR' otherwise.
     *     message - An error message, suitable for display to an end user.
     *       Defaults to the error message associated with the value of the
     *       option 'status'.
     *     details - A detailed error message, suitable for display to a
     *       developer. Defaults to the value of the option 'message'.
     *     inner - An instance of Throwable representing the error
     *       condition, if any, that caused the current error.
     */
    public function __construct(array $options)
    {
        foreach (['status', 'message', 'details'] as $n) {
            if (isset($options[$n]) && !is_string($options[$n])) {
                $v = $options[$n];
                throw new
                    Exception(
                        "Invalid $n: expected string; found " .
                        (is_scalar($v) ? (string) $v :gettype($v))
                    );
            }
        }
        if (isset($options['inner']) && !$options['inner'] instanceof Throwable) {
            $v = $options[$n];
            throw new
                Exception(
                    "Invalid inner exception: expected Throwable; found " .
                    (is_scalar($v) ? (string) $v :gettype($v))
                );
        }

        // Apply defaults
        if (!isset($options['status'])) {
            if ( isset($options['inner']) &&
                 $options['inner'] instanceof Error )
            {
                $options['status'] = $options['inner']->status();
            } else {
                $options['status'] = self::DEFAULT_STATUS;
            }
        }
        if (!isset($options['message']))
            $options['message'] = self::translateStatus($options['status']);
        if (!isset($options['details']))
            $options['details'] = $options['message'];
        if (!isset($options['inner']))
            $options['inner'] = null;

        // Construct object
        parent::__construct($options['message'], 0, $options['inner']);
        $this->details = $options['details'];
        $this->status = $options['status'];
    }

    /**
     * Returns an error message suitable for display to an end user
     *
     * @return string
     */
    public function message() : string
    {
        return $this->getMessage();
    }

    /**
     * Returns a detailed error message, suitable for display to a developer.
     *
     * @return string
     */
    public function details() : ?string
    {
        return $this->details;
    }

    /**
     * Returns a status code, as a string.
     *
     * @return string
     */
    public function status() : string
    {
        return $this->status;
    }

    /**
     * Returns true if the underlying status code is equal to the given value.
     *
     * @param string $code A status code
     * @return boolean
     * @throws CodeRage\Error if $code is not a registered status code
     */
    public function statusEquals($code) : bool
    {
        self::loadStatusCodes();
        if ( sizeof(self::statusCodes) > 0 &&
             !isset(self::$statusCodes[$code]) )
        {
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' => "Unknown status code: $code"
                ]);
        }
        return $this->code == $code;
    }

    /**
     * Returns the exception, if any, that caused this error.
     *
     * @return Exception
     */
    public function inner() : ?Throwable
    {
        return $this->getPrevious();
    }

    /**
     * Returns a stack trace
     *
     * @return Exception
     */
    public function trace() : array
    {
        return $this->getTrace();
    }

    /**
     * Returns true if this exceptin has been logged.
     *
     * @return boolean
     */
    public function logged() : bool
    {
        return $this->logged;
    }

    /**
     * Marks this exception as logged.
     */
    public function setLogged() : void
    {
        $this->logged = true;
    }

    /**
     * Logs this exception to the specified log, if it has not already been
     * logged.
     *
     * @param mixed $log The log
     */
    public function log($log) : void
    {
        if (!$this->logged) {
            $this->logged = true;
            $log->logError($this);
        }
    }

    /**
     * If the given exception is an instance of CodeRage\Error, return it; otherwise,
     * returns a newly constructed instance of CodeRage\Error with status
     * 'INTERNAL_ERROR' and inner exception equal to the given exception.
     *
     * @param Throwable $error
     * @return CodeRage\Error
     */
    public static function wrap(Throwable $error) : self
    {
        return $error instanceof Error ?
            $error :
            new Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' => $error->getMessage(),
                    'inner' => $error
                ]);
    }

    /**
     * Translates a status code, represented as a string, into a human readable
     * message.
     *
     * @param string $code The status code
     * @return string The status message
     */
    public static function translateStatus(string $code) :string
    {
        self::loadStatusCodes();
        return isset(self::$statusCodes[$code]) ?
            self::$statusCodes[$code]['message'] :
            self::DEFAULT_MESSAGE;
    }

    /**
     * Registers a status code.
     *
     * @param string $code The status code
     * @param string $message The status message
     */
    public static function registerStatus(string $code, string $message) : void
    {
        if (isset(self::$statusCodes[$code]))
            throw new Exception("The status '$code' is already defined");
        self::$statusCodes[$code] =
            [
                'code' => $code,
                'message' => $message
            ];
    }

    /**
     * Hook for use with CodeRage\Util\NativeDataEncoder
     *
     * @param CodeRage\Util\NativeDataEncoder $encoder The native data encoder
     * @return stdClass
     */
    public function nativeDataEncode(Util\NativeDataEncoder $encoder)
    {
        return self::encode($this);
    }

    /**
     * Encodes the given exception as a native data structure or as JSOMN
     *
     * @param Throwable $e
     * @param boolean $json true to return JSON
     * @return mixed
     */
    public static function encode(Throwable $e, bool $json = false)
    {
        $result = new \stdClass;
        if ($e instanceof Error)
            $result->status = $e->status;
        $result->message = $e->getMessage();
        if ($e instanceof Error && $e->details !== $result->message)
            $result->details = $e->details;
        $result->file = $e->getFile();
        $result->line = $e->getLine();
        $trace = [];
        foreach ($e->getTrace() as $info) {
            $func =
                ( isset($info['class']) ? "{$info['class']}::" : "" ) .
                ( isset($info['function']) ?
                      "{$info['function']}()" :
                      'unknown function' );
            $line = $info['line'] ?? '<unknown>';
            $file = $info['file'] ?? '<unknown>';
            $trace[] = "$func called at line $line in $file";
        }
        if (!empty($trace))
            $result->trace = $trace;
        if (($prev = $e->getPrevious()))
            $result->inner = self::encode($prev);
        return $json ?
            json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) :
            $result;
    }

    /**
     * Returns a human-readable label for the type of the given value, for use
     * in error messages
     *
     * @param mixed $value The value
     * @return string
     */
    public static function formatType($value) : string
    {
        return is_object($value) ? get_class($value) : \gettype($value);
    }

    /**
     * Formats the given value as a human readable string, for use in error
     * messages
     *
     * @param mixed $value The value
     * @return string
     */
    public static function formatValue($value) : string
    {
        if (is_object($value))
            return get_class($value);
        if (is_array($value))
            return 'array';
        if (is_string($value))
            return '"' . str_replace('"', '\\"', $value) . '"';
        if ($value === null)
            return 'null';
        if (is_bool($value))
            return ($value ? 'true' : 'false');
        return (string) $value;
    }

    /**
     * Implements a method 'throw' that throws this error
     */
    public function __call($method, $args)
    {
        $args; // Suppress 'unused function parameter' warning
        if ($method == 'throw') {
            throw $this;
        } else {
            throw new Exception("No such method: CodeRage\\Error::$method()");
        }
    }

    /**
     * Returns a string representation of this instance, suitable for display to a
     * developer.
     */
    public function __toString() : string
    {
        return self::encode($this, true);
    }

    /**
     * Loads status codes from a file generated by the build system, if
     * available.
     */
    private static function loadStatusCodes() : void
    {
        if (self::$statusCodes === null) {
            self::$statusCodes = [];
            $config = Config::current();
            $definitions =
                $config->getRequiredProperty('project_root') .
                '/.coderage/error.php';
            if (file_exists($definitions)) {
                File::checkReadable($definitions);
                include($definitions);
            }
        }
    }

    /**
     * A detailed error message, suitable for display to a developer.
     *
     * @var string
     */
    private $details;

    /**
     * A status code, as a string.
     *
     * @var string
     */
    private $status;

    /**
     * true if this exceptin has already been logged.
     *
     * @var boolean
     */
    private $logged = false;

    /**
     * Associative array mapping status codes, represented as strings, to
     * associative arrays with keys 'status' and 'message'.
     *
     * @var array
     */
    private static $statusCodes;
}
