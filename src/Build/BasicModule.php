<?php

/**
 * Defines the class CodeRage\Build\BasicModule
 *
 * File:        CodeRage/Build/BasicModule.php
 * Date:        Tue Mar  2 03:03:35 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Provided for backward compatibility
 */
class BasicModule extends \CodeRage\Sys\BasicModule {

    /**
     * Constructs an instance of CodeRage\Build\BasicModule
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }
}
