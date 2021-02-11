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
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Throwable;
use Psr\Container\ContainerExceptionInterface;
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
        $name = self::class;
        $this->aliases[$name] = $name;
        $this->name[$name] = (object)
            [
                'factory' => null,
                'parameters' => null,
                'instance' => $this,
                'shared' => true
            ];
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
     * Returns the service with the given name
     *
     * @param array $name The service name
     * @return mixed
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    public function get($name)
    {
        $alias = $this->aliases[$name] ?? null;
        if ($alias === null && $this->parent === null) {
            $this->throwNotFoundException("No such service: $name");
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
     *   service - The service, as a class name, as a callable to be used as a
     *     factory to construct the service instance, or as any other value, to
     *     be treated as the service instance itself
     *   shared - true to cache the service when it is first constructed and
     *     use it to satisfy all subsequent matching calls to getService();
     *     defaults to true
     *   factory - Specifies whether a provided callable is to be treated as a
     *     factory or as a service instance
     * @throws CodeRage\Error
     */
    public function add(array $options): void
    {
        $name =
            Args::checkKey($options, 'name', 'string', [
                'required' => true
            ]);
        $service =
            Args::checkKey($options, 'service', 'string|callable|object', [
                'required' => true
            ]);
        $shared = Args::checkKey($options, 'shared', 'boolean');
        $isFactory = Args::checkKey($options, 'factory', 'boolean');
        if (isset($this->services[$name])) {
            throw new
                Error([
                    'status' => 'OBJECT_EXISTS',
                    'details' =>
                        "A service with name '$name' is arlready registered"
                ]);
        }
        $factory = $signature = $instance = null;
        if (is_callable($service)) {
            if ($isFactory === false) {
                $instance = $service;
            } else {
                $factory = $service;
                $signature = self::getSignature($service);
            }
        } elseif ($isFactory !== null) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Factories must be callable"
                ]);
        } elseif (is_string($service) && Factory::classExists($service)) {
            $factory =
                function(...$args)
                {
                    return new $service(...$args);
                };
            $signature = self::getConstructorSignature($service);
        } elseif ($shared === false) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Literal service instances must be shared'
                ]);
        } else {
            $instance = $service;
            if (is_object($service)) {
                $signature = [[], get_class($service)];
            }
        }
        $shared = $shared ?? true;
        $this->services[$name] = (object)
            [
                'factory' => $factory,
                'parameters' => $signature[0] ?? null,
                'instance' => $instance,
                'shared' => $shared
            ];
        $this->aliases[$name] = $name;
        $returnType = $signature[1] ?? null;
        if ($returnType !== null) {
            $this->addAliases($name, $returnType);
        }
    }

    /**
     * Returns an instance of the named service
     *
     * @param string $name The service name
     */
    private function load(string $name)
    {
        $info = $this->services[$name];
        if ($info->instance !== null) {
            return $info->instance;
        }

        // Construct arguments
        $args = [];
        foreach ($info->parameters as $i => $param) {
            if ($param !== null) {
                $n = (string) $param->getType();
                if ($this->has($n)) {
                    $args[] = $this->get($n);
                } elseif ($param->isOptional()) {
                    $args[] = null;
                } else {
                    $this->throwNotFoundException(
                        "Failed loading service '$name'; missing " .
                        "service '$n' required for parameter $i"
                    );
                }
            } else {
                $args[] = null;
            }
        }

        // Construct instance
        $instance = null;
        try {
            $instance = ($info->factory)(...$args);
        } catch (Throwable $e) {
            $this->throwContainerException("Failed loading service $name", $e);
        }

        // Cache instance
        if ($info->shared) {
            $info->instance = $instance;
            if (is_object($instance)) {
                $this->addAliases($name, get_class($instance));
            }
        }

        return $instance;
    }

    /**
     * Returns the signature of the given callable, if available
     *
     * @param callable $callable
     * @return array A pair [$input, $output], where $input is an array of
     *   instances of ReflectionParam and $output is a class or interface name
     *   or null
     */
    private static function getSignature(callable $callable): ?array
    {
        $func = null;
        if (is_string($callable)) {
            $func = strpos($callable, ':') !== false ?
                new ReflectionMethod($callable) :
                new ReflectionFunction($callable);
        } elseif (is_array($callable)) {
            [$o, $m] = $callable;
            $func = (new ReflectionClass($o))->getMethod($m);
        } elseif ($callable instanceof \Closure) {
            $func = new ReflectionFunction($callable);
        } elseif (is_object($callable)) {
            $func = (new ReflectionClass($callable))->getMethod('__invoke');
        }
        $return = null;
        if ($func->hasReturnType()) {
            $type = (string) $func->getReturnType();
            $return =
                Factory::classExists($type) || Factory::interfaceExists($type) ?
                    $type :
                    null;
        }
        return [self::getParameterList($func), $return];
    }

    /**
     * Returns the signatrue of the constructor of the named class, if available
     *
     * @param string $class
     * @return array A pair [$input, $output], where $input is an array of
     *   instances of ReflectionParam and $output is the class name
     */
    private static function getConstructorSignature(string $class): ?string
    {
        $constructor = null;
        for ( $reflect = new ReflectionClass($class);
              $reflect !== null;
              $reflect = $reflect->getParentClass() )
        {
            if ($reflect->hasConstructor()) {
                $constructor = $reflect->getConstructor();
                break;
            }
        }
        return [self::getParameterList($constructor), $class];
    }

    /**
     * Returns a list of service names or null values that can be used to
     * supply function arguments
     *
     * @param ReflectionFunctionAbstract $func
     */
    private static function getParameterList(ReflectionFunctionAbstract $func)
    {
        $result = [];
        foreach ($func->getParameters() as $param) {
            $type = $param->hasType() ? (string) $param->getType() : null;
            if (self::isClassOrInterface($type)) {
                $result[] = $param;
            } elseif ($param->isOptional()) {
                $result[] = null;
            } else {
                $i = $param->getPosition();
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The value of parameter $i cannot be supplied " .
                            "from the container, since it does not declared " .
                            "to have class or interface type"
                    ]);
            }
        }
        return $result;
    }

    /**
     * Adds aliases for each ancestral class or interface types of the named
     * type
     *
     * @param string $name The service name
     * @param string $type A class name
     */
    private function addAliases(string $name, string $type): void {
        foreach ($this->getSubTypes($type) as $t) {
            if (!isset($this->aliases[$t])) {
                $this->aliases[$t] = $name;
            }
        }
    }

    /**
     * Returns the return type of the given callable, if available
     *
     * @param string $type
     * @return array
     */
    private static function getSubtypes(string $type): array
    {
        if (!Factory::classExists($type) && !Factory::interfaceExists($type)) {
            return [];
        }
        $stack = [new ReflectionClass($type)];
        $result = [$type];
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
     * Returns true if the given string is the name of a class or interface
     *
     * @param string $type
     * @return boolean
     */
    private static function isClassOrInterface(?string $type): bool
    {
        return $type !== null &&
               (Factory::classExists($type) || Factory::interfaceExists($type));
    }

    /**
     * Throws an instance of Psr\Container\NotFoundExceptionInterface
     *
     * @param string $message The error message
     * @throws Psr\Container\NotFoundExceptionInterface
     */
    private function throwNotFoundException(string $message)
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
     * Throws an instance of Psr\Container\ContainerExceptionInterface
     *
     * @param string $message The error message
     * @throws Psr\Container\ContainerExceptionInterface
     */
    private function throwContainerException(string $message, ?Throwable $inner = null)
    {
        throw new
            class($message) extends Error implements ContainerExceptionInterface {
                public function __construct(string $message)
                {
                    parent::__construct([
                        'status' => 'INTERNAL_ERROR',
                        'details' => $message,
                        'inner' => $inner
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
