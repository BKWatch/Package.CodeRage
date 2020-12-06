<?php

/**
 * Defines the class CodeRage\Sys\DataSource\Bindings
 *
 * File:        CodeRage/Sys/DataSource/Bindings.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\DataSource;

use CodeRage\Error;
use CodeRage\Util\Args;

/**
 * Maps module data source names to the names of the data sources in the project
 * configuration to which they are bound
 */
class Bindings implements \ArrayAccess, \IteratorAggregate
{
    /**
     * Constructs an instance of CodeRage\Sys\DataSource\Bindings
     *
     * @param array mapping An associative array mapping module data source
     *   names to project data source names
     */
    public function __construct(array $bindings = [])
    {
        foreach ($bindings as $n => $v) {
            $this[$n] = $v;
        }
    }

            /*
             * ArrayAccess interface
             */

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->bindings);
    }

    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->bindings))
            throw new Error([
                'status' => 'OBJECT_DOES_NOT_EXIST',
                'detail' => "No binding exists for data source '$offset'"
            ]);
        return $this->bindings[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        Args::check($value, 'string', 'datasource name');
        if (!ctype_alnum($value))
            throw new Error([
                'status' => 'INVALID_PARAMETER',
                'detail' => "Invalid datasource name: $value"
            ]);
        $this->bindings[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset]);
    }

            /*
             * IteratorAggregate interface
             */

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->bindings);
    }

    public function __toString() : string
    {
        return join(',', $this->bindings);
    }

    /**
     * @var array
     */
    private $bindings = [];
}
