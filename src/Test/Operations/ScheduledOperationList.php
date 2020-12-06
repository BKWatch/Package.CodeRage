<?php

/**
 * Defines the class CodeRage\Test\Operations\ScheduledOperationList
 *
 * File:        CodeRage/Test/Operations/ScheduledOperationList.php
 * Date:        Tue March 14 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use DOMDocument;
use DOMElement;
use Exception;
use Throwable;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Util\XmlEncoder;
use CodeRage\Xml;


/**
 * Represents a a collection of operations to be executed according to their
 * schedules
 */
class ScheduledOperationList extends OperationListBase {

    /**
     * Constructs an instance of CodeRage\Test\Operations\ScheduledOperationList
     *
     * @param string $description The operation list description
     * @param CodeRage\Util\Properties $properties The collection of properties
     * @param array $config An associative array of configuration variables
     * @param string $path The path to the XML description of this operation
     *   list, if any
     */
    protected function __construct($description, $properties, $config, $path)
    {
        parent::__construct($description, $properties, $config, $path);
    }

        /*
         * Accessor methods
         */

    /**
     * Returns the subset of the operations in the underlying collection that
     * have a non-repating schedule, in order of their scheduled execution
     *
     * @return array A list of instances of CodeRage\Test\Operations\Operation
     */
    public function operations()
    {
        return $this->operations;
    }

        /*
         * Operation execution methods
         */

    /**
     * Executes this operation list by invoking execute() on each underlying
     * operation in according to their schedules
     *
     * @return mixed
     */
    public function execute()
    {
        $prev = null;
        try {
            $prev = $this->installConfig();
            $executionPlan =
                new ExecutionPlan($this->description(), $this->operations);
            $action =
                function($plan, $step)
                {
                    return $this->executeStep($step, 'execute');
                };
            $executionPlan->execute($action);
        } finally {
            if ($prev !== null)
                Config::setCurrent($prev);
            $this->clearConfig();
        }
    }

    /**
     * Iterates over the operations in the underlying collection, according
     * to their schedule, invoking test() on each operation with a non-repating
     * schedule and execute() on each operation with a repeating schedule,
     * without propagating any exceptions thrown by execute()
     *
     * @throws CodeRage\Error
     */
    public function test()
    {
        $prev = null;
        try {
            $prev = $this->installConfig();
            $executionPlan =
                new ExecutionPlan($this->description(), $this->operations);
            $action =
                function($plan, $step)
                {
                    return $this->executeStep($step, 'test');
                };
            $executionPlan->execute($action);
        } finally {
            if ($prev !== null)
                Config::setCurrent($prev);
            $this->clearConfig();
        }
    }

    /**
     * Iterates over the operations in the underlying collection, according
     * to their schedule, invoking generate() on each operation with a
     * non-repating schedule and execute() on each operation with a repeating
     * schedule, without propagating any exceptions thrown by execute()
     */
    public function generate()
    {
        $prev = null;
        try {
            $prev = $this->installConfig();
            $executionPlan =
                new ExecutionPlan($this->description(), $this->operations);
            $action =
                function($plan, $step)
                {
                    return $this->executeStep($step, 'generate');
                };
            $executionPlan->execute($action);
            foreach ($this->operations as $i => $op)
                if ($i >= 0)
                    $op->normalize();
        } finally {
            if ($prev !== null)
                Config::setCurrent($prev);
            $this->clearConfig();
        }
    }

        /*
         * Load and save methods
         */

