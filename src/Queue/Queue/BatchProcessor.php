<?php

/**
 * Defines the class CodeRage\Queue\BatchProcessor
 *
 * File:        CodeRage/Queue/BatchProcessor.php
 * Date:        Fri Jan  3 15:52:34 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Queue;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Args;


/**
 * Base class for queue processing tools that follow a uniform pattern
 */
abstract class BatchProcessor extends \CodeRage\Tool\Tool {

    /**
     * @var array
     */
    const OPTIONS =
        [ 'taskMode', 'taskMaxAttempts', 'taskLifetime', 'taskSlaves',
          'taskBatchSize', 'taskSleep', 'taskShuffle', 'taskSessionid',
          'taskDebug' ];

    /**
     * @var string
     */
    const MATCH_MODE = '/^(create|run|master|slave|status|clear|terminate)$/';

    /**
     * @var int
     */
    const DEFAULT_BATCH_SIZE = 1;

    /**
     * The number of seconds to sleep after starting a slave; reduces the chance
     * of database failures
     *
     * @var int
     */
    const CREATE_SLAVE_SLEEP = 1000;

    /**
     * The number of seconds to sleep after checking the status of slaves
     *
     * @var int
     */
    const CHECK_SLAVE_SLEEP = 1;

    /**
     * Constructs a Util\TaskProcessor
     *
     * @param array $options The options array; supports all options supported
     *   by CodeRage\Tool\Tool
     */
    public function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * @param array $options The options array; supports the following options:
     *     taskMode - One of "create", "run", "master", "slave", "status",
     *       "clear", "terminate"; defaults to "master" if "taskSlaves" is
     *       specified, and to "run", otherwise (optional)
     *     taskMaxAttempts - The maximum number of times to attempt each task
     *       (optional)
     *     taskLifetime - The task lifetime, in seconds (optional)
     *     taskSlaves - The number of concurrent processes to run; only
     *       compatible with mode "master" (optional)
     *     taskBatchSize - The number of tasks to assign to a slave, for task
     *       processors that do not customize task assignment; only compatible
     *       with mode "master" (optional)
     *     taskSleep - The number of milliseconds to sleep between tasks;
     *       requires the cooperation of the derived class, if the derived class
     *       implements doProcessBatch() without using processTask(); defaults
     *       to 0
     *     taskShuffle - true to process tasks in shuffled order; requires
     *       the cooperation of the derived class; defaults to false
     *     taskSessionid - The session ID of an existing queue processing
     *       session; only compatible with mode "slave" (optional)
     */
    public final function doExecute(array $options)
    {
        $this->queue = $this->doQueue();
        $this->processOptions($options);
        $this->setParameters($options);
        $mode = $options['taskMode'];
        $result = null;
        switch ($mode) {
        case 'create':
            $result = $this->create($options, 0);
            break;
        case 'run':
            $result = $this->run($options, 0);
            break;
        case 'master':
            $result = $this->master($options, 0);
            break;
        case 'slave':
            $result = $this->slave($options, 0);
            break;
        case 'status':
            $result = $this->status($options, 0);
            break;
        case 'clear':
            $result = $this->clear($options, 0);
            break;
        case 'terminate':
            $result = $this->terminate($options, 0);
            break;
        default:
            // Can't occur
            break;
        }
        return $result;
    }

    /**
     * Executes in "create" mode
     *
     * @param array $options The options array passed to doExecute
     * @return int The number of tasks created
     */
    public final function create(array $options)
    {
        $manager =
            new Manager([
                    'queue' => $this->queue(),
                    'parameters' => $this->parameters(),
                    'maxAttempts' => $options['taskMaxAttempts'],
                    'lifetime' => $options['taskLifetime'],
                    'tool' => $this
                ]);
        return $this->createTasks($options, $manager);
    }

    /**
     * Executes in "run" mode
     *
     * @param array $options The options array passed to doExecute
     * @return Varies accoding to mode
     */
    public final function run(array $options)
    {
        $manager =
            new Manager([
                    'queue' => $this->queue(),
                    'parameters' => $this->parameters(),
                    'maxAttempts' => $options['taskMaxAttempts'],
                    'lifetime' => $options['taskLifetime'],
                    'tool' => $this
                ]);
        $this->createTasks($options, $manager);
        $retval = null;
        while (true) {
            $manager = $this->assignTasks($options);
            if ($manager === null)
                break;
            $queue = $this->queue();
            $sql =
                "SELECT RecordID as id, task, data1, data2, data3
                 FROM [$queue]
                 WHERE sessionid = %s AND
                       status = %i AND
                       parameters = %s
                 ORDER BY RecordID";
            $result =
                $this->db()->query(
                    $sql,
                    $manager->sessionid(),
                    Task::STATUS_PENDING,
                    $this->parameters()
                );
            $batch = [];
            while ($task = $result->fetchArray())
                $batch[] = $task;
            $rv = $this->processBatch($options, $manager, $batch);
            $retval = $this->aggregateResults($options, $retval, $rv);
        }
        return $retval;
    }

