<?php

/**
 * Defines the interface CodeRage\Sys\DataSource\ConfigInterface
 *
 * File:        CodeRage/Sys/DataSource/ConfigInterface.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\DataSource;

/**
 * Data source configuration interface
 */
interface ConfigInterface
{
    /**
     * Returns the name of the data source, as an identifier
     *
     * @return boolean
     */
    public function getName(): string;

    /**
     * Returns the data source type, e.g., "sql", as an alphanumeric string
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Returns the DBMS name, e.g., "mysql", as an alphanumeric string
     *
     * @return string
     */
    public function getDbms(): string;

    /**
     * Returns the data source hostname, if any
     *
     * @return string
     */
    public function getHost(): ?string;

    /**
     * Returns the data source port, if any
     *
     * @return int
     */
    public function getPort(): ?int;

    /**
     * Returns the data source username, if any
     *
     * @return string
     */
    public function getUsername(): ?string;

    /**
     * Returns the data source password, if any
     *
     * @return string
     */
    public function getPassword(): ?string;

    /**
     * Returns the data source database name, if any
     *
     * @return string
     */
    public function getDatabase(): ?string;

    /**
     * Returns the data source URL, if any
     *
     * @return string
     */
    public function getUrl(): ?string;

    /**
     * Returns a collection of configuration variables
     *
     * @return CodeRage\Sys\Config
     */
    public function getOptions(): ?\CodeRage\Sys\Config;
}
