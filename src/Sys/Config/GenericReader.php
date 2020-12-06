<?php

/**
 * Defines the interface CodeRage\Sys\Config\GenericReader
 *
 * File:        CodeRage/Sys/Config/GenericReader.php
 * Date:        Fri Nov 20 19:54:31 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

/**
 * Parses a configuration file using the file extension to determine the
 * expected format
 */
final class GenericReader extends ReaderBase
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\GenericReader
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
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $reader = null;
        switch ($ext) {
            case 'json':
                $reader = new JsonReader($this->environmentAware());
                break;
            case 'yml':
            case 'yaml':
                $reader = new YamlReader($this->environmentAware());
                break;
            default:
                throw new Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Unsuppoprted configuration file extension: $ext"
                ]);
        }
        return $reader->parse($path);
    }
}