    /**
     * Executes in "master" mode
     *
     * @param array $options The options array passed to doExecute()
     * @return An associative array reference with keys "errors" and
     *   "billablePages"
     */
    public final function master(array $options)
    {
        // Create tasks
        $manager =
            new Manager([
                    'queue' => $this->queue(),
                    'parameters' => $this->parameters(),
                    'maxAttempts' => $options['taskMaxAttempts'],
                    'lifetime' => $options['taskLifetime'],
                    'tool' => $this
                ]);
        $this->createTasks($options, $manager);

        // Encode options as string
        $slaveOpts = $this->doEncodeOptions($options);
        foreach (self::OPTIONS as $opt)
            if (isset($options[$opt]) && $opt != 'taskSlaves')
                $slaveOpts[$opt] = $options[$opt];
        $slaveOpts['taskMode'] = 'slave';
        unset($slaveOpts['taskSlaves']);

        // Run slaves
        $slaves = [];
        $result = null;
        do {

            // Create new slaves
            $manager->clearSessions();
            while ($slaves < $options['taskSlaves']) {
                $manager = $this->assignTasks($options);
                if ($manager !== null) {
                   $slaves[] = new Slave($manager, $slaveOpts);
                } else {
                    break;
                }
                if ($slaves < $options['taskSlaves'])
                   self::sleep(self::CHECK_SLAVE_SLEEP);
            }

            // Check existing slaves
            for ($i = $slaves - 1; $i >= 0; --$i) {
                $slave = $slaves[$i];
                try {
                    if ($slave->terminated()) {
                        array_splice($slaves, $i, 1);
                        $res = $slave->result();
                        $result =
                            $this->aggregateResults($options, $result, $res);
                        if ($stream = $this->log()->getStream(Log::VERBOSE)) {
                            $this->logMessage(
                                'Slave ' . $slave->sessionid() . ' ' .
                                'completed with  output ' . $json->encode($res)
                            );
                        } elseif ($stream = $this->log()->getStream(Log::INFO)) {
                            $this->logMessage(
                                'Slave ' . $slave->sessionid() . ' completed'
                            );
                        }
                    } elseif ($slave->timedOut()) {
                        array_splice($slaves, $i, 1);
                        $this->logMessage(
                            'Slave ' . $slave->sessionid() . ' timed out'
                        );
                    }
                } catch (Throwable $e) {
                    $this->logError(
                        new Error([
                                'details' =>
                                    'Failed processing slave ' .
                                    $slave->sessionid(),
                                'inner' => $e
                            ])
                    );
                }
            }
            self::sleep(self::CHECK_SLAVE_SLEEP);
        } while ($slaves);

        // Output results
        return $result;
    }

    /**
     * Executes in "slave" mode
     *
     * @param array $options The options array passed to doExecute()
     * @return An associative array reference with keys "errors" and
     *   "billablePages"
     */
    public final function slave(array $options)
    {
        $manager =
            new Manager([
                    'queue' => $this->queue(),
                    'parameters' => $this->parameters(),
                    'maxAttempts' => $options['taskMaxAttempts'],
                    'lifetime' => $options['taskLifetime'],
                    'sessionid' => $options['taskSessionid'],
                    'tool' => $this
                ]);
        $queue = $this->queue();
        $sql =
            "SELECT RecordID as id, task, data1, data2, data3
             FROM [$queue]
             WHERE sessionid = %s AND
                   status = %i
             ORDER BY RecordID";
        $result =
            $this->db()->query(
                $sql,
                $manager->sessionid(),
                Task::STATUS_PENDING
            );
        $batch = [];
        while ($task = $result->fetchArray())
            $batch[] = $task;
        $retval = $this->processBatch($options, $manager, $batch);
        return $retval;
    }

