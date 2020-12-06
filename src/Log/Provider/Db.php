<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\Db
 *
 * File:        CodeRage/Log/Provider/Db.php
 * Date:        Thu Jan 31 20:33:13 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

use CodeRage\Log;
use CodeRage\Log\Entry;

/**
 * @ignore
 */

/**
 * Implementation of CodeRage\Log\Provider that writes entries to a database
 */
final class Db implements \CodeRage\Log\Provider {

    function name() { return 'db'; }

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    public function dispatchEntry(Entry $entry)
    {
        if (!$this->connectionsLogged)
            $this->logConnections();
        $db = \CodeRage\Db::nonNestableInstance();
        $sessionId = $entry->sessionId();
        if (!isset($this->sessions[$sessionId])) {
            $sql =
                'SELECT RecordID, level
                 FROM LogSession
                 WHERE id = %s';
            $row = $db->fetchFirstRow($sql, $sessionId);
            $id = $level = null;
            $tags = [];
            if ($row !== null) {
                list($id, $level) = $row;
                $sql =
                    'SELECT tag
                     FROM LogTag
                     WHERE sessionid = %i';
                $result = $db->query($sql, $id);
                while ($row = $result->fetchRow())
                    $tags[$row[0]] = 1;
            } else {
                $level = \CodeRage\Log::INFO;
                $id =
                    $db->insert(
                        'LogSession',
                        [
                            'id' => $sessionId,
                            'level' => $level
                        ]
                    );
            }
            $this->sessions[$sessionId] =
                [
                    'id' => $id,
                    'level' => $level,
                    'tags' => $tags
                ];
        }
        $session = $this->sessions[$sessionId];
        $id =
            $db->insert(
                'LogEntry',
                [
                    'sessionid' => $session['id'],
                    'created' => $entry->timestamp()->format('U'),
                    'level' => $entry->level(),
                    'message' =>
                        Entry::formatMessage(
                            $entry->message(),
                            Entry::EXCEPTION_DETAILS
                        ),
                    'file' => $entry->file(),
                    'line' => $entry->line()
                ]
            );
        if ($entry->level() < $session['level']) {
            $session['level'] = $entry->level();
            $sql =
                'UPDATE LogSession
                 SET level = %i
                 WHERE RecordID = %i';
            $db->query($sql, $session['level'], $session['id']);
        }
        foreach ($entry->tags() as $t) {
            if (!isset($session['tags'][$t])) {
                $sql =
                    'SELECT count(*)
                     FROM LogTag
                     WHERE sessionid = %i AND
                           tag = %s';
                if ($db->fetchValue($sql, $session['id'], $t) == 0) {
                    $session['tags'][$t] = 1;
                    $db->insert(
                        'LogTag',
                        [
                            'sessionid' => $session['id'],
                            'tag' => $t
                        ]
                    );
                }
            }
        }
    }

    /**
     * Returns the URL for viewing the log session with the given session ID
     *
     * @param string $sessionId
     * @return string
     */
    public static function url($sessionId)
    {
        static $domain;
        if ($domain === null) {

            // Logging must work during the install process, when
            // CodeRage\Config may not be available
            $config = class_exists('CodeRage\\Config') ?
                \CodeRage\Config::current() :
                null;
            $domain = $config !== null ?
                $config->getProperty('site_domain', 'localhost.localdomain') :
                'localhost.localdomain';
        }
        return "https://$domain/CodeRage/Log/view.php?session=$sessionId";
    }

    /**
     * Log the connection IDs of the default connection and the non-nestable
     * connection
     */
    private function logConnections()
    {
        $this->connectionsLogged = true;
        $default = new \CodeRage\Db;
        $nonNestable = \CodeRage\Db::nonNestableInstance();
        $id1 = $default->fetchValue('SELECT CONNECTION_ID()');
        $id2 = $nonNestable->fetchValue('SELECT CONNECTION_ID()');
        $log = new Log;
        try {
            $log->registerProvider($this, Log::INFO);
            $log->logMessage("Connection IDs: $id1, $id2");
        } finally {
            $log->unregisterProvider($this);
        }
    }

    /**
     * Maps alphanumeric session IDs to associative arrays with keys 'id',
     * 'level', and 'tags'
     *
     * @var array
     */
    private $sessions = [];

    /**
     * true if the database connection IDs have been logged
     *
     * @var boolean
     */
    private $connectionsLogged = false;
}
