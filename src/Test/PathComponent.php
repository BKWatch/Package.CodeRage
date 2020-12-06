<?php

/**
 * Defines the class CodeRage\Test\PathComponent
 * 
 * File:        CodeRage/Test/PathComponent.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use stdClass;
use CodeRage\Error;
use CodeRage\Util\XmlEncoder;


/**
 * Represents a component in a path expression
 */
final class PathComponent {

    /**
     * Constructs an instance of CodeRage\Test\PathComponent
     *
     * @param string $name The name of the property to which the component under
     *   construction points
     * @param boolean $isListItem true if the component under construction
     *   points to a list item
     * @param int $index The position of the list item in the list to which the
     *   path under construction points, if $isListItem is true and the
     *   component is not a wildcard item
     */
    public function __construct($name, $isListItem = false, $index = null)
    {
        if (!$isListItem && $index !== null)
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "Path components that do not represent list items " .
                        "may not have indices"
                ]);
        $this->name = $name;
        $this->isListItem = $isListItem;
        $this->index = $index;
    }

    /**
     * Returns the name of the property to which this component points
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns true if this component points to a list item
     *
     * @return boolean
     */
    public function isListItem()
    {
        return $this->isListItem;
    }

    /**
     * Returns the position of the list item in the list to which this component
     * points, if isListItem() returns true and this component is not a wildcard
     * item
     *
     * @return boolean
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * Returns true if this component represents a wildcard list item
     *
     * @return boolean
     */
    public function isWildcard()
    {
        return $this->isListItem && $this->index === null;
    }

    public function __toString()
    {
        return PathExpr::encode($this->name) .
               ( $this->isListItem ?
                     '[' . ($this->index !== null ? $this->index : '*') . ']' :
                     '' );
    }

    /**
     * The name of the property to which this component points, if $isListItem
     * is false, and null otherwise
     *
     * @var string
     */
    private $name;

    /**
     * true if this component points to a list item
     *
     * @var boolean
     */
    private $isListItem;

    /**
     * The position of the list item in the list to which this component points,
     * if isListItem() returns true and this component is not a wildcard item
     *
     * @var int
     */
    private $index;
}
