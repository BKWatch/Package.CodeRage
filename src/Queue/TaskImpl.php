<?php

/**
 * Defines the class CodeRage\Queue\TaskImpl
 * 
 * File:        CodeRage/Queue/TaskImpl.php
 * Date:        Wed Dec 25 18:27:37 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Queue;

use CodeRage\Error;


/**
 * Helper class to give CodeRage\Queue\Manager access to private properties of
 * CodeRage\Queue\Task
 */
final class TaskImpl {

    /**
     * Encodes this task implementation as an instance of stdClass for use in
     * logging
     *
     * @return stdClass
     */
    public function encode()
    {
        $result = (object)
            [
                'id' => $this->id,
                'taskid' => $this->taskid
            ];
        foreach (['data1', 'data2', 'data3'] as $n)
            if (($v = $this->$n) !== null)
                $result->$n = $v;
        if ($this->parameters !== '')
            $result->parameters = $this->parameters;
        $result->created = $this->formatTimestamp($this->created);
        $result->expires = $this->formatTimestamp($this->expires);
        if ($this->maxAttempts !== null)
            $result->maxAttempts = $this->maxAttempts;
        $result->attempts = $this->attempts;
        if ($this->sessionid !== null)
            $result->sessionid = $this->sessionid;
        $result->status = $this->status;
        if ($this->completed !== null)
            $result->completed = $this->formatTimestamp($this->completed);
        foreach (['errorStatus', 'errorMessage'] as $n)
            if (($v = $this->$n) !== null)
                $result->$n = $v;
        return $result;
    }

    /**
     * Formats the given UNOX timestamp as a string with time zone UTC
     *
     * @param int $time
     * @return string
     */
    public function formatTimestamp($time)
    {
        static $zone;
        if ($zone === null)
            $zone = new \DateTimeZone('UTC');
        return (new \DateTime(null, $zone))
            ->setTimestamp($time)
            ->format(DATE_W3C);
    }

    /**
     * The database ID
     *
     * @var int
     */
    public $id;

    /**
     * The opaque task identifier
     *
     * @var string
     */
    public $taskid;

    /**
     * The first data item associated with the task, if any
     *
     * @var string
     */
    public $data1;

    /**
     * The second data item associated with the task, if any
     *
     * @var string
     */
    public $data2;

    /**
     * The third data item associated with the task, if any
     *
     * @var string
     */
    public $data3;

    /**
     * The runtime parameters
     *
     * @var string
     */
    public $parameters;

    /**
     * The creation date, as a UNIX timestamp
     *
     * @var int
     */
    public $created;

    /**
     * The expiration date, as a UNIX timestamp
     *
     * @var int
     */
    public $expires;

    /**
     * The number of times this task may fail before being assigned the status
     * STATUS_FAILURE
     *
     * @var int
     */
    public $maxAttempts;

    /**
     * The number of times this task has been attempted
     *
     * @var int
     */
    public $attempts;

    /**
     * The alphanumeric session ID, if any
     *
     * @var string
     */
    public $sessionid;

    /**
     * The task status, as the value of one of the STATUS_XXX constants
     *
     * @var int
     */
    public $status;

    /**
     * The time the task status was set to STATUS_SUCCESS or STATUS_FAILURE, as
     * a UNIX timestamp
     *
     * @var int
     */
    public $completed;

    /**
     * The status of the most recent exception thrown during task processing, if
     * any
     *
     * @var string
     */
    public $errorStatus;

    /**
     * The details of the most recent exception thrown during task processing,
     * if any
     *
     * @var string
     */
    public $errorMessage;
}
