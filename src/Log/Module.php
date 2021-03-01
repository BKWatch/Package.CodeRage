<?php

/**
 * Defines the class CodeRage\Log\Module
 *
 * File:        CodeRage/Log/Module.php
 * Date:        Fri Dec 18 00:55:27 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log;


use CodeRage\Sys\Engine;
use DI\ContainerBuilder;

/**
 * Log Module
 */
final class Module extends \CodeRage\Sys\BasicModule {

    /**
     * Constructs an instance of CodeRage\Log\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Log',
            'description' => 'Log Module',
            'dependencies' => ['CodeRage.Db.Module'],
            'tables' => [__DIR__ . '/log.tbx'],
            'webRoots' => [__DIR__ => 'CodeRage/Log']
        ]);
    }

    /**
     * Returns a list of PHP-DI service definitions
     *
     * @param CodeRage\Sys\Engine $engine
     * @return array
     */
    public function services(Engine $engine): array
    {
        return ['log' => function() { return \CodeRage\Log::current(); }];
    }
}
