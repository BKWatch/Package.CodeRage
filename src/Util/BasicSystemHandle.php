<?php

/**
 * Defines the class CodeRage\Util\BasicSystemHandle
 *
 * File:        CodeRage/Util/BasicSystemHandle.php
 * Date:        Tue Feb  7 06:03:23 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Args;
use CodeRage\Util\Factory;


/**
 * Basic implementation of CodeRage\Util\SystemHandle
 */
class BasicSystemHandle implements SystemHandle {
    use LogHelper;

    /**
     * Constructs an instance of CodeRage\Util\BasicSystemHandle
     *
     * @param array $options The options array; supports the following options:
     *     config - An instance of CodeRage\Build\ProjectConfig (optional)
     *     db - An instance of CodeRage\Db (optional)
     *     log - An instance of CodeRage\Log (optional)
     *     session - An instance of CodeRage\Access\Session (optional)
     *     handle - An instance of CodeRage\Util\SystemHandle (optional)
     *   The option "handle" is incompatible with the other options
     */
    public function __construct($options)
    {
        Args::checkKey($options, 'config', 'CodeRage\\Build\\ProjectConfig', [
           'label' => 'configuration',
           'default' => null
        ]);
        Args::checkKey($options, 'db', 'CodeRage\\Db', [
           'label' => 'database connection',
           'default' => null
        ]);
        Args::checkKey($options, 'log', 'CodeRage\\Log', [
           'default' => null
        ]);
        Args::checkKey($options, 'handle', 'CodeRage\\Util\\SystemHandle', [
           'label' => 'system handle',
           'default' => null
        ]);
        Args::checkKey($options, 'session', 'CodeRage\\Access\\Session', [
           'label' => 'session',
           'default' => null
        ]);
        if ( isset($options['handle']) &&
             ( isset($options['config']) ||
               isset($options['db']) ||
               isset($options['log']) ) )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        "The option 'handle' is incompatible with the " .
                        "options 'config', 'db', and 'log'"
                ]);
        }
        $this->config = $options['config'];
        $this->db = $options['db'];
        $this->log = $options['log'];
        $this->handle = $options['handle'];
        $this->session = $options['session'];
        $this->logTagged = false;
    }

    public function config()
    {
        if ($this->handle !== null) {
            return $this->handle->config();
        } else {
            if ($this->config === null)
                $this->config = \CodeRage\Config::current();
            return $this->config;
        }
    }

    public function db()
    {
        if ($this->handle !== null) {
            return $this->handle->db();
        } else {
            if ($this->db === null)
                $this->db = new \CodeRage\Db;
            return $this->db;
        }
    }

    public function log($level = null)  // $level provides backward compatibiliy
    {                                   // for CodeRage\Tool\Tool
        $log = null;
        if ($this->handle !== null) {
            $log = $this->handle->log();
        } else {
            if ($this->log === null)
                $this->log = \CodeRage\Log::current();
            $log = $this->log;
        }
        if (!$this->logTagged) {
            $tag = str_replace('\\', '.', get_class($this));
            $log->setTag($tag);
            $this->logTagged = true;
        }
        return $level !== null ?
            $log->getStream($level) :
            $log;
    }

    public function session()
    {
        if ($this->handle !== null) {
            return $this->handle->session();
        } else {
            if ($this->session === null)
                $this->session = \CodeRage\Access\Session::current();
            return $this->session;
        }
    }

    public function setSession($session)
    {
        Args::check($session, 'CodeRage\\Access\\Session', 'session');
        $this->session = $session;
    }

    public function loadComponent($options)
    {
        return $this->handle !== null ?
            $this->handle->loadComponent($options) :
            Factory::create($options);
    }

    /**
     * @var CodeRage\Build\ProjectConfig
     */
    private $config;

    /**
     * @var CodeRage\Db
     */
    private $db;

    /**
     * @var CodeRage\Log
     */
    private $log;

    /**
     * @var Pacer\Session
     */
    private $session;

    /**
     * @var CodeRage\Util\SystemHandle
     */
    private $handle;

    /**
     * @var boolean
     */
    private $logTagged;
}
