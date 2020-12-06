<?php

/**
 * Defines the class CodeRage\Crypto
 *
 * File:        CodeRage/Crypto.php
 * Date:        Mon Nov  7 20:22:02 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use CodeRage\Util\Args;
use CodeRage\Util\Factory;


/**
 * Provides static methods encrypt(), decrypt(), and loadKms()
 */
final class Crypto {

    /**
     * @var string
     */
    const MATCH_KMS_NAME = '/^[_a-zA-Z0-9]+$/';

    /**
     * Encrypts a string of bytes
     *
     * @param string $data The string of bytes
     * @param array $options The options array; supports the following options:
     *     algorithm - An instance of CodeRage\Crypto\Algorithm; defaults to
     *       an instance of CodeRage\Crypto\Algorithm\V1
     *     key - An encryption key, as a string of bytes (optional)
     *     kms - An instance of CodeRage\Crypto\Kms, or the name of a key
     *       management service to be passed to the function
     *       CodeRage\Crypto\loadKms() (optional)
     *   Exactly one of the options "key" and "kms" must be supplied
     * @return string The encrypted string of bytes
     */
    public static function encrypt($data, array $options = [])
    {
        self::processEncryptionOptions($options);
        $kms = $options['kms'];
        $key = $kms->createKey();
        $encrypted = $options['algorithm']->encrypt($data, $key->value());
        return $kms->compose($encrypted, $key);
    }

    /**
     * Decrypts a string of bytes
     *
     * @param string $data The string of bytes
     * @param array $options The options array; supports the following options:
     *     algorithm - An instance of CodeRage\Crypto\Algorithm; defaults to
     *       an instance of CodeRage\Crypto\Algorithm\V1
     *     key - An encryption key, as a string of bytes (optional)
     *     kms - An instance of CodeRage\Crypto\Kms, or the name of a key
     *       management service to be passed to the function
     *       CodeRage\Crypto\loadKms() (optional)
     *   Exactly one of the options "key" and "kms" must be supplied
     * @return string The decrypted string of bytes
     */
    public static function decrypt($data, array $options = [])
    {
        self::processEncryptionOptions($options);
        list($encryped, $key) = $options['kms']->decompose($data);
        return $options['algorithm']->decrypt($encryped, $key->value());
    }

    /**
     * Returns a named key management service
     *
     * @param string $name The name of the key management service, as determined
     *   by the project configuration. The class name and constructor parameters
     *   are determined as follows:
     *     1. The class name is the value of the configuration variable
     *        coderage.crypto.kms.XXX.class, where XXX is replaced by $name
     *     2. The constructor is passed an associative array of arguments, with
     *        each argumnent encoded by a configuration variable of the form
     *        coderage.crypto.kms.XXX.param.NAME=VALUE
     * @return CodeRage\Crypto\Kms
     * @throw CodeRage\Error if there is no key management service configured
     *   with the given name, or if an error occurs when constructing the key
     *   management service
     */
    public static function loadKms($name)
    {
        Args::check($name, 'string', 'KMS name');
        if (!preg_match(self::MATCH_KMS_NAME, $name))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Malformed KMS name: $name"
                ]);
        $config = Config::current();
        if (!isset(self::$cache[$name])) {
            $class =
                $config->getRequiredProperty("coderage.crypto.kms.$name.class");
            $params = [];
            $prefix = "coderage.crypto.kms.$name.param.";
            $len = strlen($prefix);
            foreach ($config->propertyNames() as $prop)
                if (strncmp($prop, $prefix, $len) == 0)
                    $params[substr($prop, $len)] = $config->getProperty($prop);
            self::$cache[$name] =
                (object) ['class' => $class, 'params' => $params];
        }
        $config = self::$cache[$name];
        return Factory::create([
                   'class' => $config->class,
                   'params' => $config->params
               ]);
    }

    /**
     * Validates and processes options for encrypt() and decrypt()
     *
     * @param array $options The options array
     * @throws CodeRage\Error if the options are invalid, or if an error occurs
     *   when constructing an algorithm or key managament service
     */
    private static function processEncryptionOptions(array &$options)
    {
        Args::checkKey($options, 'algorithm', 'CodeRage\\Crypto\\Algorithm');
        if (!isset($options['algorithm'])) {
            $options['algorithm'] = new \CodeRage\Crypto\Algorithm\V1;
        }
        $keyOpt = Args::uniqueKey($options, ['key', 'kms']);
        $keyVal = $options[$keyOpt];
        if ($keyOpt == 'key') {
            Args::check($keyVal, 'string', 'key');
            $options['kms'] =
                new \CodeRage\Crypto\Kms\Fixed(['key' => $keyVal]);
        } elseif (is_string($keyVal)) {
            $options['kms'] = self::loadKms($keyVal);
        } else {
            Args::check($keyVal, 'CodeRage\\Crypto\\Kms', 'kms');
        }
    }

    /**
     * Maps names of key management services to objects with properties "class"
     * and "params"
     *
     * @var array
     */
    private static $cache = [];
}
