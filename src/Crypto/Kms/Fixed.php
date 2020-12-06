<?php

/**
 * Defines the class CodeRage\Crypto\Kms\Fixed
 * 
 * File:        CodeRage/Crypto/Kms/Fixed.php
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
 * Manages a single encryption key specified at construction
 */
final class Fixed implements \CodeRage\Crypto\Kms {

    /**
     * Constructs an instance of CodeRage\Crypto\Kms\Fixed
     *
     * @param array $options The options array; supports the following options:
     *     key - The encryption key, as a string of bytes (optional)
     *     hex - The ecryption key, hex encoded (optional)
     *   Exactly one of the options "key" and "hex" must be supplied
     */
    public function __construct(array $options)
    {
        $opt = Args::uniqueKey($options, ['key', 'hex']);
        $value = $options[$opt];
        Args::check($value, 'string', $opt);
        $key = null;
        if ($opt == 'hex') {
            if (!ctype_xdigit($value))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The option 'hex' must be a hexidecial string"
                    ]);
            if (Util::strlen($value) % 2 != 0)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The option 'hex' must have even length"
                    ]);
            $key = hex2bin($value);
            if ($key === false)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The option 'hex' must be a hexidecial string"
                    ]);
        } else {
            $key = $value;
        }
        $this->key = new FixedKey($key);
    }

    function createKey() { return $this->key; }

    function compose($memory, \CodeRage\Crypto\Key $key) { return $memory; }

    function decompose($stored) { return [$stored, $this->key]; }

    /**
     * @var CodeRage\Crypto\Kms\FixedKey
     */
    private $key;
}
