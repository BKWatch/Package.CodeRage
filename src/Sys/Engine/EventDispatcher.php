<?php

/**
 * Defines the class CodeRage\Sys\Engine\EventDispatcher
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

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use CodeRage\Error;
use CodeRage\Log;

/**
 * Implementation of Psr\EventDispatcher\EventDispatcherInterface
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var int
     */
    private const MAX_STACK_SIZE = 50;

    /**
     * Constructs an instance of CodeRage\Sys\Engine\EventDispatcher
     *
     * @param Psr\EventDispatcher\ListenerProviderInterface $provider The
     *   listener provider
     * @param CodeRage\Sys\Engine\EventStore $events The event store
     * @param CodeRage\Log $log The log
     * @param int $maxStackSize The maximum event stack size
     */
    public function __construct(
        ListenerProviderInterface $provider,
        EventStore $events,
        ?Log $log = null,
        ?int $maxStackSize = null
    ) {
        $this->provider = $provider;
        $this->events = $events;
        $this->log = $log;
        $this->verbose = $log !== null ? $log->getStream(Log::VERBOSE) : null;
        $this->maxStackSize = $maxStackSize !== null ?
            $maxStackSize :
            self::MAX_STACK_SIZE;
    }

    public function dispatch(object $event): object
    {
        if (count($this->eventStack) > $this->maxStackSize) {
            throw new Error([
                'status' => 'INTERNAL_ERROR',
                'details' =>
                    'Exceeded maximum event stack size of ' . $this->maxStackSize
            ]);
        }
        $this->eventStack[] = $event;
        try {
            return $this->dispatchImpl($event);
        } finally {
            array_pop($this->eventStack);
            if (empty($this->eventStack)) {
                $this->mode = null;
            }
        }
    }

    /**
     * Helper for dispatch()
     *
     * @param object $event The event to dispatch
     * @return object The event, after modification by listeners
     */
    private function dispatchImpl(object $event): object
    {
        $desc = $this->getEvent(get_class($event));
        if ($desc === null) {
            return $event;
        }
        $mode = $desc->getMode();
        if ($this->mode === null) {
            $this->mode = $mode;
        } elseif ($mode != $this->mode) {
            throw new Error([
                'status' => 'INTERNAL_ERROR',
                'details' =>
                    "The event '{$desc->getName()}' cannot be dispatched in " .
                        self::translateMode($this->mode) . " mode"
            ]);
        }
        foreach ($this->provider->getListenersForEvent($event) as $lst) {
            $lst($event);
            if ($desc->isStoppabale() && $event->isPropagationStopped()) {
                break;
            }
        }
        return $event;
    }

    /**
     * Returns a human-readable label for the specified event mode
     *
     * @param int $mode The value of one of the
     *   CodeRage\Sys\Engine\EventDescriptor constants BUILD, INSTALL, or RUN
     * @return string
     */
    private static function translateMode(int $mode): string
    {
        switch ($mode) {
            case EventDescriptor::BUILD:
                return 'build';
            case EventDescriptor::INSTALL:
                return 'install';
            case EventDescriptor::RUN:
            default;
                return 'run';
        }
    }

    /**
     * @var Psr\EventDispatcher\ListenerProviderInterface
     */
    private $provider;

    /**
     * @var CodeRage\Sys\Engine\EventStore
     */
    private $events;

    /**
     * @var CodeRage\Log
     */
    private $log;

    /**
     * @var CodeRage\Log\Stream
     */
    private $verbose;

    /**
     * @var int
     */
    private $maxStackSize;

    /**
     * @var array
     */
    private $eventStack = [];

    /**
     * @var int
     */
    private $mode;
}
