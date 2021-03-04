<?php

/**
 * Defines the class CodeRage\Tool\Module
 *
 * File:        CodeRage/Access/Module.php
 * Date:        Mon Dec 21 19:08:56 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;

/**
 * Tool module
 */
final class Module extends \CodeRage\Sys\BasicModule {

    /**
     * Constructs an instance of CodeRage\Tool\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Tool',
            'description' => 'Tool module',
            'webRoots' => [__DIR__ => 'CodeRage/Tool']
        ]);
    }
}
