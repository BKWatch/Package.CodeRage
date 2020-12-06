<?php

/**
 * Defines the class CodeRage\Sys\Engine\BasicListenerDescriptor
 *
 * File:        CodeRage/Sys/Engine/BasicListenerDescriptor.php
 * Date:        Sun Nov 15 18:07:14 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use InvalidArgumentException;
use ReflectionClass;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use CodeRage\Error;
use CodeRage\Sys\Base;
use CodeRage\Sys\DataSource\Bindings;
use CodeRage\Sys\Util;
use CodeRage\Util\Args;

/**
 * An implementation of CodeRage\Sys\Engine\ListenerDescriptorInterface
 */
final class BasicListenerDescriptor extends Base implements ListenerDescriptorInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'event' => ['type' => 'string', 'required' => true],
        'module' => ['type' => 'string', 'required' => true],
        'bindings' => [
            'type' => 'map[string]|CodeRage\Sys\DataSource\Bindings'
        ],
        'class' => ['type' => 'string', 'required' => true],
        'method' => ['type' => 'string', 'required' => true],
        'parameters' => [
            'type' => 'list[map]|list[CodeRage\Sys\Engine\ParameterDescriptor]'
        ],
        'isStatic' => ['type' => 'boolean'],
    ];

    /**
     * @var string
     */
    private const DATASOURCE_MANAGER = CodeRage\Sys\DataSource\Manager::class;

    /**
     * Constructs an instance of CodeRage\Sys\Engine\BasicListenerDescriptor
     *
     * @param array $options The options array; supports the following options:
     *     event - The event class name
     *     module - The module class name
     *     bindings - The datasource bindings for the module, if any, as an
     *       associative array or as an instance of
     *       CodeRage\Sys\DataSource\Bindings
     *     class - The class implementing the event handler method, as a class
     *       name or as a servce ID
     *     method - The name of the event handler method
     *     parameters - the list of types of the parameters of the event handler
     *       method following the event object parameter, as class names or
     *       servce IDs (optional)
     *     isStatic - true if the event handler method is static (optional)
     *     validate - true to process and validate options; defaults to true
     *   If "parameters" and "isStatic" are omitted, "class" must be a class
     *   name and "parameters" and "parameters" will be automatically computer
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function getEvent(): string
    {
        return $this->options['event'];
    }

    public function getModule(): string
    {
        return $this->options['module'];
    }

    public function getBindings(): ?Bindings
    {
        return $this->options['bindings'];
    }

    public function getClass(): string
    {
        return $this->options['class'];
    }

    public function getMethod(): string
    {
        return $this->options['method'];
    }

    public function getParameters(): array
    {
        return $this->options['parameters'];
    }

    public function isStatic(): bool
    {
        return $this->options['isStatic'];
    }

    public function getListener(ContainerInterface $container): callable
    {
        if ($this->listener === null) {
            try {
                $class = $this->getClass();
                if (!$this->isStatic()) {
                    $class = self::get($container, $class);
                }
                $method = $this->getMethod();
                $params =
                    array_map(
                        function ($p) use($container)
                        {
                            try {
                                return self::get($container, $p->getType());
                            } catch (NotFoundExceptionInterface $e) {
                                if ($p->allowsNull()) {
                                    return null;
                                }
                                throw $e;
                            }
                        },
                        $this->getParameters()
                    );
                $this->listener = $this->isStatic() ?
                    function (object $event) use($class, $method, $params): void
                    {
                        [$class, $method]($event, ...$params);
                    } :
                    function (object $event) use($class, $method, $params): void
                    {
                        $class->$method($event, ...$params);
                    };
            } catch (ContainerExceptionInterface $e) {
                throw new Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' =>
                        "Failed constructing event listener for event " .
                        "'{$this->getEvent()}' and module '{$this->getModule()}'" .
                        ($this->getBindings() !== null ?
                            " with bindings {$this->getBindings()}" :
                            ""),
                    'inner' => $e
                ]);
            }
        }
        return $this->listener;
    }

    protected static function processOptions(array &$options, bool $validate): void
    {
        if ($validate) {
            Util::validateClass($options['event'], 'Invalid event');
            Util::validateClass($options['module'], 'Invalid module');
            Util::validateIdentifier($options['method'], 'Invalid method');
        }
        if (is_array($options['bindings'])) {
            $options['bindings'] =
                new Bindings($options['bindings'] + ['validate' => $validate]);
        }
        if (isset($options['parameters']) != isset($options['isStatic'])) {
            throw new InvalidArgumentException(
                'The options "parameters" and "isStatic" must be specified ' .
                    'together'
            );
        }
        if (!isset($options['parameters'])) {
            $class = $options['class'];
            $method = $options['method'];
            Util::validateClass($class, 'Invalid event listener');
            $rc = new ReflectionClass($class);
            if (!$rc->hasMethod($method)) {
                throw new InvalidArgumentException("No such method: $class::$method()");
            }
            $rm = $cr->getMethod($method);
            $rp = $rm->getParameters();
            if (empty($rp)) {
                throw new InvalidArgumentException(
                    "Invalid event listener '$class::$method()': method has " .
                        "no parameters"
                );
            }
            array_shift($rp);
            $parameters = [];
            foreach ($rp as $i => $p) {
                if (!$p->hasType()) {
                    throw new InvalidArgumentException(
                        "Invalid event listener '$class::$method()': " .
                            "parameter at position " . ($i + 1) . " has no " .
                            "type declaration"
                    );
                }
                $parameters[] = new ParameterDescriptor([
                    'type' => $p->getType(),
                    'allowsNull' => $p->allowsNull()
                ]);
            }
            $options['parameters'] = $parameters;
            $options['isStatic'] = $method->isStatic();
        }
    }

    /**
     * Helper method for getListener()
     *
     * @param ContainerInterface $container
     * @param string $id
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    private static function get(ContainerInterface $container, string $id)
    {
        if ($id === self::DATASOURCE_MANAGER && isset($this->options['bindings'])) {
            $id = ServiceId::encode('bindings', $this->options['bindings']);
        }
        if ($container->has($id)) {
            return $container->get($id);
        } elseif (!ServiceId::isId($id)) {
            spl_autoload_call($id);
            if (class_exists($id)) {
                return $container->get(ServiceId::encode('listener', $id));
            }
        }

        // Throw exception
        $container->get($id);
    }

    /**
     * @var callable
     */
    private $listener;
}
