<?php

/**
 * Defines the class CodeRage\Util\SystemContainer
 *
 * File:        CodeRage/Util/SystemContainer.php
 * Date:        Thu Feb 11 03:52:16 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

/**
 * Dependency injection container with built-in support for some important system
 * services
 */
class SystemContainer extends Container {

    /**
     * Constructs an instance of CodeRage\Util\SystemContainer
     *
     * @param array $options The options array; supports the following options:
     *     parent - The parent container, if any, as an instance of
     *       Psr\Container\ContainerInterface
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->add([
            'name' => 'config',
            'service' =>  function() { return \CodeRage\Config::current(); }
        ]);
        $this->add([
            'name' => 'db',
            'service' =>  \CodeRage\Db::class
        ]);
        $this->add([
            'name' => 'log',
            'service' =>  function() { return \CodeRage\Log::current(); }
        ]);
    }
}
