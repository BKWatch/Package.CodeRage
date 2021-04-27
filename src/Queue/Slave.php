<?php

/**
 * Defines the class CodeRage\Queue\Slave
 *
 * File:        CodeRage/Queue/Slave.php
 * Date:        Fri Jan  3 16:30:13 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Queue;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\ErrorHandler;

/**
 * Implements the batch processor mode "slave"
 */
class Slave {

    /**
     * @var string
     */
    const RUN_TOOL_PATH = 'Scripts/run-tool';

    /**
     * @var int
     */
    const OPEN_ATTEMPTS = 5;

    /**
     * @var float
     */
    const OPEN_SLEEP = 0.5;

    /**
     * @var float
     */
    const OPEN_MULTIPLIER = 2.0;

    /**
     * Constructs a Util\TaskProcessor\Slave
     *
     * @param Util\TaskManager $manager An instance of Util\TaskManager that
     *   owns the tasks to be performed by the slave
     * @param array $options The task processor options, encoded as strings
     */
    public function __construct(\Util\TaskManager $manager, array $options)
    {
        $this->manager = $manager;
        $this->options = $options;
        $this->start();
    }

    /**
     * Returns the task-processing session ID of this slave process
     *
     * @return string
     */
    public function sessionid()
    {
        return $this->manager->sessionid();
    }

    /**
     * Starts this slave
     */
    public function start()
    {
        // Construct command
        $config = \CodeRage\Config::current();
        $tool =  \CodeRage\Config::projectRoot() . '/' . self::RUN_TOOL_PATH;
        $command =
            escapeshellarg($tool) . ' --language perl ' .
            ' -c ' . escapeshellarg($this->class_()) .
            ' --param taskSessionid=' . $manager->sessionid();
        foreach ($this->options as $n => $v)
            if ($v !== null)
                $command .= ' --param ' . escapeshellarg("$n=$v");
        if ($session = \CodeRage\Access\Session::current())
            $command .= ' --session sessionid=' . $this->session->sessionid();
        if ($this->options['taskDebug'])
            $command .= ' -d';
        $this->outputFile = tempnam($tmp, 'output');
        $this->errorFile = tempnam($tmp, 'error');
        $spec =
            [
                0 => ['pipe', 'r'],
                1 => ['file', $this->outputFile, 'a'],
                2 => ['file', $this->errorFile, 'a']
            ];
        $pipes = null;
        $backoff =
            new \CodeRage\Util\ExponentialBackoff(
                    self::OPEN_ATTEMPTS,
                    self::OPEN_SLEEP,
                    self::OPEN_MULTIPLIER
                );
        $this->proc =
            $backoff->execute(
                function() use($command, $spec, &$pipes)
                {
                    $proc = proc_open($command, $spec, $pipes);
                    if ($proc === false)
                        throw new
                            Error([
                                'status' => 'INTERNAL_ERROR',
                                'details' =>
                                    "Failed starting slave (command '$command')"
                            ]);
                    return $proc;
                },
                function ($e) { return true; },
                'starting slave',
                $this->manager->log()
            );
    }

    /**
     * Returns true if this slave process has timed out
     */
    public function timedOut()
    {
        $db = $this->manager->tool()->db();
        $expires =
            $db->fetchValue(
                'SELECT expires
                 FROM AccessSession
                 WHERE RecordID = $i',
                $this->manager->session()->id()
            );
        return $expires < \CodeRage\Util\Time::real();
    }

    /**
     * Returns true if this slave process has termianted
     *
     * @return boolean
     */
    public function terminated()
    {
        $handler = new ErrorHandler;
        $info = $handler->_proc_get_status($this->proc);
        if ($info === false || $handler->errno()) {
            $this->cleanup();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' =>
                        $handler->formatError(
                            'Failed querying slave ' .
                            $this->manager->sessionid()
                        )
                ]);
        }
        $this->info = $info;
        return !$info['running'];
    }

    /**
     * Returns the output of the slave process, as an associative array
     * reference, and throws and exception if the process failed or output a
     * malformed result
     */
    public function result()
    {
        $sessionid = $this->manager->sessionid();
        $status = $this->info['exitcode'];
        if ($status != 0) {
            $error = is_file($this->errorFile) && is_readable($this->errorFile) ?
                file_get_contents($this->errorFile) :
                '';
            $this->cleanup();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' =>
                        "Slave $sessionid failed with exit status $status (" .
                        "$error)"
                ]);
        }
        try {
            File::checkReadable($this->errorFile);
        } catch (Throwable $e) {
            $this->cleanup();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' => "Failed reading output of slave $sessionid",
                    'inner' => $e
                ]);
        }
        $output = file_get_contents($this->outputFile);
        $result = json_decode($output);
        if ($result === null) {
            $this->cleanup();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' =>
                        "JSON decoding error: expected tool output; found " .
                        "'$output'"
                ]);
        }
        if (!$result instanceof stdClass) {
            $this->cleanup();
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' =>
                        "Malformed tool output: expected object; found " .
                        printScalar($result)
                ]);
        }
        $this->cleanup();
        if ($result['status'] == 'SUCCESS' && isset($result['result'])) {
            return $result['result'];
        } else {
            throw new Error($result);
        }
    }

    /**
     * Returns the class name of the underlying task processor in dot-separated
     * form
     *
     * @return string
     */
    private function class_()
    {
        return str_repalce('\\', '.', get_class($this->manager->tool()));
    }

    /**
     * Closes process handles and deletes temporary files
     */
    private function cleanup()
    {
        $handler = new ErrorHandler;
        if (is_resource($this->proc)) {
            $result = $handler->_proc_close($this->proc);
            if ($result == -1 || $handler->errno())
                $this->manager->tool()->logError(
                    $handler->formatError(
                        'Failed cleaning up slave ' .
                        $this->manager->sessionid()
                    )
                );
            $this->proc = null;
        }
        if (is_string($this->outputFile) && file_exists($this->outputFile)) {
            $handler->_unlink($this->outputFile);
            $this->outputFile = null;
        }
        if (is_string($this->errorFile) && file_exists($this->errorFile)) {
            $handler->_unlink($this->errorFile);
            $this->errorFile = null;
        }
        $this->info = null;
    }

    /**
     * @var CodeRage\Queue\Manager
     */
    private $manager;

    /**
     * @var array
     */
    private $options;

    /**
     * @var resource
     */
    private $proc;

    /**
     * @var string
     */
    private $outFile;

    /**
     * @var string
     */
    private $errFile;

    /**
     * @var array
     */
    private $info;
}
