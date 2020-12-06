<?php

/**
 * Defines the class CodeRage\Tool\Tool
 *
 * File:        CodeRage/Tool/Tool.php
 * Date:        Sun Jun 16 14:30:16 EDT 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Args;


/**
 * Represents a software component that can be executed with an associative
 * array of options
 */
abstract class Tool extends \CodeRage\Util\BasicSystemHandle {

    /**
     * Indicates that doExecute() should call logToolInput()
     *
     * @var int
     */
    const LOG_INPUT = 1;

    /**
     * Indicates that doExecute() should call logToolOutput()
     *
     * @var int
     */
    const LOG_OUTPUT = 2;

    /**
     * Indicates that the default implementation of handleToolError() should
     * log the caught exception
     *
     * @var int
     */
    const LOG_ERROR = 4;

    /**
     * The bitwise OR of LOG_INPUT and LOG_OUTPUT
     *
     * @var int
     */
    const LOG_IO = self::LOG_INPUT | self::LOG_OUTPUT;

    /**
     * The bitwise OR of LOG_INPUT, LOG_OUTPUT, and LOG_ERROR
     *
     * @var int
     */
    const LOG_ALL = self::LOG_INPUT | self::LOG_OUTPUT | self::LOG_ERROR;

    /**
     * Constructs an instance of CodeRage\Tool\Tool
     *
     * @param array $options The options array; supports the following options:
     *     config - An instance of CodeRage\Config (optional)
     *     db - An instance of CodeRage\Db (optional)
     *     log - An instance of CodeRage\Log (optional)
     *     handle - An instance of CodeRage\Util\SystemHandle (optional)
     *     logging - A bitwise OR of zero or more of the constants LOG_XXX;
     *       defaults to LOG_ALL
     *   The option "handle" is incompatible with the other options
     */
    public function __construct(array $options = [])
    {
        Args::checkKey($options, 'logging', 'int', [
            'default' => self::LOG_ALL
        ]);
        parent::__construct($options);
        $this->logging = $options['logging'];
    }

    /**
     * Returns a bitwise OR of zero or more of the constants LOG_XXX
     *
     * @return int
     */
    public final function logging()
    {
        return $this->logging;
    }

    /**
     * Specifies logging behavior
     *
     * @param int $logging A bitwise OR of zero or more of the constants
     *   LOG_XXX
     */
    public final function setLogging($logging)
    {
        Args::check($logging, 'int', 'logging');
        $this->logging = $logging;
    }

    /**
     * Performs the main work of this CodeRage\Tool\Tool
     *
     * @param array $options
     */
    public final function execute(array $options)
    {
        if (($this->logging & self::LOG_INPUT) != 0)
            $this->logToolInput($options);
        $result = null;
        try {
            $result = $this->doExecute($options);
        } catch (Throwable $e) {
            return $this->handleToolError($e);
        }
        if (($this->logging & self::LOG_OUTPUT) != 0)
            $this->logToolOutput($result);
        return $result;
    }

        /**
         * Abstract methods
         */

    /**
     * Implements the method execute();
     */
    protected abstract function doExecute(array $options);

        /**
         * Accessor methods
         */

    /**
     * Returns the tool name in dot-separated format
     *
     * @return string
     */
    public final function name()
    {
        if ($this->name === null) {
            $class = get_class($this);
            $class = str_replace('\\', '.', $class);
            $this->name = $class;
        }
        return $this->name;
    }

    /**
     * Logs tool input for execute(), if input logging is enabled
     *
     * @param array $options The options array
     */
    protected function logToolInput(array $options)
    {
        if ($stream = $this->log()->getStream(Log::DEBUG)) {
            $name = $this->name();
            $message = "Executing tool $name";
            if (count($options)) {
                $message .= ' with arguments: ';
                $parts = [];
                foreach ($options as $n => $v)
                    $parts[] = "$n => " . Error::formatValue($v);
                $message .= join(', ', $parts);
            }
            $stream->wrte($message);
        }
    }

    /**
     * Logs tool output for execute(), if output logging is enabled
     *
     * @param mixed $result The return value of execute()
     */
    protected function logToolOutput($result)
    {
        if ($stream = $this->log()->getStream(Log::DEBUG)) {
            $name = $this->name();
            $message =
                "Done executing tool $name; result = " .
                json_encode($result, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $stream->write($message);
        } elseif ($stream = $this->log()->getStream(Log::VERBOSE)) {
            $stream->write('Done executing tool ' . $this->name());
        }
    }

    /**
     * Handles exceptions thrown by doExecute(); by default, logs the error if
     * error logging is enabled and rethrows it
     *
     * @param Throwable $error The exception
     * @return mixed The value to be returned by execute(), if handleError()
     *   does not throw an Exception
     * @throws Exception
     */
    protected function handleToolError(Throwable $error)
    {
        if (($this->logging & self::LOG_ERROR) != 0)
            $this->logError($error);
        throw $error;
    }

    /**
     * The tool name
     *
     * @var string
     */
    private $name;

    /**
     * A bitwise OR of zero or more of the constants LOG_XXX
     *
     * @var int
     */
    private $logging;
}
