<?php

/**
 * Defines the class CodeRage\Util\Test\CommandLineExecution
 * 
 * File:        CodeRage/Util/Test/CommandLineExecution.php
 * Date:        Sun Nov  1 02:43:17 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Test\Assert;
use CodeRage\Util\CommandLine;

/**
 * @ignore
 */

/**
 * Stores information about a command-line execution
 */
final class CommandLineExecution {

    /**
     * Returns a list of instances of stdClass with properties "name",
     * "options", and "arguments", representing the executed command and its
     * sequence of active subcommands
     *
     * @return array
     */
    public static function commands() { return self::$commands; }

    /**
     * Returns additional information about the command execution, if any
     *
     * @var string
     */
    public static function comment() { return self::$comment; }

    /**
     * Logs a command execution, clearing all information about previously
     * executed commands
     *
     * @param CodeRage\Util\CommandBase $cmd The command that was executed
     * @param string $comment Additional information about the command execution
     */
    public static function log(CommandLine $cmd, $comment = '')
    {
        self::$commands = [];
        self::$comment = $comment;
        do {
            array_unshift(
                self::$commands,
                (object) [
                    'name' => $cmd->name(),
                    'options' => (object) $cmd->values(),
                    'arguments' => $cmd->arguments()
                ]
            );
        } while ($cmd = $cmd->parentCommand());
    }

    /**
     * Throws an exception if the given array of command objects is not
     * equivalent to the underlying array of command objects or if the given
     * comment is not strictly equal to the underlying comment
     *
     * @param array $commands A list of instances of stdClass with properties
     *   "name", "options", and "arguments", representing the executed command
     *   and its sequence of active subcommands
     * @param string $comment Additional information about the command
     *   execution, if any
     * @throw CodeRage\Error
     */
    public static function check(array $commands, $comment)
    {
        Assert::equal($commands, self::$commands);
        Assert::equal($comment, self::$comment);
    }

    /**
     * Clears the underlying list of commands and comment
     */
    public function clear()
    {
        self::$commands = null;
        self::$comment = null;
    }

    /**
     * A list of instances of stdClass with properties "name", "options", and
     * "arguments", representing the executed command and its sequence of
     * active subcommands
     *
     * @var array
     */
    private static $commands;

    /**
     * Additional information about the command execution, if any
     *
     * @var string
     */
    private static $comment;
}
