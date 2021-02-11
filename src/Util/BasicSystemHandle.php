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

/**
 * Basic implementation of CodeRage\Util\SystemHandle
 */
class BasicSystemHandle implements SystemHandle {
    use LogHelper;

    /**
     * Constructs an instance of CodeRage\Util\BasicSystemHandle
     *
     * @param array $options The options array; supports the following options:
     *     container - An instance of Psr\Container\ContainerInterface (optional)
     *     handle - An instance of CodeRage\Util\SystemHandle (optional)
     *   The option "handle" is incompatible with the other options
     */
    public function __construct($options)
    {
        $container =
            Args::checkKey($options, 'container', 'Psr\\Container\\ContainerInterface', [
               'default' => null
            ]);
        $handle =
            Args::checkKey($options, 'handle', 'CodeRage\\Util\\SystemHandle', [
               'label' => 'system handle',
               'default' => null
            ]);
        if ($container !== null && $handle !== null) {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        "The options 'container' and 'handle' are incompatible"
                ]);
        }
        $this->container =
            $container ??
            ( $handle !== null ?
                  $handle->container() :
                  new SystemContainer );
        $this->session = null;
        $this->logTagged = false;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }

    public function config(): \CodeRage\Build\ProjectConfig
    {
        return $this->container->get('config');
    }

    public function db(): Db
    {
        return $this->container->get('db');
    }

    public function log(): Log
    {
        $log = $this->container->get('log');
        if (!$this->logTagged) {
            $log->setTag(str_replace('\\', '.', get_class($this)));
            $this->logTagged = true;
        }
        return $log;
    }

    public function session(): ?Session
    {
        if ($this->session === null) {
            $this->session = Session::current();
        }
        return $this->session;
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
     * @var CodeRage\Util\Container
     */
    private $container;

    /**
     * @var Pacer\Session
     */
    private $session;

    /**
     * @var boolean
     */
    private $logTagged;
}
