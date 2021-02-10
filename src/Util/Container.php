<?php

/**
 * Defines the class CodeRage\Util\BasicSystemHandle
 *
 * File:        CodeRage/Util/BasicSystemHandle.php
 * Date:        Tue Feb  7 06:03:23 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use ReflectionClass;
use ReflectionFuntion;
use ReflectionMethod;
use Psr\Container\NotFoundExceptionInterface;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Args;
use CodeRage\Util\Factory;

/**
 * Simple dependency injection container
 */
class Container implements \Psr\Container\ContainerInterface {

    /**
     * Constructs an instance of CodeRage\Util\Container
     *
     * @param CodeRage\Util\Container $parent The parent container, if any
     */
    public function __construct(?Container $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Returns true if a service with the given name has been registered
     *
     * @param string $name The service name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->aliases[$name]) ||
               $this->parent !== null && $this->parent->has($name);
    }

    /**
     * Returns the service with the given name, if one is available
     *
     * @param array $name The service name
     * @return mixed
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    public function get($name)
    {
        $alias = $this->aliases[$name] ?? null;
        if ($alias === null && $this->parent === null) {
            $this->throwNotFound("No such service: $name");
        }
        return $alias !== null ?
            $this->load($alias) :
            $this->parent->get($name);
    }

    /**
     * Registers a service
     *
     * @param array $options The options array; supports the following options:
     *   name - The service name
     *   service - The service, as a class name, a callable, or any other value
     *   shared - true to cache the service when it is first constructed and
     *     use it to satisfy all subsequent matching calls to getService();
     *     defaults to true
     *   factory - specifies whether the provided service is to be treated as a
     *     factory or a service instance; defaults to true for names of invokabe
     *     classes and for callables and to false otherwise
     * @throws CodeRage\Error
     */
    public function register(array $options): object
    {
        $name =
            Args::checkKey($options, 'name', 'string', [
                'required' => true
            ]);
        $service =
            Args::checkKey($options, 'service', 'string|callable|object', [
                'required' => true
            ]);
        $shared =
            Args::checkKey($options, 'shared', 'boolean', [
                'detaul' => true
            ]);
        if (isset($this->services[$name])) {
            throw new
                Error([
                    'status' => 'OBJECT_EXISTS',
                    'details' =>
                        "A service with name '$name' is arlready registered"
                ]);
        }
        $isFactory = Args::checkKey($options, 'factory', 'boolean');
        $factory = $instance = $returnType = null;
        if (is_string($service) && Factory::classExists($service)) {
            if ($isFactory !== false) {
                $reflect = new ReflectionClass($class);
                if ($reflect->hasMethod('__invoke')) {
                    $isFactory = true;
                    $invoke = $reflect->getMethod('__invoke')->getReturnType();
                } elseif ($isFactory) {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid factory: class '$class' has no " .
                                "__invoke() method"
                        ]);
                } else {
                    $isFactory = false;
                }
            }
            if ($isFactory) {
                $factory =
                    function() use ($service)
                    {
                        static $instance;
                        if ($instance === null) {
                            $instance = $this->construct($service);
                        }
                        return $this->call($instance);
                    };
            } else {
                $factory =
                    function() use($service)
                    {
                        return $this->construct($service);
                    };
            }
        } elseif (is_callable($service) && $isFactory !== false) {
            $isFactory = true;
            $factory = $service;
            $returnType = $this->getReturnType();
        } elseif ($isFactory) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Factories must be callable or names of invocable classes"
                ]);
        } else {
            $isFactory = false;
            $instance = $service;
            if (is_object($service)) {
                $returnType = get_class($service);
            }
        }
        $this->services[$name] =
            [
                'name' => $name,
                'factory' => $factory,
                'instance' => $instance,
                'shared' => $shared
            ];
        foreach ($this->subTypes($returnType) as $type) {
            if (!isset($this->aliases[$type])) {
                $this->aliases[$type] = $service;
            }
        }
    }

    /**
     *
     * @param unknown $callable
     */
    public function call($callable)
    {

    }

    /**
     * Returns the return type of the given callable, if available
     *
     * @param callable $callable
     * @return string
     */
    private static function getReturnType(callable $callable): ?string
    {
        if (is_string($callable)) {
            return strpos($callable, ':') !== false ?
                (new ReflectionMethod($callable))->getReturnType() :
                (new ReflectionFunction($callable))->getReturnType();
        } elseif (is_array($callable)) {
            [$obj, $method] = $callable;
            return (new ReflectionClass($obj))->getMethod($method)->getReturnType();
        } elseif ($callable instanceof \Closure) {
            return (new ReflectionFunction($callable))->getReturnType();
        } elseif (is_object($callable)) {
            return (new ReflectionClass($callable))->getMethod('__invoke')->getReturnType();
        } else {
            return null;
        }
    }

    /**
     * Returns the return type of the given callable, if available
     *
     * @param callable $callable
     * @return string
     */
    private static function getSubtypes(string $type): array
    {
        if (!Factory::classExists($type) && !Factory::interfaceExists($type)) {
            return [];
        }
        $stack = [new ReflectionClass($type)];
        $result = [];
        while (!empty($stack)) {
            $t = array_pop($stack);
            if (($p = $t->getParentClass()) !== false) {
                $stack[] = $p;
                $result[] = $p->getName();
            }
            foreach ($t->getInterfaces() as $i) {
                $stack[] = $i;
                $result[] = $i->getName();
            }
        }
        return array_unique($result);
    }

    /**
     * Throws an instance of Psr\Container\NotFoundExceptionInterface
     *
     * @param string $message The error message
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    private function throwNotFound(string $message)
    {
        throw new
            class($message) extends Error implements NotFoundExceptionInterface {
                public function __construct(string $message)
                {
                    parent::__construct([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'details' => $message
                    ]);
                }
            };
    }

    /**
     * @var CodeRage\Util\Container
     */
    private $parent;

    /**
     * @var array
     */
    private $services = [];

    /**
     * @var array
     */
    private $aliases = [];
}
