<?php

/**
 * Defines the class CodeRage\Util\Shared
 *
 * File:        CodeRage/Util/Shared.php
 * Date:        Tue Apr 14 22:18:55 UTC 2020
 * Notice:      This document contains confidential information and
 *              trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

/**
 * Stores a reference-counted instance of a class specified at construction
 */
class Shared {

    /**
     * Constructs an instance of CodeRage\Util\Shared
     *
     * @param string $class The class name of the stored instance
     * @param array $params A json-encodable array of constructor parameters;
     */
    public function __construct(string $class, array $params = [])
    {
        $key = $class . '#' . Json::encode($params, ['throwOnError' => true]);
        if (!isset(self::$storage[$key]))
            self::$storage[$key] = (object)
                [
                    'class' => $class,
                    'params' => $params,
                    'instance' => new $class(...$params),
                    'key' => $key,
                    'count' => 0
                ];
        $this->pointer = self::$storage[$key];
        ++$this->pointer->count;
    }

    public function __destruct()
    {
        $key = $this->pointer->key;
        if (--self::$storage[$key]->count == 0)
            unset(self::$storage[$key]);
    }

    /**
     * Returns the stored instance
     *
     * @return object
     */
    public function get() : object
    {
        return $this->pointer->instance;
    }

    /**
     * Replaces the stored instance with a newly constructed instance
     *
     * @return object
     */
    public function reset() : void
    {
        $class = $this->pointer->class;
        $params = $this->pointer->params;
        $this->pointer->instance = new $class(...$params);
    }

    /**
     * @var array
     */
    private static $storage = [];

    /**
     * @var object
     */
    private $pointer;
}
