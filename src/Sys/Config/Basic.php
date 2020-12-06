<?php

/**
 * Defines the class CodeRage\Sys\Config\Basic
 *
 * File:        CodeRage/Sys/Config/Basic.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\SysConfig;

use CodeRage\Error;
use CodeRage\Sys\ConfigInterface;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\ConfigInterface based on a stored associative
 * array and an optional fallback configuration
 */
final class Basic implements ConfigInterface, \JsonSerializable
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\Basic
     *
     * @param array $options An associative array of configuration options
     * @param odeRage\Sys\ConfigInterface $fallback A configuration to consult
     *   for keys not in $options
     */
    public function __construct(array $options = [], ?ConfigInterface $fallback = null)
    {
        Args::check($options, 'map[scalar]', 'options');
        $this->options = $options;
        $this->fallback = $fallback;
    }

    public function hasProperty(string $name): bool
    {
        if (array_key_exists($name, $this->options)) {
            return true;
        } elseif ($this->fallback !== null) {
            return $this->fallback->hasProperty($name);
        } else {
            return false;
        }
    }

    public function getProperty(string $name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        } elseif ($this->fallback !== null) {
            return $this->fallback->getProperty($name, $default);
        } else {
            return $default;
        }
    }

    public function getRequiredProperty(string $name)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        } elseif ($this->fallback !== null) {
            return $this->fallback->getRequiredProperty($name);
        } else {
            throw new Error([
                'status' => 'CONFIGURATION_ERROR',
                'details' => "The configuration variable '$name' is not set"
            ]);
        }
    }

    public function jsonSerialize(): array
    {
        if ($this->fallback !== null) {
            throw new Error([
                'status' => 'CONFIGURATION_ERROR',
                'details' =>
                    'Instances of ' . self::class . ' with fallback ' .
                    'configuration do not support JSON serialization'
            ]);
        }
        return $this->options;
    }

    /**
     * @var array
     */
    private $options;

    /**
     * @var CodeRage\Sys\ConfigInterface
     */
    private $fallback;
}
