<?php

/**
 * Defines the class CodeRage\Util\Bytes
 *
 * File:        CodeRage/Util/Bytes.php
 * Date:        Thu Nov 24 22:46:34 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

/**
 * Defines static methods for accessing strings containing binary data
 */
final class Bytes {

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
