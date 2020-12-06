<?php

/**
 * Defines the class CodeRage\Sys\Engine\ListenerProvider
 *
 * File:        CodeRage/Sys/Engine/ListenerProvider.php
 * Date:        Sun Nov 15 21:49:05 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use CodeRage\Log;

/**
 * Implementation of Psr\EventDispatcher\ListenerProviderInterface based on a
 * listener store
 */
class ListenerProvider implements ListenerProviderInterface
{
    /**
     * Constructs an instance of CodeRage\Sys\Engine\ListenerProvider
     *
     * @param CodeRage\Sys\Engine\EventStoreInterface $events The event store
     * @param Psr\Container\ContainerInterface $container The service container
     * @param CodeRage\Log $log The log
     */
    public function __construct(
        EventStoreInterface $events,
        ContainerInterface $container,
        ?Log $log = null
    ) {
        $this->events = $events;
        $this->container = $container;
        $this->log = $log;
        $this->verbose = $log !== null ? $log->getStream(Log::VERBOSE) : null;
    }

    public function getListenersForEvent(object $event) : iterable
    {
        $class = get_class($event);
        if ($this->verbose !== null) {
            $this->verbose->write("ListenerProvider: processing event '$class'");
        }
        foreach ($this->events->getListeners($event) as $desc) {
            if ($this->verbose !== null) {
                $this->verbose->write(
                    "ListenerProvider: processing module '{$desc->getModule()}'" .
                    ($desc->getBindings() !== null ?
                        " with bindings {$desc->getBindings()}" :
                        "")
                );
            }
            yield $desc->getListener($container);
        }
        if ($this->verbose !== null) {
            $this->verbose->write(
                "ListenerProvider: done processing event '$class'"
            );
        }
    }

    /**
     * @var CodeRage\Sys\Engine\EventStoreInterface
     */
    private $events;

    /**
     * @var Psr\Container\ContainerInterface
     */
    private $container;

    /**
     * @var CodeRage\Log
     */
    private $log;

    /**
     * @var CodeRage\Log\Stream
     */
    private $verbose;
}
