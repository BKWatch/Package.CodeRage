<?php

/**
 * Defines the class CodeRage\Sys\Config\Forgetful
 *
 * File:        CodeRage/Sys/Config/Forgetful.php
 * Date:        Thu Nov 19 22:43:54 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

use CodeRage\Error;
use CodeRage\Sys\ConfigInterface;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\ConfigInterface that delegates to a stored
 * configuration but ignores certain keys
 */
final class Forgetful implements ConfigInterface
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\Forgetful
     *
     * @param CodeRage\Sys\ConfigInterface $inner The configuration to which to
     *   delegate requests
     * @param array $ignore A list of keys to be treated as if they do not exist
     *   in $inner
     */
    public function __construct(ConfigInterface $inner, array $ignore)
    {
        Args::check($ignore, 'list[string]', 'list of keys to ignore');
        $this->inner = $inner;
        $this->ignore = [];
        foreach ($ignore as $name) {
            $this->ignore[$name] = 1;
        }
    }

    public function hasProperty(string $name): bool
    {
        return !isset($this->ignore[$name]) && $this->inner->hasProperty($name);
    }

    public function getProperty(string $name, $default = null)
    {
        return !isset($this->ignore[$name]) ?
            $this->inner->getProperty($name, $default) :
            $default;
    }

    public function getRequiredProperty(string $name)
    {
        if (!isset($this->ignore[$name])) {
            return $this->inner->getRequiredProperty($name);
        } else {
            throw new Error([
                'status' => 'CONFIGURATION_ERROR',
                'details' => "The configuration variable '$name' is not set"
            ]);
        }
    }

    /**
     * @var CodeRage\Sys\ConfigInterface
     */
    private $inner;

    /**
     * @var array
     */
    private $ignore;
}
