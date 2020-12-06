<?php

/**
 * Defines the interface CodeRage\Sys\Engine\BasicModuleStore
 *
 * File:        CodeRage/Sys/Engine/BasicModuleStore.php
 * Date:        Thu Nov 12 23:10:07 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use BadMethodCallException;
use CodeRage\Sys\Util;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\Engine\ModuleStoreInterface
 */
final class BasicModuleStore implements ModuleStoreInterface, ArrayAccess
{
    /**
     * Constructs and instance of CodeRage\Sys\Engine\BasicModuleStore
     *
     * @param array $modules An Associative array mapping module class names to
     *   module descriptors, represented as associative arrays or as instances
     *   of CodeRage\Sys\Engine\ModuleDescriptorInterface
     * @param boolean $validate true to validate $modules
     */
    public function __construct(array $modules, bool $validate = true)
    {
        if ($validate) {
            Args::check($modules, 'map', 'modules');
            foreach ($modules as $n => $m) {
                Args::check(
                    $m,
                    'map|CodeRage\Sys\Engine\ModuleDescriptorInterface',
                    'module'
                );
            }
        }
        foreach ($modules as $n => $m) {
            if (is_array($m)) {
                $modules[$n] =
                    new BasicModuleDescriptor($m + ['validate' => $validate]);
            }
        }
        $this->modules = $modules;
    }

    public function getModule(string $name): ?ModuleDescriptor
    {
        return $this->modules[$name] ?? null;
    }

            /*
             * ArrayAccess interface
             */

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->modules);
    }

    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->modules))
            throw new Error([
                'status' => 'OBJECT_DOES_NOT_EXIST',
                'detail' => "No configuration exists for module '$offset'"
            ]);
        return $this->modules[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('Module store is read-only');
    }

    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('Module store is read-only');
    }

            /*
             * IteratorAggregate interface
             */

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->modules);
    }

    /**
     * @var array
     */
    private $modules;
}