    /**
     * Executes in "status" mode implemented by calling doStatus()
     *
     * @param array $options The options array passed to doExecute()
     * @return An associative array reference with keys "success", "failure",
     *   and "pending", indicating the number of jobs with each status
     */
    public final function status(array $options)
    {
        $queue = $this->queue();
        $result =
            $this->db()->query(
                "SELECT status, COUNT(*)
                 FROM [$queue]
                 WHERE parameters = %s
                 GROUP BY status",
                $this->parameters()
            );
        $totals =
            [
                'success' => 0,
                'failure' => 0,
                'pending' => 0
            ];
        $labels =
            [
                Task::STATUS_SUCCESS => 'success',
                Task::STATUS_FAILURE => 'failure',
                Task::STATUS_PENDING => 'pending'
            ];
        while ($row = $result->fetchRow())
            $totals[$labels[$row[0]]] = $row[1];
        $opts = $options;
        return $this->doStatus($opts, $totals);
    }

    /**
     * Executes in "clear" mode
     *
     * @param array $options The options array passed to doExecute()
     * @return The number of deleted sessions (subject to race conditions)
     */
    public final function clear(array $options)
    {
        $queue = $this->queue();
        $params = $this->parameters();
        $count =
            $this->db()->fetchValue(
                "SELECT COUNT(*)
                 FROM [$queue]
                 WHERE sessionid IS NOT NULL AND
                       parameters = %s AND
                       status = %i",
                $params, Task::STATUS_PENDING
            );
        $this->db()->query(
            "UPDATE [$queue]
             SET sessionid = NULL,
                 attempts = attempts + 1
             WHERE sessionid IS NOT NULL AND
                   parameters = %s AND
                   status = %i",
            $params, Task::STATUS_PENDING
        );
        return ['clearedSessions' => $count];
    }

    /**
     * Executes in "terminate" mode
     *
     * @param array $options The options array passed to doExecute()
     * @return An associative array reference with key "count" indicating the
     *   number of deleted jobs (subject to race conditions)
     */
    public final function terminate(array $options)
    {
        $queue = $this->queue();
        $params = $this->parameters();
        $count =
            $this->db()->fetchValue(
                "SELECT COUNT(*)
                 FROM [$queue]
                 WHERE parameters = %s",
                $params
            );
        $this->db()->delete($queue, ['parameters' => $params]);
        return ['deletedTasks' => $count];
    }

    /**
     * Returns the queue name
     */
    public final function queue()
    {
        return $this->queue;
    }

    /**
     * Returns the value stored in the "parameters" column of the queue
     */
    public final function parameters()
    {
        return $this->parameters;
    }

    /**
     * Creates tasks in the queue; implemented by calling doCreateTasks()
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param CodeRage\Queue\Manager $manager An instance of
     *   CodeRage\Queue\Manager
     */
    public final function createTasks(array $options, Manager $manager)
    {
        $opts = $options;
        return $this->doCreateTasks($opts, $manager);
    }

    /**
     * Returns an instance of CodeRage\Queue\Manager that owns a collection of
     * tasks to be processed by a slave
     *
     * @param array $options The options array passed to doExecute, after
     *   processing
     * @return CodeRage\Queue\Manager
     */
    public final function assignTasks(array $options)
    {
        $opts = $options;
        return $this->doAssignTasks($opts);
    }

    /**
     * Processes the given tasks; implemented by calling doProcessTask()
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param CodeRage\Queue\Manager $manager An instance of
     *   CodeRage\Queue\Manager
     * @param array $task An associative array with keys "id",
     *   "task", "data1", "data2", and "data3"
     * @return The result of processing $task
     */
    public final function processTask(array $options, Manager $manager, $task)
    {
        $opts = $this->pruneOptions($options);
        $result = $error = null;
        $status = Task::STATUS_SUCCESS;
        try {
            $result = $this->doProcessTask($opts, $manager, $task);
            if ($options['taskSleep'] > 0)
                $this->sleep($options['taskSleep']);
        } catch (Throwable $e) {
            $error = Error::wrap($e);
            $status = Task::STATUS_PENDING;
            $this->logError($error);
        }
        $manager->updateTaskStatus($task['task'], $status, ['error' => $error]);
        return $result;
    }

    /**
     * Processes the given list of tasks; implemented by calling
     * doProcessBatch()
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param CodeRage\Queue\Manager $manager An instance of
     *   CodeRage\Queue\Manager
     * @param array $batch A array of associative array with keys
     *   "id", "task", "data1", "data2", and "data3"
     * @return The result of processing $batch
     */
    public final function processBatch(array $options, Manager $manager, $batch)
    {
        $opts = $this->pruneOptions($options);
        return $this->doProcessBatch($opts, $manager, $batch);
    }