    /**
     * Returns an instance of CodeRage\Test\Operations\ScheduledOperationList
     * newly constructed from the specified file
     *
     * @param string $path The path to an XML document conforming to the schema
     *   "operation.xsd" and having document element "ScheduledOperationList"
     * @return CodeRage\Test\Operations\Operation
     */
    public static function load($path)
    {
        $dom = Xml::loadDocument($path, self::SCHEMA_PATH);
        $dom->xinclude();
        $elt = $dom->documentElement;
        if ($elt->localName != 'scheduledOperationList')
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' =>
                        "Failed parsing '$path': expected " .
                        "'scheduledOperationList' element; found '" .
                        $elt->localName . "'"
                ]);
        return self::loadXml($elt, $path);
    }

    /**
     * Creates an XML document from this operation list and saves it to the
     * given path
     *
     * @param string $path The path
     */
    public function save($path)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $dom->appendChild(
            $dom->createComment(
                "AUTOMATICALLY GENERATED BY CODERAGE TOOLS - DO NOT EDIT"
            )
        );
        $dom->appendChild($dom->createComment('Copyright CodeRage'));
        $dom->appendChild($this->saveXml($dom));
        $dom->loadXml($dom->saveXml(), LIBXML_NSCLEAN);  // Tidy namespaces
        $dom->save($path);
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\ScheduledOperationList
     * newly constructed from the specified XML element
     *
     * @param string $path An element with localName "ScheduledOperationList"
     *   conforming to the schema "operation.xsd"
     * @param string $baseUri The URI for resolving relative paths referenced by
     *   $elt
     * @return CodeRage\Test\Operations\ScheduledOperationList
     */
    public static function loadXml(DOMElement $elt, $baseUri)
    {
        $description = Xml::childContent($elt, 'description');
        $properties = self::loadProperties($elt);
        $config = self::loadConfig($elt);
        $path = $baseUri !== null ?
            $baseUri :
            Xml::documentPath($elt->ownerDocument);
        $opList =
            new ScheduledOperationList(
                    $description,
                    $properties,
                    $config,
                    $path
                );
        $operations = Xml::firstChildElement($elt, 'operations');
        $prevTime = null;
        $index = 0;
        foreach (Xml::childElements($operations, 'operation') as $child) {
            $op = Operation::loadXml($child, $baseUri, $opList, $index);
            $schedule = $op->schedule();
            if ($schedule === null)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'message' =>
                            "Missing schedule for operation at position $index"
                    ]);
            if ($schedule->time() === null)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'message' =>
                            "Expected operation with non-repeating schedule " .
                            "at position $index"
                    ]);
            if ($prevTime !== null && $schedule->time() <= $prevTime)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'message' =>
                            "Execution times of operations not in increasing " .
                            "order at positions " . ($index - 1) . ", $index"


                    ]);
            $prevTime = $schedule->time();
            $opList->operations[$index] = $op;
            ++$index;
        }
        $repeatingOperations = Xml::firstChildElement($elt, 'repeatingOperations');
        $index = -1;
        if ($repeatingOperations !== null) {
            foreach (Xml::childElements($repeatingOperations, 'operation') as $child) {
                $op = Operation::loadXml($child, $baseUri, $opList, $index);
                $schedule = $op->schedule();
                if ($schedule === null)
                    throw new
                        Error([
                            'status' => 'UNEXPECTED_CONTENT',
                            'message' =>
                                'Missing schedule for operation at position ' .
                                (-$index - 1)
                        ]);
                if ($schedule->time() !== null)
                    throw new
                        Error([
                            'status' => 'UNEXPECTED_CONTENT',
                            'message' =>
                                'Expected operation with repeating schedule ' .
                                'at position ' . (-$index - 1)
                        ]);
                $opList->operations[$index] = $op;
                --$index;
            }
        }
        return $opList;
    }

    /**
     * Returns an XML element representing this operation list
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @return DOMElement An XML element with localName "scheduledOperationList"
     */
    public function saveXml(DOMDocument $dom)
    {
        $ns = self::NAMESPACE_URI;
        $elt = XmlEncoder::createElement($dom, 'scheduledOperationList', $ns);

        // Handle description
        $this->appendElement($dom, $elt, 'description', $this->description());

        // Handle properties
        $properties = $this->createPropertiesElement($dom);
        if ($properties !== null)
            $elt->appendChild($properties);

        // Handle config
        $config = $this->createConfigElement($dom);
        if ($config !== null)
            $elt->appendChild($config);

        // Handle operations with schedules
        $operations = $dom->createElementNS($ns, 'operations');
        $repeatingOperations = null;
        foreach ($this->operations as $i => $op) {
            if ($i >= 0) {
                $operations->appendChild($op->saveXml($dom));
            } else {
                if ($repeatingOperations === null)
                    $repeatingOperations =
                        $dom->createElementNS($ns, 'repeatingOperations');
                $repeatingOperations->appendChild($op->saveXml($dom));
            }
        }
        $elt->appendChild($operations);
        if ($repeatingOperations !== null)
            $elt->appendChild($repeatingOperations);
        return $elt;
    }

    /**
     * Implements an action for use with
     * CodeRage\Test\Operations\ExecutionPlan::execute()
     *
     * @param CodeRage\Test\Operations\ExecutionPlanStep $step
     * @param string One of 'execute', 'test', or 'generate'
     */
    private function executeStep(ExecutionPlanStep $step, $method)
    {
        $op = $step->operation();
        $method = $op->schedule()->time() !== null ?
            $method :
            'execute';
        $this->logStep($step);
        try {
            $op->$method();
        } catch (\Throwable $e) {
            $this->logStep($step, $e);
            if ($method !== 'execute')
                throw $e;
        }
    }

    /**
     * Logs the given execution plan step, before or after execution
     *
     * @param CodeRage\Test\Operations\ExecutionPlanStep $step
     * @param Throwable $e The exception thrown by the operation, if any
     */
    private function logStep(ExecutionPlanStep $step, ?Throwable $e = null)
    {
        $op = $step->operation();
        $name = pathinfo($op->path(), PATHINFO_FILENAME);
        $timestamp = $step->time()->format(DATE_W3C);
        $message =
            "Scheduled Operation List '$name' [$timestamp]: ";
        if ($e === null) {
            $message .= "Executing '{$op->description()}'";
        } else {
            $e = Error::wrap($e);
            $message .= "Failed executing '{$op->description()}': $e";
        }
        \CodeRage\Log::current()->logMessage($message);
    }

    /**
     * The list of instances of CodeRage\Test\Operations\Operation with
     * non-repeating schedules
     *
     * @var array
     */
    private $operations = [];
}
