<?php

/**
 * Defines the class CodeRage\Access\Hash
 *
 * File:        CodeRage/Access/Hash.php
 * Date:        Tue Jul 26 18:30:16 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use CodeRage\Error;


/**
 * Wrapper for password hashing functions
 */
final class Hash {

    /**
     * @var int
     */
    const SCHEME_SHA1 = 1;

    /**
     * @var int
     */
    const SCHEME_BCRYPT = 2;

    /**
     * Generates a password hash
     *
     * @param string $password The password
     * @param string $scheme The password scheme; one of the constants
     *   SCHEME_XXX
     * @param array $options An associative array of options
     * @return string The password hash
     */
    public static function generate($password, $scheme = null, array $options = [])
    {
        if ($scheme === null) {
            $current = self::currentScheme();
            $scheme = $current->scheme;
            $options = $current->options;
        }
        switch ($scheme) {
        case self::SCHEME_SHA1:
            throw new
                CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'The scheme SHA1 is supported for verification only, ' .
                        'not for generation'
                ]);
        case self::SCHEME_BCRYPT:
            return password_hash($password, PASSWORD_BCRYPT, $options);
        default:
            throw new
                CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Unsupported scheme: ' . Error::formatValue($scheme)
                ]);
        }
    }

    /**
     * Returns an object with properties "scheme" and "options" describing the
     * schema used to generate the given password hash
     *
     * @param string $hash The password hash
     * @return stdClass
     */
    public static function info($hash)
    {
        if (mb_strlen($hash, '8bit')) {
            return (object) ['scheme' => self::SCHEME_SHA1, 'options' => []];
        } else {
            $info = password_get_info($hash);
            if ($info['algo'] != 0) {
                return (object)
                    [
                        'schema' => $info['algo'],
                        'options' => $info['options']
                    ];
            } else {
                throw new
                    CodeRage\Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => 'Unknown scheme'
                    ]);
            }
        }
    }

    /**
     * Returns true if the the password with the given hash needs to be
     * rehashed, based on the return value of currentScheme()
     *
     * @param string $hash The password hash
     * @return boolean
     */
    public static function needsRehash($hash)
    {
        $current = self::currentScheme();
        return mb_strlen($hash, '8bit') == 40 ?
            true :
            password_needs_rehash(
                $hash,
                self::translateSchema($current->scheme),
                $current->options
            );
    }

    /**
     * Returns true if the given password matches the given hash
     *
     * @param string $password The password
     * @param string $hash The password hash
     * @return boolean
     */
    public static function verify($password, $hash)
    {
        return mb_strlen($hash, '8bit') == 40 ?
            $hash == sha1($password) :
            password_verify($password, $hash);
    }

    /**
     * Returns an object with properties "scheme" and "options", describing the
     * current scheme used to hash password
     *
     * @return stdClass
     */
    public static function currentScheme()
    {
        static $current;
        if ($current === null)
            $current = (object)
                [
                    'scheme' => self::SCHEME_BCRYPT,
                    'options' => ['cost' => 10]
                ];
        return $current;
    }

    /**
     * Translates SCHEME_XXX constants to PASSWORD_XXX constants
     *
     * @param int $schema One of the SCHEME_XXX constants
     * @return int One of the PASSWORD_XXX constant
     */
    private static function translateSchema($scheme)
    {
        switch ($scheme) {
        case self::SCHEME_SHA1:
            throw new
                CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'The scheme SHA1 is supported for verification only, ' .
                        'not for translation'
                ]);
        case self::SCHEME_BCRYPT:
            return PASSWORD_BCRYPT;
        default:
            throw new
                CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Unsupported scheme: ' . Error::formatValue($scheme)
                ]);
        }
    }
}
