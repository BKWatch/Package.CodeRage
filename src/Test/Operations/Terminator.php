<?php

/**
 * Defines the class CodeRage\Test\Operations\Terminator
 *
 * File:        CodeRage/Test/Operations/Terminator.php
 * Date:        Thu Jul 11 15:50:40 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Error;
use CodeRage\Test\PathExpr;
use CodeRage\Test\Traversal;
use CodeRage\Util\XmlEncoder;
use CodeRage\Xml;


/**
 * Checks operation output and exceptions to determine whether a test case
 * should be terminated early
 */
final class Terminator extends Base {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Terminator
     *
     * @param CodeRage\Util\XmlEncoder $xmlEncoder An XML encoder
     * @param boolean $success Indicates whether a terminated test case should
     *   pass or fail
     * @param string $reason The reason for early termination
     * @param string $conditions A list of instances of
     *   CodeRage\Test\Operations\Constraint
     */
    public function __construct($xmlEncoder, $success, $reason, $conditions)
    {
        foreach ($conditions as $constraint) {
            if ($constraint->type() == 'scalar') {
                if ($constraint->replacement() !== null)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Replacement text is not supported in " .
                                "terminator conditions"
                        ]);
                foreach ($constraint->address()->components() as $c)
                    if ($c->isWildcard())
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' =>
                                    "Wildcards are not supported in " .
                                    "terminator conditions"
                            ]);
            }
        }
        $listElements =
            $xmlEncoder->listElements() +
            [ 'operations' => 'operation',
              'input' => 'arg' ];
        $this->xmlEncoder = new XmlEncoder(['listElements' =>$listElements]);
        $this->success = $success;
        $this->reason = $reason;
        $this->conditions = $conditions;
    }

    /**
     * Returns a boolean indicating whether a terminated test case should pass
     * or fail
     *
     * @return array
     */
    public function success()
    {
        return $this->success;
    }

    /**
     * Returns the reason for early termination
     *
     * @return string
     */
    public function reason()
    {
        return $this->reason;
    }

    /**
     * Returns the underlying list of instances of
     * CodeRage\Test\Operations\Constraint
     *
     * @return array
     */
    public function conditions()
    {
        return $this->conditions;
    }

    /**
     * Throws an exception if all of the underlying conditions match the given
     * data structure at the given location
     *
     * @param mixed $operation An operation, represents as a native data
     *   structure, with properties among "input", "output", and "exception"
     * @param CodeRage\Test\PathExpr $path The path to $operation within its
     *   operation list
     * @throws CodeRage\Error
     */
    public function check($data, PathExpr $path)
    {
        $traversal =
            new Traversal($data, [
                    'xmlEncoder' => $this->xmlEncoder,
                    'path' => $path
                ]);
        $matches = 0;
        $traversal->traverse(function(&$data, $path) use (&$matches) {
            if (!is_scalar($data))
                return;
            foreach ($this->conditions() as $c)
                if ($c->matches($data, $path))
                    ++$matches;
        });
        if ($matches == count($this->conditions()))
            throw new TerminatorException($this, $path);
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\Terminator newly
     * constructed from the given "terminator" element
     *
     * @param DOMElement $elt An element with localName "termination"
     *   conforming to the schema "operation.xsd"
     * @param CodeRage\Util\XmlEncoder $encoder The XML encoder
     * @param int $index The index of the operation under construction within
     *   its parent's list of child operations, if any
     * @return CodeRage\Test\Operations\Terminator
     */
    public static function load(\DOMElement $elt, XmlEncoder $encoder, $index)
    {
        $success = Xml::getBooleanAttribute($elt, 'success');
        $reason = Xml::getAttribute($elt, 'reason');
        $prefix =
            PathExpr::parse(
                $index !== null ?
                    "/operation[$index]" :
                    "/"
            );
        $conditions = [];
        foreach (Xml::childElements($elt) as $k)
            $conditions[] = Constraint::load($k, $encoder, $prefix);
        return new self($encoder, $success, $reason, $conditions);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $$elt = $dom->createElementNS(self::NAMESPACE_URI, 'termination');
        $elt->setAttribute('success', $this->success() ? 'true' : false);
        $elt->setAttribute('reason', $this->reason());
        foreach ($this->conditions() as $c)
            $elt->appendChild($c->save($dom, $parent));
        return $elt;
    }

    /**
     * @var CodeRage\Util\XmlEncoder
     */
    private $xmlEncoder;

    /**
     * @var boolean
     */
    private $success;

    /**
     * @var string
     */
    private $reason;

    /**
     * A list of instances of CodeRage\Test\Operation\Pattern
     *
     * @var string
     */
    private $conditions;
}
