<?php

/**
 * CodeRage logging framework
 *
 * File:        CodeRage/Log.php
 * Date:        Wed Jan 30 16:01:23 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use CodeRage\Build\ProjectConfig;
use CodeRage\Error;
use CodeRage\Log\Provider;
use CodeRage\Util\Factory;


/**
 * The CodeRage log
 */
final class Log {

    /**
     * @var int
     */
    const CRITICAL = 0;

    /**
     * @var int
     */
    const ERROR = 1;

    /**
     * @var int
     */
    const WARNING = 2;

    /**
     * @var int
     */
    const INFO = 3;

    /**
     * @var int
     */
    const VERBOSE = 4;

    /**
     * @var int
     */
    const DEBUG = 5;

    /**
     * @var int
     */
    const SESSION_ID_LENGTH = 50;

    /**
     * @var string
     */
    const MATCH_PROVIDER_CLASS = '/^log\.provider\.([^.]+)\.class$/';

    /**
     * @var string
     */
    const MATCH_PROVIDER_LEVEL = '/^log\.provider\.([^.]+)\.level$/';

    /**
     * @var string
     */
    const MATCH_PROVIDER_PARAM = '/^log\.provider\.([^.]+)\.param\.([^.]+)$/';

    /**
     * Constructs an instance of CodeRage\Log
     */
    public function __construct()
    {
        $this->impl = new Log\Impl;
    }

    /**
     * Returns the stream, if any, for writing log entries of the given level
     *
     * @param int $level One of the constants CodeRage\Log::XXX
     * @return CodeRage\Log\Stream
     */
    public function getStream($level)
    {
        return $this->impl->getStream($level);
    }

    /**
     * Convenience function for logging a critical error
     *
     * @param string $message The log message
     */
    public function logCritical($message)
    {
        if ($this->getStream(Log::CRITICAL))
            $this->impl->write(Log::CRITICAL, $message, null, 2);
    }

    /**
     * Convenience function for logging an error
     *
     * @param mixed $message The log message or instance of Throwable
     */
    public function logError($message)
    {
        if ($this->getStream(Log::ERROR))
            $this->impl->write(Log::ERROR, $message, null, 2);
    }

    /**
     * Convenience function for logging a warning; be careful when using this
     * function because it has overhead even when no registered provider is
     * configured to deliver warnings
     *
     * @param string $message
     */
    public function logWarning($message)
    {
        if ($stream = $this->getStream(Log::WARNING))
            $this->impl->write(Log::WARNING, $message, null, 2);
    }

    /**
     * Convenience function for logging an informational message; be careful
     * when using this function because it has overhead even when no registered
     * provider is configured to deliver informational messages
     *
     * @param string $message
     */
    public function logMessage($message)
    {
        if ($stream = $this->getStream(Log::INFO))
            $this->impl->write(Log::INFO, $message, null, 2);
    }

    /**
     * Registers a log provider
     *
     * @param CodeRage\Log\Provider $provider The provider
     * @param int $level One of the constants CodeRage\Log::XXX
     */
    public function registerProvider(Provider $provider, $level)
    {
        $this->impl->registerProvider($provider, $level);
    }

    /**
     * Unregisters a log provider
     *
     * @param CodeRage\Log\Provider $provider The provider
     */
    public function unregisterProvider(Provider $provider)
    {
        $this->impl->unregisterProvider($provider);
    }

    /**
     * Returns the session ID used to link log entries created by different
     * processes
     *
     * @return string
     */
    public function sessionId()
    {
        return $this->impl->sessionId();
    }

    /**
     * Sets the session ID used to link log entries created by different
     * processes
     *
     * @param string $sessionId The session ID
     */
    public function setSessionId($sessionId)
    {
        $this->impl->setSessionId($sessionId);
    }

    /**
     * Returns the list of tags
     *
     * @return array
     */
    public function tags()
    {
        return $this->impl->tags();
    }

    /**
     * Sets a text label to be associated with every entry written to the
     * log
     *
     * @param string $tag The tag; may contain any printable characters except
     *   square brackets
     */
    public function setTag($tag)
    {
        $this->impl->setTag($tag);
    }

    /**
     * Removes the given tag
     *
     * @param string $tag The tag
     */
    public function clearTag($tag)
    {
        $this->impl->clearTag($tag);
    }

    /**
     * Returns the current log, constructing one using the project configuration
     * if necessary
     *
     * @return CodeRage\Log
     */
    public static function current()
    {
        if (self::$current === null)
            self::$current = self::create(Config::current());
        return self::$current;
    }

    /**
     * Sets or clears the current instance of CodeRage\Log
     *
     * @param CodeRage\Log $current
     */
    public static function setCurrent($current)
    {
        self::$current = $current;
    }

    /**
     * Returns an instance of CodeRage\Log newly constructed from the given
     * configuration
     *
     * @param CodeRage\Config $config The configuration
     */
    public static function create(ProjectConfig $config)
    {
        $log = new Log;
        $classes = $levels = $params = [];
        foreach ($config->propertyNames() as $name) {
            if (strncmp($name, 'log.provider.', 13) != 0)
                continue;
            $value = $config->getProperty($name);
            $match = null;
            if (preg_match(self::MATCH_PROVIDER_CLASS, $name, $match)) {
                $classes[$match[1]] = $value;
            } if (preg_match(self::MATCH_PROVIDER_LEVEL, $name, $match)) {
                $levels[$match[1]] = $value;
            } elseif (preg_match(self::MATCH_PROVIDER_PARAM, $name, $match))
            {
                $params[$match[1]][$match[2]] = $value;
            }
        }
        foreach ($classes as $name => $class) {
            if (!isset($levels[$name]))
                throw new
                    Error([
                        'status' => 'CONFIGURATION_ERROR',
                        'defailts' =>
                            "No level specified for log provider '$name'"
                    ]);
            $level = is_numeric($levels[$name]) ?
                (int)$levels[$name] :
                self::translateLevel($levels[$name]);
            $provider =
                Factory::create([
                    'class' => $class,
                    'params' => isset($params[$name]) ?
                        $params[$name] :
                        []
                ]);
            $log->registerProvider($provider, $level);
        }
        return $log;
    }

    /**
     * Translates between numeric and textual log levels
     */
    public static function translateLevel($level)
    {
        if (is_int($level)) {
            switch ($level) {
            case self::CRITICAL: return "CRITICAL";
            case self::ERROR: return "ERROR";
            case self::WARNING: return "WARNING";
            case self::INFO: return "INFO";
            case self::VERBOSE: return "VERBOSE";
            case self::DEBUG: return "DEBUG";
            default:
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid log level: $level"
                    ]);
            }
        } else {
            $level = strtoupper($level);
            switch ($level) {
            case "CRITICAL": return self::CRITICAL;
            case "ERROR": return self::ERROR;
            case "WARNING": return self::WARNING;
            case "INFO": return self::INFO;
            case "VERBOSE": return self::VERBOSE;
            case "DEBUG": return self::DEBUG;
            default:
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid log level: " .
                            Error::formatValue($level)
                    ]);
            }
        }
    }

    /**
     * @var CodeRage\Log
     */
    private static $current;
}