    /**
     * Combines the two given queue processing results into a single result;
     * returns an null by default
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param mixed $a The result of processing a task or batch or task, or an
     *   exception thrown by doProcessTask; may be undefined if $b is the result
     *   of processing the first task in a batch
     * @param mixed $b The result of processing a task or batch or tasks, or
     *   an exception thrown by doProcessTask(
     * @return The result of combining $a and $b
     */
    public final function aggregateResults(array $options, $a, $b)
    {
        $opts = $options;
        return $this->doAggregateResults($opts, $a, $b);
    }

    /**
     * Processes and validates options to doExecute, excluding the options
     * common to all task processors. A no-op by default.
     *
     * @param array $options A reference to a copy of the options array passed
     *   to doExecute, excluding common options
     */
    protected function doProcessOptions(array &$options)
    {
        // No-op
    }

    /**
     * Encodes the given options as strings so that they can be passed to child
     * processes on the command-line
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @return A string-valued associative array
     */
    protected function doEncodeOptions(array $options)
    {
        // No-op
    }

    /**
     * Returns the default value of the "taskMaxAttempts" option, if any;
     * returns an null by default
     *
     * @return int
     */
    protected function doDefaultMaxAttempts()
    {
        return null;
    }

    /**
     * Returns the default value of the "taskLifetime" option, if any; returns
     * null by default
     *
     * @return int
     */
    protected function doDefaultLifetime()
    {
        return null;
    }

    /**
     * Returns the default value of the "taskBatchSize" option, if any; returns
     * an null by default
     *
     * @return int
     */
    protected function doDefaultBatchSize()
    {
        return null;
    }

    /**
     * Returns the queue name
     */
    protected abstract function doQueue(array $options);

    /**
     * Returns the value to be stored in the "parameters" column of the
     * queue; by default, returns an empty string if the given options array is
     * empty, and otherwise calls doEncodeOptions() and JSON-encodes the result
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @return The parameters, as a string
     */
    protected function doParameters(array $options)
    {
        if (!$options)
            return '';
        $options = $this->doEncodeOptions($options);
        $result = json_encode($options);
        if ($result === null)
            throw new
                Error([
                    'status' => INVALID_PARAMETER,
                    'details' => 'JSON encoding error'
                ]);
    }

    /**
     * Creates tasks in the queue
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param CodeRage\Queue\Manager $manager An instance of CodeRage\Queue\Manager
     * @return int The number of tasks created
     */
    protected abstract function doCreateTasks(array $options, Manager $manager);

    /**
     * Returns an instance of CodeRage\Queue\Manager that owns a collection of
     * tasks to be processed by a slave; returns an null if no tasks are
     * available
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @return CodeRage\Queue\Manager
     */
    protected function doAssignTasks(array $options)
    {
        $manager =
            new Manager([
                    'queue' => $this->queue(),
                    'parameters' => $this->parameters(),
                    'maxAttempts' => $options['taskMaxAttempts'],
                    'lifetime' => $options['taskLifetime'],
                    'tool' => $this
                ]);
        $tasks = $manager->claimTasks(['maxJobs' => $options['taskBatchSize']]);
        return $tasks > 0 ? $manager : null;
    }

    /**
     * Processes the given tasks; a no-op by default. This method is not
     * responsible for updating the status of $task.
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param CodeRage\Queue\Manager $manager An instance of
     *   CodeRage\Queue\Manager
     * @param array $task An associative array with keys "id", "task", "data1",
     *   "data2", and "data3"
     * @return The result of processing $task
     * @throws CodeRage\Error if processing fails
     */
    protected function doProcessTask(array $options, Manager $manager, $task)
    {
        // No-op
    }

    /**
     * Processes the given list of tasks; by default, processes each task by
     * calling doProcessTask and aggregates the results using aggregateResults.
     * This method is responsible for updating the status of the tasks in
     * @batch.
     *
     * @param array $options The options array passed to doExecute()`, after
     *   processing
     * @param CodeRage\Queue\Manager $manager An instance of
     *   CodeRage\Queue\Manager
     * @param array $batch A array of associative array with keys
     *   "id", "task", "data1", "data2", and "data3"
     * @return The result of processing $batch
     */
    protected function doProcessBatch(array $options, Manager $manager, $batch)
    {
        $result = null;
        foreach ($batch as $task) {
            $res = $this->processTask($options, $manager, $task);
            $result = $this->aggregateResults($options, $result, $res);
        }
        return $result;
    }

    /**
     * Combines the two given queue processing results into a single result;
     * returns null value by default
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param mixed $a The result of processing a task or batch or task, or an
     *   exception thrown by doProcessTask; may be undefined if $b is the result
     *   of processing the first task in a batch
     * @param mixed $b The result of processing a task or batch or tasks, or
     *   an exception thrown by doProcessTask(
     * @return The result of combining $a and $b
     */
    protected function doAggregateResults(array $options, $a, $b)
    {
        return null;
    }

