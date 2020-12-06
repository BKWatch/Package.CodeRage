<?php

/**
 * Defines the class CodeRage\Sys\Engine\ServiceId
 *
 * File:        CodeRage/Sys/Engine/ServiceId.php
 * Date:        Sun Nov 15 19:49:51 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use CodeRage\Util\Base62;

/**
 * Utility for managing internal service container IDs
 */
final class ServiceId
{
    /**
     * @var string
     */
    private const PREFIX = 'coderage.sys';

    /**
     * @var string
     */
    private const MATCH_ID = '/^coderage\.sys.[a-zA-Z0-1]+$/';

    /**
     * Returns true if the given value is a well-formed internal service
     * container ID
     *
     * @param string $id
     * @return bool
     */
    public static function isId(string $id): bool
    {
        return preg_match(self::MATCH_ID, $id) > 0;
    }

    /**
     * Encodes the given information as an internal service container ID
     *
     * @param string $type The general category of value
     * @param string $value The value
     * @return string
     */
    public static function encode(string $type, string $value): string
    {
        return Base62::encode(self::PREFIX . ".$type.$value");
    }
}
