<?php

/**
 * Defines the class CodeRage\Util\ConfigToken
 *
 * File:        CodeRage/Util/ConfigToken.php
 * Date:        Mon Aug  6 00:57:17 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Access\Session;
use CodeRage\Access\User;
use CodeRage\Build\Config\Builtin as BuiltinConfig;
use CodeRage\Build\Config\Array_ as ArrayConfig;
use CodeRage\Config;
use CodeRage\Util\Args;


/**
 * Defines static methods for loading and saving configurations using sessions
 */
final class ConfigToken {

    /**
     * @var int
     */
    const LIFETIME = 300;

    /**
     * If the current configuration is not built-in, stores it in a session
     * and returns the session ID
     *
     * @param string $lifetime The session lifetime
     * @return string The session ID, or null if the current session is built-in
     */
    public static function create($lifetime = self::LIFETIME)
    {
        $config = Config::current();
        if (!$config instanceof BuiltinConfig) {
            $offset = // Session expiration is checked before config is loaded
                max(0, Time::real() - Time::get());
            $data = [];
            foreach ($config->propertyNames() as $name)
                $data[$name] = $config->getProperty($name);
            $session =
                Session::create([
                    'userid' => User::ROOT,
                    'lifetime' => $lifetime + $offset,
                    'data' => (object)$data
                ]);
            return $session->sessionid();
        } else {
            return null;
        }
    }

    /**
     * Installs the configuration specified by the given session ID
     *
     * @param string $sessionid A session ID
     * @throws CodeRage\Error If $sessionid cannot be used to install a valid
     *   configuration
     */
    public static function load($sessionid)
    {
        $session = Session::load(['sessionid' => $sessionid]);
        $data = $session->data();
        foreach ($data as $n => $v)
            Args::check($v, 'string', "configuration variable '$n'");
        $config = new ArrayConfig((array) $data);
        Config::setCurrent($config);
        if ($config->hasProperty('coderage.util.time.offset')) {
            $offset = $config->getProperty('coderage.util.time.offset');
            Time::set(Time::real() + $offset);
        }
    }
}
