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

/**
 * Log Module
 */
final class Module extends \CodeRage\Build\BasicModule {

    /**
     * Constructs an instance of Data\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Log',
            'description' => 'Log Module',
            'dependencies' => ['CodeRage.Db.Module'],
            'tables' => [__DIR__ . '/log.tbx']
        ]);
    }
}
