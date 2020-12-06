<?php

/**
 * Defines the abstract class CodeRage\Sys\Config\ReaderBase
 *
 * File:        CodeRage/Sys/Config/ReaderBase.php
 * Date:        Fri Nov 20 19:54:31 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

use CodeRage\File;
use CodeRage\Sys\ConfigInterface;
use CodeRage\Util\Json;

/**
 * Base class for implementations of CodeRage\Sys\Config\ReaderInterface
 */
abstract class ReaderBase implements ReaderInterface
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\ReaderBase
     *
     * @param bool $environmentAware true if embedded strings with leading
     *   "%" characters should be treated as environment variable placeholders
     */
    public function __construct(bool $environmentAware)
    {
        $this->environmentAware = $environmentAware;
    }

    public final function read(string $path): ConfigInterface
    {
        File::checkFile($path, 0b0100);
        $values = $this->parse($path);
        return $this->environmentAware ?
            new EnvironmentAware($values) :
            new Basic($values);
    }

    /**
     * Parses the specified file and returns an associative collection
     *
     * @param string $path The file pathname, already checked for existence and
     *   readability
     * @return mixed An array or instance of stdClass
     */
    abstract protected function parse(string $path);

    /**
     * Returns true if embedded strings with leading "%" characters should be
     * treated as environment variable placeholders
     *
     * @return boolean
     */
    protected final function environmentAware(): bool
    {
        return $this->environmentAware;
    }

    /**
     *
     * @var boolean
     */
    private $environmentAware;
}
