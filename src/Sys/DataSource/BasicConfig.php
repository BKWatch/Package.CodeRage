<?php

/**
 * Defines the class CodeRage\Sys\DataSource\BasicConfig
 *
 * File:        CodeRage/Sys/DataSource/BasicConfig.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\DataSource;

use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\DataSource\ConfigInterface based on an
 * associative array of values specified at construction
 */
final class BasicConfig extends \CodeRage\Sys\Base implements ConfigInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'name' => ['type' => 'string', 'required' => true],
        'type' => ['type' => 'string', 'required' => true],
        'dbms' => ['type' => 'string', 'required' => true],
        'host' => ['type' => 'string'],
        'port' => ['type' => 'int'],
        'username' => ['type' => 'string'],
        'password' => ['type' => 'string'],
        'url' => ['type' => 'string'],
        'options' => ['type' => 'map[scalar]|CodeRage\Sys\Config']
    ];

    /**
     * Constructs an instance of CodeRage\Sys\DataSource\BasicConfig
     *
     * @param array $options The options array; supports the following options:
     *     name - The data source name
     *     type - The data source type
     *     dbms - The DBMS name
     *     host - The hostname, if any
     *     port - The port, if any
     *     username - The username, if any
     *     password - The apssword, if any
     *     url - The URL, if any
     *     options - The options, if any, as an associative array or an instance
     *       of CodeRage\Sys\Config
     *     validate - true to process and validate options; defaults to true
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function getName(): string
    {
        return $this->options['name'];
    }

    public function getType(): string
    {
        return $this->options['type'];
    }

    public function getDbms(): string
    {
        return $this->options['dbms'];
    }

    public function getHost(): ?string
    {
        return $this->options['host'];
    }

    public function getPort(): ?int
    {
        return $this->options['port'];
    }

    public function getUsername(): ?string
    {
        return $this->options['username'];
    }

    public function getPassword(): ?string
    {
        return $this->options['password'];
    }

    public function getDatabase(): ?string
    {
        return $this->options['database'];
    }

    public function getUrl(): ?string
    {
        return $this->options['url'];
    }

    public function getOptions(): ?\CodeRage\Sys\Config
    {
        return $this->options['options'];
    }

    protected static function processOptions(array $options, bool $validate): void
    {
        if (isset($options['options']) && is_array($options['options'])) {
            $options['options'] =
                new \CoseRage\Sys\BasicConfig(
                    $options['options'] + ['validate' => $validate]
                );
        }
    }
}
