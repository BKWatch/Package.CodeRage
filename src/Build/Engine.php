<?php

/**
 * Defines the class CodeRage\Build\Engine
 *
 * File:        CodeRage/Build/Engine.php
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
final class Engine extends \CodeRage\Sys\Engine {

    /**
     * Constructs an instance of CodeRage\Build\Engine
     *
     * @param array $options The options array; supports the following options:
     *     log - An instance of CodeRage\Log (optional)
     */
    public function __construct(array $options = [])
    {
        self::__construct($options);
    }
}
