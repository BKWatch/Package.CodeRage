<?php

/**
 * Defines the interface CodeRage\Crypto\Kms
 * 
 * File:        CodeRage/Crypto/Kms.php
 * Date:        Thu Nov 10 22:09:40 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Crypto;

/**
 * Represents a key management service
 */
interface Kms {

    /**
     * Returns a key object
     *
     * @return CodeRage\Crypto\Key
     */
    function createKey();

    /**
     * Combines an encrypted string of bytes stored in memory with a key object
     * to produce a string of bytes that can be stored, e.g. in a database
     *
     * @param string $memory The string of bytes
     * @param Key $key The key object
     * @return string
     */
    function compose($memory, Key $key);

    /**
     * Extracts an encrypted string of bytes and a key object from a string of
     * bytes retrieved from storage, e.g. in a database
     *
     * @param string $stored The stored string of bytes
     * @return array A pair [$memory, $key] consisting of a string and an
     *   instance of CodeRage\Crypto\Key
     */
    function decompose($stored);
}
