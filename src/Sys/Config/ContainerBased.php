<?php

/**
 * Defines the class CodeRage\Sys\Config\ContainerBased
 *
 * File:        CodeRage/Sys/Config/ContainerBased.php
 * Date:        Thu Nov 19 19:16:42 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

use Psr\Container\ContainerInterface;
use CodeRage\Error;
use CodeRage\Sys\ConfigInterface;

/**
 * Implementation of CodeRage\Sys\ConfigInterface based on a PSR-11 container
 */
final class ContainerBased implements ConfigInterface
{
    /**
     * Constructs an instance of CodeRage\Sys\Config\ContainerBased
     *
     * @param Psr\Container\ContainerInterface $container The container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function hasProperty(string $name): bool
    {
        return $this->container->has($name);
    }

    public function getProperty(string $name, $default = null)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        } else {
            return $default;
        }
    }

    public function getRequiredProperty(string $name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        } else {
            throw new Error([
                'status' => 'CONFIGURATION_ERROR',
                'details' => "The configuration variable '$name' is not set"
            ]);
        }
    }

    /**
     * @var Psr\Container\ContainerInterface
     */
    private $container;
}
