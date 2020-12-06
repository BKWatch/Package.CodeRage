<?php

/**
 * Defines the class CodeRage\Access\Pruner
 *
 * File:        CodeRage/Access/Pruner.php
 * Date:        Tue Aug  9 00:39:24 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use CodeRage\Db;

/**
 * Deletes expired authorization tokens and sessions
 */
final class Pruner extends \CodeRage\Tool\Tool {

    /**
     * Constructs a CodeRage\Access\Pruner
     *
     * @param array $options The options array
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Deletes records from the tables AccessAuthToken and AccessSession
     *
     * @param array $options The options array; currently no options are
     *   supported
     */
    protected function doExecute(array $options)
    {
        $db = new Db;
        $now = \CodeRage\Util\Time::get();
        $sql =
            'DELETE FROM AccessAuthToken
             WHERE expires < %i';
        $db->query($sql, $now);
        $sql =
            'DELETE FROM AccessSession
             WHERE expires < %i';
        $db->query($sql, $now);
    }
}
