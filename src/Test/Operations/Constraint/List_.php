<?php

/**
 * Defines the class CodeRage\Test\Operations\Constraint\List_
 *
 * File:        CodeRage/Test/Operations/Constraint/List_.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations\Constraint;

use CodeRage\Error;
use CodeRage\Test\Operations\AbstractOperation;
use CodeRage\Test\Operations\Constraint;
use CodeRage\Test\PathExpr;
use CodeRage\Util\XmlEncoder;
use CodeRage\Xml;


/**
 * Represents a constraint on a list of items
 */
final class List_ extends Constraint {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Constraint\List_
     *
     * @param int $minItems The minimum number of items in the list
     * @param int $maxItems The maximum number of items in the list
     * @param CodeRage\Test\Operations\PathExpr $address The path expression
     *   restricting the values to which the pattern applies
     */
    private function __construct(PathExpr $address, ?int $minItems, ?int $maxItems)
    {
        if ($minItems !== null && $minItems < 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid minItems: $minItems"
                ]);
        if ($maxItems !== null && $maxItems < 1)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid maxItems: $maxItems"
                ]);
        if ($minItems !== null && $maxItems !== null && $maxItems < $minItems)
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' => "maxItems is less than minItems"
                ]);
        parent::__construct($address, 'list');
        $this->minItems = $minItems;
        $this->maxItems = $maxItems;
    }

    /**
     * Returns the minimum number of allow items, if any
     *
     * @return string
     */
    public function minItems() : ?int { return $this->minItems; }

    /**
     * Returns the maximum number of allow items, if any
     *
     * @return string
     */
    public function maxItems() : ?int { return $this->maxItems; }

    /**
     * Returns true if the length of the given list falls within the underlying
     * bounds
     *
     * @param string $value The string to be tested
     */
    public function matches($value) : bool
    {
        $count = count($value);
        return ($this->minItems === null || $count >= $this->minItems) ||
               ($this->maxItems === null || $count <= $this->maxItems);
    }

    /**
     * Replaces the given list
     *
     * @param mixed $data
     */
    public function replace(&$data) : void
    {
        array_splice($data, $this->minItems);
    }

    public static function load(\DOMElement $elt, XmlEncoder $encoder,
        PathExpr $prefix) : Constraint
    {
        $items = [];
        foreach (['minItems', 'maxItems'] as $name) {
            $value = Xml::getAttribute($elt, $name);
            if ($value !== null) {
                if (!ctype_digit($value))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => "Invalid $name: $value"
                        ]);
                $value = (int) $value;
            }
            $items[$name] = $value;
        }
        $address = PathExpr::parse($elt->getAttribute('address'));
        if ($address->isAbsolute())
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Expected relative path expression; found ' .
                        $address
                ]);
        $address = $prefix->append($address);
        return new self($address, $items['minItems'], $items['maxItems']);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $list = $dom->createElementNS(self::NAMESPACE_URI, 'list');
        $address =
            $this->address()->suffix(
                $this->address()->length() - ($parent !== null ? 1 : 0)
            );
        $list->setAttribute('address', $address);
        if ($this->minItems !== null)
            $list->setAttribute('minItems', $this->minItems);
        if ($this->maxItems !== null)
            $list->setAttribute('maxItems', $this->maxItems);
        return $list;
    }

    /**
     * @var int
     */
    private $minItems;

    /**
     * @var int
     */
    private $maxItems;
}
