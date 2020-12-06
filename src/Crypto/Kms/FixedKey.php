<?php

/**
 * Defines the class CodeRage\Crypto\Kms\FixedKey
 * 
 * File:        CodeRage/Crypto/Kms/FixedKey.php
 * Date:        Mon Nov  7 20:22:02 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Crypto\Kms;

use CodeRage\Crypto\Util;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Implementation of CodeRage\Crypto\Key used by CodeRage\Crypto\Kms\Fixed
 */
final class FixedKey implements \CodeRage\Crypto\Key {

    /**
     * Constructs an instance of CodeRage\Crypto\Key\FixedKey
     *
     * @param string $key The encryption key, as a string of types
     */
    public function __construct($key)
    {
        Args::check($key, 'string', 'key');
        $this->key = $key;
    }

    public function value() { return $this->key; }

    /**
     * @var string
     */
    private $key;
}
