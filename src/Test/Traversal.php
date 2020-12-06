<?php

/**
 * Defines the class CodeRage\Test\Traversal
 * 
 * File:        CodeRage/Test/Traversal.php
 * Date:        Tue Jul  9 15:14:14 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use CodeRage\Error;
use CodeRage\Util\XmlEncoder;


/**
 * Perfomes a depth-first traveral of a native data structure
 */
final class Traversal extends TraversalBase {

    /**
     * constructs a CodeRage\Test\Traversal
     *
     * @param mixed $data A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param array $options The options array; supports the following options:
     *     xmlEncoder - An instance of CodeRage\Util\XmlEncoder
     *       (optional)
     *     path - The path to $data within the containing data structure, if
     *       any (optional)
     *     preOrder - true to visit parent nodes before their children; defaults
     *       to true
     */
    public function __construct(&$data, array $options = [])
    {
        parent::__construct($options);
        $this->data = $data;
    }

    /**
     * Traverses the underlying data structure, invoking the given callable
     * at each node
     *
     * @param callable $visitor A callable f(&$data, $type, $path), where
     *   $data is the node, $t5ype is one of "scalar", "list", or "map", and
     *   $path is the address of $data within the containing data structure
     */
    public function traverse(callable $visitor)
    {
        $this->traverseImpl($visitor, $this->data, $this->path);
    }

    /**
     * Helper to traverse()
     *
     * @param callable $callable
     * @param mixed $data
     * @param CodeRage\Test\PathExpr $path
     */
    private function traverseImpl(callable $callable, &$data, PathExpr $path)
    {
        $type = self::getType($data);
        if ($this->preorder)
            $callable($data, $type, $path);
        if ($type == 'list') {
            $itemName = self::itemName($path);
            for ($i = 0, $n = sizeof($data); $i < $n; ++$i) {
                $comp = new PathComponent($itemName, true, $i);
                self::traverseImpl(
                    $callable,
                    $data[$i],
                    $path->append($comp)
                );
            }
        } elseif ($type == 'object') {
            foreach ($data as $n => &$v) {
                $comp = new PathComponent($n);
                self::traverseImpl($callable, $v, $path->append($comp));
            }
        }
        if (!$this->preorder)
            $callable($data, $type, $path);
    }

    /**
     * @var mixed
     */
    private $data;
}
