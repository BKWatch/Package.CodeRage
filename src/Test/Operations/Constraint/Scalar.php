<?php

/**
 * Defines the class CodeRage\Test\Operations\Constraint\Scalar
 *
 * File:        CodeRage/Test/Operations/Constraint/Scalar.php
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
 * Represents a regular expression together with a path expression. Patterns
 * are used to specify that the certain components of an operation's input,
 * output or exception don't need to exactly match the exepcted input, output or
 * exception.
 */
final class Scalar extends Constraint {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Constraint\Scalar
     *
     * @param CodeRage\Test\Operations\PathExpr $address The path expression
     *   restricting the values to which the pattern applies
     * @param string $text A regular expression
     * @param string $flags A sequence of modifiers; supported values are 'i',
     *   'm', 's', and 'x'
     * @param string $replacement Text to replace values that match the pattern
     */
    private function __construct(PathExpr $address, string $text, string $flags,
        ?string $replacement = null)
    {
        if (preg_match('/([^imsx])/', $flags, $match))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid regular expression modifier: {$match[1]}"
                ]);
        parent::__construct($address, 'scalar');
        $this->text = $text;
        $this->flags = $flags;
        $this->replacement = $replacement;
        $this->regex = '#^(' . addcslashes($text, '#') . ')$#' . $flags;
    }

    /**
     * Returns the regular expression
     *
     * @return string
     */
    public function text() : string { return $this->text; }

    /**
     * Returns the sequence of modifiers; supported values are 'i', 'm', 's',
     * and 'x'
     *
     * @return string
     */
    public function flags() : string { return $this->flags; }

    /**
     * Returns true if the underlying regular expression matches the given
     * scalar
     *
     * @param scalar $data The string to be tested
     */
    public function matches($data) : bool
    {
        return preg_match($this->regex, $data) > 0;
    }

    /**
     * Returns the replacement text, if any
     *
     * @return string
     */
    public function replacement() : ?string
    {
        return $this->replacement;
    }

    /**
     * Replaces the data scalar with the underlying replacement string, if it
     * is non-null
     *
     * @param mixed $data
     */
    public function replace(&$data) : void
    {
        if  ($this->replacement !== null)
            $data = $this->replacement;
    }

    public static function load(\DOMElement $elt, XmlEncoder $encoder,
        PathExpr $prefix) : Constraint
    {
        $text = $elt->getAttribute('text');
        $flags = $elt->getAttribute('flags');
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
        $replacement = Xml::getAttribute($elt, 'replacement');
        return new self($address, $text, $flags, $replacement);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $scalar = $dom->createElementNS(self::NAMESPACE_URI, 'pattern');
        $scalar->setAttribute('text', $this->text);
        if (($flags = $this->flags) != '')
            $scalar->setAttribute('flags', $flags);
        $address =
            $this->address()->suffix(
                $this->address()->length() - ($parent !== null ? 1 : 0)
            );
        $scalar->setAttribute('address', $address);
        if (($replacement = $this->replacement) !== null)
            $scalar->setAttribute('replacement', $replacement);
        return $scalar;
    }

    /**
     * The string passed to preg_match
     *
     * @var string
     */
    private $regex;

    /**
     * The regular expression
     *
     * @var string
     */
    private $text;

    /**
     * The sequence of modifiers; supported values are 'i', 'm', 's', and 'x'
     *
     * @var string
     */
    private $flags;

    /**
     * Text to replace values that match the pattern
     *
     * @var string
     */
    private $replacement;
}
