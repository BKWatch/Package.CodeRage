<?php

/**
 * Defines the interface CodeRage\Sys\Engine\EventStoreInterface
 *
 * File:        CodeRage/Sys/Engine/EventStoreInterface.php
 * Date:        Sun Nov 15 20:24:41 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

/**
 * Represents a collection of event and event listener descriptors
 */
interface EventStoreInterface extends \Traversable
{
    /**
     * Returns an event description representing the event with the given name,
     * if any
     *
     * @return odeRage\Sys\Engine\EventDescriptor
     */
    public function getEvent(string $event): ?EventDescriptorInterface;

    /**
     * Returns a list of event listener descriptors for the named event, in the
     * order in which they should be invoked
     *
     * @return string
     */
    public function getListeners(string $event): array;
}
