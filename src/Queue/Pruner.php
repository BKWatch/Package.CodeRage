<?php

/**
 * Defines the class CodeRage\Queue\Pruner
 *
 * File:        CodeRage/Queue/Pruner.php
 * Date:        Fri Jan  3 16:30:13 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Queue;

use Throwable;
use CodeRage\Db\Operations;
use CodeRage\Error;
use CodeRage\Text\Regex;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\Time;


/**
 * Deletes old tasks
 */
class Pruner extends \CodeRage\Tool\Tool {

    /**
     * @var string
     */
    private const MATCH_MODE = '/^(list|execute)$/';

    /**
     * @var string
     */
    private const DEFAULT_MODE = 'execute';

    private const MATCH_QUEUE_SPECIFIER =
        '/^([a-zA-Z0-9?*]+):(0|[1-9][0-9]*)$/';

    /**
     * @var string
     */
    private const MATCH_STATUS =
        '#\s*(SUCCESS|PENDING|FAILURE)(\s*,\s*(SUCCESS|PENDING|FAILURE))*\s*$#';

    /**
     * @param array
     */
    private const STATUSES =
        [
            'SUCCESS' => Task::STATUS_SUCCESS,
            'PENDING' => Task::STATUS_PENDING,
            'FAILURE' => Task::STATUS_FAILURE,
            Task::STATUS_SUCCESS => 'SUCCESS',
            Task::STATUS_PENDING => 'PENDING',
            Task::STATUS_FAILURE => 'FAILURE',
        ];

    /**
     * Constructs a CodeRage\Queue\Pruner
     *
     * @param array $options The options array; supports the options supported
     *   by CodeRage\Tool\Tool
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Implements execute()
     *
     * @param array $options The options array; supports the following options:
     *     queues - A comma-separated list of items of the form pattern:age,
     *       where pattern is a wildcard expression to be matched against
     *       table names, and age is the maximum age of tasks allowed to remain
     *       in the queue
     *     mode - One of "execute" or "list"; in list mode, outputs an $this
     *       associative mapping queue names to maximum age values without
     *       deleting any tasks; defaults to "execute"
     *     status - A comma-separated list of values among "SUCCESS", "PENDING",
     *       and "FAILURE", specifying the statuses of tasks to be deleted
     * @return The generated URL
     */
    public function doExecute(array $options)
    {
        // Process options
        $this->processOptions($options);
        $queues = $options['queues'];
        $mode = $options['mode'];
        $status = $options['status'] ?? null;

        // Create map from table names to ages
        $tables = $byPattern = [];
        $matchAny = '/^(' . join('|', array_keys($queues)) . ')$/';
        foreach (Operations::listTables($this->db()->params()) as $t) {
            if (!preg_match($matchAny, $t))
                continue;
            foreach ($queues as $pattern => $age) {
                if (empty($byPattern[$pattern]))
                    $byPattern[$pattern] = [];
                if (preg_match("/^$pattern$/", $t)) {
                    if (!isset($tables[$t])) {
                        $tables[$t] = $age;
                        $byPattern[$pattern][] = $t;
                    }
                }
            }
        }

        if ($mode == 'list')
           return $tables;

        // Delete tasks
        if (empty($tables)) {
            $this->logWarning('No matching queues');
        } else {
            foreach ($byPattern as $p => $t)
                if (empty($t))
                    $this->logWarning("No queues match pattern '$p");
        }
        foreach ($tables as $queue => $age) {
            $statusDesc = $status !== null ?
                " with status in " .
                    Array_::map(function($s) { return self::STATUSES[$s]; }, $status, ',') :
                "";
            $this->logMessage(
                "Deleting tasks from $queue older than $age days$statusDesc"
            );
            try {
                $where = $values = [];
                if ($age != 0) {
                    $where[] = 'CreationDate < %i';
                    $values[] = Time::get() - $age * 24 * 3600;
                }
                if ($status !== null)
                    $where[] = "status IN (" . join(',', $status) . ")";
                $sql =
                    "DELETE FROM [$queue]
                     WHERE " . join(' AND ', $where);
                $this->db()->query($sql, $values);
            } catch (Throwable $e) {
                $this->logError(new Error([
                    'message' => "Failed deleting tasks from $queue",
                    'inner' => $e
                ]));
            }
        }
    }

    /**
     * Validates and processes objects for doExecute()
     *
     * @param array $options The options array passed to doExecute()
     */
    private function processOptions(array &$options)
    {
        $queues =
            Args::checkKey($options, 'queues', 'string', [
                'required' => true
            ]);
        if (!mb_check_encoding($queues, 'ascii'))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The options 'queues' must be ASCII"
                ]);
        $queues = preg_split('/\s*,\s*/', trim($queues));
        $options['queues'] = [];
        foreach ($queues as $spec) {
            [$pattern, $age] =
                Regex::getMatch(self::MATCH_QUEUE_SPECIFIER, $spec, [1, 2]);
            if ($pattern === null)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Invalid maximum age specification: expected " .
                            "value of the form 'pattern:age': found '$spec'"
                    ]);
            if (strpos($pattern, '**') !== false)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The pattern '$pattern' contains consecutive " .
                            "wildcard symbols"
                    ]);
            $pattern = str_replace(['?', '*'], ['.', '.*'], $pattern);
            $options['queues'][$pattern] = (int) $age;
        }
        $mode =
            Args::checkKey($options, 'mode', 'string', [
                'default' => self::DEFAULT_MODE
            ]);
        if (!preg_match(self::MATCH_MODE, $mode))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Unsupported mode: $mode"
                ]);
        $status = Args::checkKey($options, 'status', 'string');
        if ($status !== null) {
            if (!preg_match(self::MATCH_STATUS, $status))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Invalid status list: $status"
                    ]);
            $status =
                array_map(
                    function($s) { return self::STATUSES[$s]; },
                    preg_split('/\s*,\s*/', trim($status))
                );
            $options['status'] = array_unique($status);
        }
    }
}
