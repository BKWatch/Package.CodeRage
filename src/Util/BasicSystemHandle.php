<?php

/**
 * Defines the class CodeRage\Util\BasicSystemHandle
 *
 * File:        CodeRage/Util/BasicSystemHandle.php
 * Date:        Tue Feb  7 06:03:23 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use Psr\Container\ContainerInterface;
use CodeRage\Access\Session;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Log;

/**
 * Provided for backward compatibility
 */
class BasicSystemHandle extends \CodeRage\Sys\BasicHandle {

    /**
     * Constructs an instance of CodeRage\Util\BasicSystemHandle
     *
     * @param array $options The options array; supports the following options:
     *     engine - An instance of CodeRage\Sys\Engine
     *     container - An instance of Psr\Container\ContainerInterface (optional)
     *     handle - An instance of CodeRage\Sys\Handle (optional)
     *   Exactly on option must be supplied
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }
}
