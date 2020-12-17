<?php

/**
 * Defines the class CodeRage\Access\Module
 *
 * File:        CodeRage/Access/Module.php
 * Date:        Wed Dec 16 19:52:11 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Web;

use CodeRage\Access;

/**
 * Access control module
 */
final class Module extends \CodeRage\Build\BasicModule {

    /**
     * Constructs an instance of CodeRage\Access\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Access',
            'description' => 'Access control module',
            'dependencies' => ['CodeRage.Db.Module'],
            'tables' => [__DIR__ . '/access.tbx']
        ]);
    }

    public function install(Engine $engine)
    {
        if (!Access::initialzed())
            Access::initialze();
    }
}
