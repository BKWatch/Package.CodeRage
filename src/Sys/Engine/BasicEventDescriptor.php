<?php

/**
 * Defines the class CodeRage\Sys\Engine\BasicEventDescriptor
 *
 * File:        CodeRage/Sys/Engine/BasicEventDescriptor.php
 * Date:        Mon Nov 16 16:33:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use ReflectionClass;
use CodeRage\Sys\Engine\Base;
use CodeRage\Sys\Util;
use CodeRage\Util\Args;

/**
 * An implementation of CodeRage\Sys\Engine\EventDescriptorInterface
 */
final class BasicEventDescriptor extends Base implements EventDescriptorInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'name' => ['type' => 'string', 'required' => true],
        'isStoppable' => ['type' => 'boolean'],
        'mode' => ['type' => 'int']
    ];

    /**
     * Constructs an instance of CodeRage\Sys\Engine\BasicEventDescriptor
     *
     * @param array $options The options array; supports the following options:
     *     name - The event class name
     *     isStoppable - true if the event is stoppable (optional)
     *     mode - The value of one of the constants BUILD, INSTALL, or RUN
     *        (optional)
     *     validate - true to process and validate options; defaults to true
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function getName(): string
    {
        return $this->options['name'];
    }

    public function isStoppable(): bool
    {
        return $this->options['isStoppable'];
    }

    public function getMode(): int
    {
        return $this->options['mode'];
    }

    protected static function processOptions(array &$options, bool $validate): void
    {
        if ($validate) {
            Util::validateEventName($options['name'], 'Invalid event');
            $reflect = null;
            if (!isset($options['isStoppable'])) {
                $reflect = $reflect ?: new ReflectionClass($name);
                $options['isStoppable'] =
                    $reflect->implementsInterface(
                        \Psr\EventDispatcher\StoppableEventInterface::class
                    );
            }
            if (!isset($options['mode'])) {
                $reflect = $reflect ?: new ReflectionClass($name);
                foreach (Util::MODE as $i => $m) {
                    if ($reflect->implementsInterface($m)) {
                        $options['mode'] = $i;
                        break;
                    }
                }
            }
        }
    }
}
