<?php

/**
 * Defines the interface CodeRage\Crypto\Algorithm
 * 
 * File:        CodeRage/Crypto/Algorithm.php
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
 * Represents a symmetric cipher
 */
interface Algorithm {

    /**
     * Encrypts a string
     *
     * @param string $data The data
     * @param string $key The encryption key, as a string of bytes
     * @return string The encrypted string
     */
    function encrypt($data, $key);

    /**
     * Decrypts a string
     *
     * @param string $data
     * @param string $key The encryption key, as a string of bytes
     * @return string The decrypted string
     */
    function decrypt($data, $key);
}
