<?php

/**
 * Defines the class CodeRage\Crypto\Algorithm\V1
 * Implementation adapted from http://bit.ly/2fVOW7F and http://bit.ly/2fVUY7Y
 *
 * File:        CodeRage/Crypto/Algorithm/V1.php
 * Date:        Mon Nov  7 20:22:02 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Crypto\Algorithm;

use CodeRage\Crypto\Util;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Implements authenticated AES-256-CBC encryption using the OpenSSL extension
 */
final class V1 implements \CodeRage\Crypto\Algorithm {

    /**
     * @var int
     */
    const METHOD = 'aes-256-cbc';

    /**
     * @var int
     */
    const KEY_LENGTH = 32;

    /**
     * @var int
     */
    const IV_LENGTH = 16;

    /**
     * @var int
     */
    const HMAC_LENGTH = 32;

    public function encrypt($data, $key)
    {
        $this->validate($data, $key);

        // Encrypt
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);
        $ciphertext =
            openssl_encrypt(
                $data,
                self::METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

        // Calculate HMAC
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        return $hmac . $iv . $ciphertext;
    }

    public function decrypt($data, $key)
    {
        $this->validate($data, $key);

        // Decompose data into HMAC, IV, and ciphertext
        $hmac = Util::substr($data, 0, self::HMAC_LENGTH);
        $iv = Util::substr($data, self::HMAC_LENGTH, self::IV_LENGTH);
        $ciphertext =
            Util::substr($data, self::HMAC_LENGTH + self::IV_LENGTH);

        // Verify HMAC
        if (!hash_equals($hmac, hash_hmac('sha256', $iv . $ciphertext, $key, true)))
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' => 'Failed verifying HMAC'
                ]);

        // Decrypt
        $plaintext =
            openssl_decrypt(
                $ciphertext,
                self::METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

        return $plaintext;
    }

    /**
     * Validates input for encrypt() and decrypt()
     *
     * @param string $data
     * @param string $key
     */
    private function validate($data, $key)
    {
        Args::check($data, 'string', 'data');
        Args::check($key, 'string', 'data');
        if (Util::strlen($key) != self::KEY_LENGTH)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid key length: expected ' . self::KEY_LENGTH .
                        '; found ' . Util::strlen($key)
                ]);

    }
}
