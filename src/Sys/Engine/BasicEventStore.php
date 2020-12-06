<?php

/**
 * Defines the class CodeRage\Sys\Engine\BasicEventStore
 *
 * File:        CodeRage/Sys/Engine/BasicEventStore.php
 * Date:        Sun Nov 15 21:33:41 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use CodeRage\Sys\Base;
use CodeRage\Util\Args;

/**
 * An implementation of CodeRage\Sys\Engine\EventStoreInterface
 */
class BasicEventStore extends Base implements EventStoreInterface, \IteratorAggregate
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'events' => ['type' => 'map', 'required' => true],
        'listeners' => ['type' => 'map', 'required' => true]
    ];

    /**
     * Constructs an instance of CodeRage\Sys\Engine\BasicEventStore
     *
     * @param array $events An associative array mapping event class names to
     *   event descriptors, represented as associative arrays or as
     *   instances of CodeRage\Sys\Engine\EventDescriptor
     * @param array $listeners An associative array mapping event class names to
     *   event listener descriptors, represented as associative arrays or as
     *   instances of CodeRage\Sys\Engine\ListenerDescriptor
     */
    public function __construct(array $listeners)
    {
        parent::__construct($options);
    }

    public function getEvent(string $event): ?EventDescriptor
    {
        return $this->options['events'][$event] ?? null;
    }

    public function getListeners(string $event): array
    {
        return $this->options['listeners'][$event] ?? [];
    }

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->options['events']);
    }

    protected static function processOptions(array &$options, bool $validate): void
    {
        if ($validate) {
            foreach ($options['events'] as $event => $desc) {
                Args::check(
                    $desc,
                    'map|CodeRage\Sys\Engine\EventDescriptorInterface',
                    "event descriptor for event '$event"
                );
            }
            foreach ($options['listeners'] as $event => $listeners) {
                foreach ($listeners as $desc) {
                    Args::check(
                        $desc,
                        'map|CodeRage\Sys\Engine\ListenerDescriptorInterface',
                        "listener descriptor for event '$event"
                    );
                }
            }
        }
        foreach ($options['events'] as $event => $desc) {
            if (is_array($desc)) {
                $options['events'][$event] =
                    new BasicEventDescriptor($listener, ['validate' => $validate]);
            }
        }
        foreach ($options['listeners'] as $event => $listeners) {
            foreach ($listeners as $i => $desc) {
                if (is_array($desc)) {
                    $options['listeners'][$event][$i] =
                        new BasicListenerDescriptor($desc, ['validate' => $validate]);
                }
            }
        }
    }
}
