<?php

/**
 * Defines the class CodeRage\Test\TraversalBase
 * 
 * File:        CodeRage/Test/TraversalBase.php
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
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\XmlEncoder;


/**
 * Base class for CodeRage\Test\Traversal and CodeRage\Test\BiTraversal
 */
abstract class TraversalBase {

    /**
     * constructs a CodeRage\Test\Traversal
     *
     * @param array $options The options array; supports the following options:
     *     nativeDataEncoder - An instance of CodeRage\Util\NativeDataEncoder
     *       (optional)
     *     path - A path expression, as a string or instance of
     *       CodeRage\Test\PathExpr (optional)
     *     preorder - true to visit parent nodes before their children; defaults
     *       to true
     */
    protected function __construct(array $options = [])
    {
        $path = Args::checkKey($options, 'path', 'CodeRage\Test\PathExpr');
        if ($path === null)
            $path = '/';
        if (is_string($path))
            $path = PathExpr::parse($path);
        $xmlEncoder =
            Args::checkKey($options, 'xmlEncoder', 'CodeRage\Util\XmlEncoder', [
                'label' => 'XML encoder'
            ]);
        $preorder =
            Args::checkKey($options, 'preorder', 'boolean', [
                'label' => 'preorder flag',
                'default' => true
            ]);
        $this->path = $path;
        $this->itemNames = $xmlEncoder !== null ?
            $xmlEncoder->listElements() :
            ['*' => 'item'];
        $this->preorder = $preorder;
    }

    /**
     * Returns the name to use to address the child of a list node based on the
     * name used to address the parent node
     *
     * @param CodeRage\Test\PathExpr $path The path to the parent node
     * @return string
     */
    protected final function itemName(PathExpr $path)
    {
        $parentName = $path->length() > 0 ?
            $path->last()->name() :
            '*';
        $itemName = isset($this->itemNames[$parentName]) ?
            $this->itemNames[$parentName] :
            ( isset($this->itemNames['*']) ?
                  $this->itemNames['*'] :
                  null );
        if ($itemName === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' =>
                        "Failed traversing data structure: found list node " .
                        "at $path with unsupported name: $parentName"
                ]);
        return $itemName;
    }

    /**
     * Returns the type of the given node
     *
     * @param mixed $node
     * @param mixed $other An optional second node used to help resolve the
     *   ambiguous case of an empty array, which could be either a list or a map
     * @return string One of the strings 'scalar', 'list', 'object', or 'empty'
     *   or the result of calling gettype(), if $node is not null, a scalar, an
     *   array, or an instance of stdClass
     */
    protected static function getType($node, $other = null)
    {
        if (is_scalar($node) || is_null($node)) {
            return 'scalar';
        } elseif (is_array($node)) {
            if (empty($node) && is_array($other)) {
                return Array_::isIndexed($other) && !empty($other) ? 'list' : 'object';
            } else {
                return Array_::isIndexed($node) && !empty($node) ? 'list' : 'object';
            }
        } elseif ($node instanceof \stdClass) {
            return 'object';
        } else {
            return \gettype($node);
        }
    }

    /**
     * An associative array mapping names used to address list nodes to the
     * names used to address their child nodes
     *
     * @array
     */
    protected $itemNames;

    /**
     * @var CodeRage\Test\PathExpr
     */
    protected $path;

    /**
     * @var boolean
     */
    protected $preorder;
}
