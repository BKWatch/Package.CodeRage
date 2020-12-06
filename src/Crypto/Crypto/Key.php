<?php

/**
 * Defines the interface CodeRage\Crypto\Key
 * 
 * File:        CodeRage/Crypto/Key.php
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
 * Represents an encryption key
 */
interface Key {

    /**
     * Returns a key for use with an instance of CodeRage\Crypt\Algorithm
     *
     * @return string
     */
    function value();
}
