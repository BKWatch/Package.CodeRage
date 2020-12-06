<?php

/**
 * Defines the interface CodeRage\Sys\Engine\ListenerDescriptorInterface
 *
 * File:        CodeRage/Sys/Engine/ListenerDescriptorInterface.php
 * Date:        Sun Nov 15 18:07:14 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use Psr\Container\ContainerInterface;
use CodeRage\Sys\DataSource\Bindings;

/**
 * Stores information about an event listener
 */
interface ListenerDescriptorInterface
{
    /**
     * Returns the event class name
     *
     * @return string
     */
    public function getEvent(): string;

    /**
     * Returns the module class name
     *
     * @return string
     */
    public function getModule(): string;

    /**
     * Returns the datasource bindings for the module, if any
     *
     * @return CodeRage\Sys\DataSource\Bindings
     */
    public function getBindings(): ?\CodeRage\Sys\DataSource\Bindings;

    /**
     * Returns the class implementing the event handler method, as a class name
     * or as a servce ID
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Returns the name of the event handler method
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Returns the list of types of the parameters of the event handler method
     * following the event object parameter, represented as associative arrays
     * or as instances of CodeRage\Sys\Engine\ParameterDescriptor
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Returns true if the event handler method is static
     *
     * @return string
     */
    public function isStatic(): bool;

    /**
     * Returns an event listener
     *
     * @param Psr\Container\ContainerInterface $container
     * @return callable
     */
    public function getListener(ContainerInterface $container): callable;
}
