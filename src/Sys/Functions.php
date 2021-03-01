<?php

/**
 * Defines the class CodeRage\Sys\Functions
 *
 * File:        CodeRage/Sys/Functions.php
 * Date:        Wed Mar  3 19:01:46 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use Psr\Container\NotFoundExceptionInterface;
use CodeRage\Error;

/**
 * Returns the service with the given name
 *
 * @param string $name
 * @return mixed
 * @throws Psr\Container\NotFoundExceptionInterface
 */
function service(string $name)
{
    $engine = Engine::current();
    if ($engine === null) {
        throw new
            class($name) extends Error implements NotFoundExceptionInterface {
                public function __construct($name)
                {
                    parent::__construct([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'details' =>
                            "Failed retrieving service '$name': no engine " .
                            "available"
                    ]);
                }
            };
    }
    return $engine->container()->get($name);
}
