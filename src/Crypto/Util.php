<?php

/**
 * Defines the class CodeRage\Crypto\Util
 *
 * File:        CodeRage/Crypto/Util.php
 * Date:        Mon Nov  7 23:12:24 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Crypto;

/**
 * Collection of static utility methods
 */
class Util {

    /**
     * Returns the length of the given string in bytes
     *
     * @param string $value The string
     * @return int
     */
    static function strlen($value) { return mb_strlen($value, '8bit'); }

    /**
     * Returns a substring of the given string
     *
     * @param string $value The string
     * @param int $offset The offset, in bytes
     * @paramn int $length The length, in $bytes
     * @return string
     */
    static function substr($value, $offset, $length = null)
    {
        return mb_substr($value, $offset, $length, '8bit');
    }
}
