<?php

/**
 * Defines the class CodeRage\Build\Tool\Basic.
 *
 * File:        CodeRage/Build/Tool/Basic.php
 * Date:        Thu Dec 25 15:05:08 MST 2008
 * Notice:      This document contains confidential information and
 *              trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Tool;

/**
 * Partial implementation of CodeRage\Build\Tool defining methods for managing
 * metadata.
 */
abstract class Basic implements \CodeRage\Build\Tool {

    /**
     * A collection of metadata.
     *
     * @var CodeRage\Build\Info
     */
    private $info;

    /**
     * Constructs a CodeRage\Build\Tool\Basic.
     *
     * @param CodeRage\Build\Info $info A collection of metadata
     */
    function __construct($info = null)
    {
        $this->info = $info;
    }

    /**
     * Returns an instance of CodeRage\Build\Info describing this target.
     *
     * @return CodeRage\Build\Info
     */
    function info()
    {
        return $this->info;
    }

    /**
     * Specifies an instance of CodeRage\Build\Info describing this target.
     *
     * @param CodeRage\Build\Info $info
     */
    function setInfo(\CodeRage\Build\Info $info)
    {
        $this->info = $info;
    }

    /**
     * Returns an instance of stdClass containing cached data attached to
     * the given build engine
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @return stdClass
     */
    final function cache(\CodeRage\Build\Engine $engine)
    {
        $key = 'cache.' . str_replace('\\', '.', get_class($this));
        if (!$engine->hasProperty($key))
            $engine->setProperty($key, new \stdClass);
        return $engine->getProperty($key);
    }
}
