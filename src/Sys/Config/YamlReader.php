<?php

/**
 * Defines the interface CodeRage\Sys\Config\YamlReader
 *
 * File:        CodeRage/Sys/Config/YamlReader.php
 * Date:        Fri Nov 20 19:54:31 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

use CodeRage\Error;
use CodeRage\Util\ErrorHandler;

/**
 * Parses a YAML configuration file
 */
final class YamlReader extends ReaderBase
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\YamlReader
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
        $yaml = file_get_contents($path);
        $handler = new ErrorHandler;
        $values = $handler->_yaml_parse($yaml);
        if ($values === null || $handler->errno()) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Failed parsing '$path'"
                ]);
        }
        return $values;
    }
}
