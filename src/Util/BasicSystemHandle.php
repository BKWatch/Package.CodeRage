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

use Psr\Container\ContainerInterface;
use CodeRage\Access\Session;
use CodeRage\Db;
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
     *     container - An instance of Psr\Container\ContainerInterface (optional)
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
        Args::checkKey($options, 'container', 'Psr\\Container\\ContainerInterface', [
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
               isset($options['container']) ||
               isset($options['db']) ||
               isset($options['log'])||
               isset($options['session']) ) )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        "The option 'handle' is incompatible with the " .
                        "options 'config', 'container', 'db', 'log'"
                ]);
        }
        $this->config = $options['config'];
        $this->config = $options['container'];
        $this->db = $options['db'];
        $this->log = $options['log'];
        $this->handle = $options['handle'];
        $this->session = $options['session'];
        $this->logTagged = false;
    }

    public function config(): \CodeRage\Build\ProjectConfig
    {
        if ($this->handle !== null) {
            return $this->handle->config();
        } else {
            if ($this->config === null)
                $this->config = \CodeRage\Config::current();
            return $this->config;
        }
    }

    public function container(): ContainerInterface
    {
        if ($this->handle !== null) {
            return $this->handle->container();
        } else {
            if ($this->container === null)
                $this->container = new Container;
            return $this->db;
        }
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function db(): Db
    {
        if ($this->handle !== null) {
            return $this->handle->db();
        } else {
            if ($this->db === null)
                $this->db = new Db;
            return $this->db;
        }
    }

    public function log($level = null): ?Log // $level is deprecated
    {
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

    public function session(): ?Session
    {
        if ($this->handle !== null) {
            return $this->handle->session();
        } else {
            if ($this->session === null)
                $this->session = Session::current();
            return $this->session;
        }
    }

    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    public function hasService(string $service): bool
    {
        return $this->container()->has($service);
    }

    /**
     * Returns the service with the given name
     *
     * @param array $name The service name
     * @return mixed
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    public function getService(string $service)
    {
        return $this->container()->get($service);
    }

    /**
     * @var CodeRage\Build\ProjectConfig
     */
    private $config;

    /**
     * @var CodeRage\Util\Container
     */
    private $container;

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
