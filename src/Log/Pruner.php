<?php

/**
 * Defines the class CodeRage\Log\Pruner
 *
 * File:        CodeRage/Log/Pruner.php
 * Date:        Thu Apr 25 21:09:18 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log;

use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Time;


/**
 * Deletes old log entries. May result in the retention of a log session even
 * though some or all of its tags and entries have been deleted.
 */
class Pruner extends \CodeRage\Tool\Tool {

    /**
     * @var int
     */
    const DEFAULT_RETENTION_PERIOD = 7 * 24 * 3600;

    /**
     * @var int
     */
    const DEFAULT_MAX_SESSION_LIFETIME = 8 * 3600;

    /**
     * Constructs a CodeRage\Log\Pruner
     *
     * @param array $options The options array; supports the options supported
     *   by CodeRage\Tool\Tool
     */
    public function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * Implements execute()
     *
     * @param array $options The options array; supports the following options:
     *     retentionPeriod - The amount of time log sessions, tags, abd entries
     *       should be stored, in seconds
     *     maxSessionLifetime - The length of time that a log session should be
     *       assumed to be active
     * @return The generated URL
     */
    public function doExecute(array $options)
    {
        $retentionPeriod =
            Args::checkIntKey($options, 'retentionPeriod', [
                'default' => self::DEFAULT_RETENTION_PERIOD
            ]);
        $maxSessionLifetime =
            Args::checkIntKey($options, 'maxSessionLifetime', [
                'default' => self::DEFAULT_MAX_SESSION_LIFETIME
            ]);
        if ($retentionPeriod <= 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid retentionPeriod: expected positive integer; " .
                        "found $retentionPeriod"
                ]);
        if ($maxSessionLifetime <= 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid maxSessionLifetime: expected positive " .
                        "integer; found $maxSessionLifetime"
                ]);
        $now = Time::real();
        $db = $this->db();

        $this->logMessage('Deleting old log entries');
        $sql = 'DELETE FROM LogEntry where created < %i';
        $db->query($sql, $now - $retentionPeriod);

        $this->logMessage('Deleting old log tags');
        $sql = 'DELETE FROM LogTag where CreationDate < %i';
        $db->query($sql, $now - $retentionPeriod);

        $this->logMessage('Deleting old log sessions');
        $sql = 'DELETE FROM LogSession where CreationDate < %i';
        $db->query($sql, $now - $retentionPeriod - $maxSessionLifetime);
    }
}
