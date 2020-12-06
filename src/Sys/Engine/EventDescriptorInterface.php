<?php

/**
 * Defines the interface CodeRage\Sys\Engine\EventDescriptor
 *
 * File:        CodeRage/Sys/Engine/EventDescriptorInterface.php
 * Date:        Mon Nov 16 16:33:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

/**
 * Stores information about an event listener
 */
interface EventDescriptorInterface
{
    /**
     * Represents build mode
     *
     * @var integer
     */
    public const BUILD = 0;

    /**
     * Represents install mode
     *
     * @var integer
     */
    public const INSTALL = 1;

    /**
     * Represents run mode
     *
     * @var integer
     */
    public const RUN = 2;

    /**
     * Returns the event class name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns true if the event is stoppable
     *
     * @return string
     */
    public function isStoppable(): bool;

    /**
     * Returns the value of one of the constants BUILD, INSTALL, or RUN
     *
     * @return string
     */
    public function getMode(): int;
}
