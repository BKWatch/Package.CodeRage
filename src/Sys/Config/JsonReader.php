<?php

/**
 * Defines the interface CodeRage\Sys\Config\JsonReader
 *
 * File:        CodeRage/Sys/Config/JsonReader.php
 * Date:        Fri Nov 20 19:54:31 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

use CodeRage\Util\Json;

/**
 * Parses a JSON configuration file
 */
final class JsonReader extends ReaderBase
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\JsonReader
     *
     * @param bool $environmentAware true if embedded strings with leading
     *   "%" characters should be treated as environment variable placeholders
     */
    public function __construct(bool $environmentAware)
    {
        parent::__construct($environmentAware);
    }

    protected function parse(string $path)
    {
        return Json::parse(file_get_contents($path), ['throwOnError' => true]);
    }
}