    /**
     * Returns aggregate information about the tasks in the queue with
     * parameters equal to this instance's parameters; by default returns
     * $totals
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     * @param array $totals An associative array reference with keys "success",
     *   "failure", and "pending", indicating the number of tasks with each
     *   status
     * @return An associative array reference
     */
    protected function doStatus(array $options, $totals)
    {
        return $totals;
    }

    /**
     * Validates and processes options for doExecute()
     *
     * @param array $options The options array passed to doExecute
     */
    private function processOptions(array &$options)
    {
        // Validate options
        Args::checkKey($options, 'taskMode', 'string');
        if ( isset($options['taskMode']) &&
             !preg_match(self::MATCH_MODE, $options['taskMode']) )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid taskMode: {$options['taskMode']}"
                ]);
        }
        Args::checkKey($options, 'taskMaxAttempts', 'int', [
            'default' => $this->doDefaultMaxAttempts()
        ]);
        if ( isset($options['taskMaxAttempts']) &&
             $options['taskMaxAttempts'] <= 0 )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid taskMaxAttempts: expected positive integer; ' .
                        'found ' . $options['taskMaxAttempts']
                ]);
        }
        Args::checkKey($options, 'taskLifetime', 'int', [
            'default' => $this->doDefaultLifetime()
        ]);
        if (isset($options['taskLifetime']) &&$options['taskLifetime'] <= 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid taskLifetime: expected positive integer; ' .
                        'found ' . $options['taskLifetime']
                ]);
        Args::checkKey($options, 'taskSlaves', 'int');
        if (isset($options['taskSlaves']) && $options['taskSlaves'] <= 1)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid taskSlaves: expected integer greater than ' .
                        '1; found ' . $options['taskSlaves']
                ]);
        $default = $this->doDefaultBatchSize();
        Args::checkKey($options, 'taskBatchSize', 'int', [
            'default' => $default !== null ? $default : self::DEFAULT_BATCH_SIZE
        ]);
        Args::checkKey($options, 'taskSleep', 'int', [
            'default' => 0
        ]);
        if (isset($options['taskSleep']) && $options['taskSleep'] < 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid taskSlaves: expected non-negative integer; ' .
                        'found ' . $options['taskSleep']
                ]);
        Args::checkKey($options, 'taskShuffle', 'boolean', [
            'default' => false
        ]);
        Args::checkKey($options, 'taskSessionid', 'string');
        Args::checkKey($options, 'taskDebug', 'boolean', [
            'default' => false
        ]);

        // Calculate mode
        if (!isset($options['taskMode']))
            $options['taskMode'] = isset($options['taskSlaves']) ?
                'master' :
                'run';

        // Check relationships between options
        if ( !isset($options['taskMaxAttempts']) &&
             !isset($options['taskLifetime']) )
        {
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => "Missing 'taskMaxAttempts' or 'taskLifetime'"
                ]);
        }
        if (isset($options['taskSlaves']) && $options['taskMode'] != 'master')
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        "The option 'taskSlaves' is incompatible with mode " .
                        "'{$options['taskMode']}'"
                ]);
        if (isset($options['taskSessionid']) != ($options['taskMode'] == 'slave'))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' => isset($options['taskSessionid']) ?
                        "The option 'taskSessionid' is incompatible with mode " .
                            "'{$options['taskMode']}'" :
                        "Missing session ID"
                ]);

        // Process processor-specific options
        $procOpts = $this->pruneOptions($options);
        foreach ($procOpts as $opt => $v)
            unset($options[$opt]);
        $this->doProcessOptions($procOpts);
        foreach ($procOpts as $n => $v)
            $options[$n] = $v;
    }

    /**
     * Sets the _parameters property
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     */
    private function setParameters(array $options)
    {
        foreach (self::OPTIONS as $opt)
            unset($options[$opt]);
        $this->parameters = $this->doParameters($options);
    }

    /**
     * Returns a copy of the given options array, with the queue processing
     * options removed
     *
     * @param array $options The options array passed to doExecute(), after
     *   processing
     */
    private function pruneOptions(array $options)
    {
        $result = $options;
        foreach (self::OPTIONS as $opt)
            unset($result[$opt]);
        return $result;
    }

    /**
     * Sleeps for the specified number of milliseconds
     *
     * @param int $ms
     */
    private static function sleep($ms)
    {
        usleep($ms * 1000);
    }

    /**
     * @var string
     */
    private $queue;
}
