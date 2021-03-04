<?php

/**
 * Defines the interface CodeRage\Sys\Handle
 *
 * File:        CodeRage/Sys/SystemHandle.php
 * Date:        Tue Feb  7 06:03:23 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use Psr\Container\ContainerInterface;
use CodeRage\Access\Session;

/**
 * Represents an interface to system services
 */
interface Handle {

    /**
     * Returns a dependency injection container
     *
     * @return Psr\Container\ContainerInterface
     */
    public function container(): ContainerInterface;

    /**
     * Returns an instance of CodeRage\Sys\ProjectConfig
     *
     * @return CodeRage\Sys\ProjectConfig
     */
    public function config(): \CodeRage\Sys\ProjectConfig;

    /**
     * Returns a database connection
     *
     * @return CodeRage\Db
     */
    public function db(): \CodeRage\Db;

    /**
     * Returns a log
     *
     * @return CodeRage\Log
     */
    public function log(): \CodeRage\Log;

    /**
     * Returns the associated session, if any
     *
     * @return CodeRage\Access\Session
     */
    public function session(): ?Session;

    /**
     * Sets or clears the associated session
     *
     * @param CodeRage\Access\Session $session The session
     */
    public function setSession(Session $session): void;

    /**
     *
     * Returns true if a service with the given name has been registered
     *
     * @param string $name The service name
     * @return bool
     */
    public function hasService(string $service): bool;

    /**
     * Returns the service with the given name
     *
     * @param array $name The service name
     * @return mixed
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    public function getService(string $service);

    /**
     * Logs a message
     *
     * @param string $msg The log message
     */
    public function logMessage($msg): void;

    /**
     * Logs a warning
     *
     * @param string $msg The log message
     */
    public function logWarning($msg): void;

    /**
     * Logs an error
     *
     * @param string $msg The log message or an instance of Throwable
     */
    public function logError($msg): void;

    /**
     * Logs a critical error
     *
     * @param string $msg The log message
     */
    public function logCritical($msg): void;
}
