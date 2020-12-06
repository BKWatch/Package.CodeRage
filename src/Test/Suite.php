<?php

/**
 * Contains the definition of the class CodeRage\Test\Suite, representing a
 * hierarchical collection of test cases
 *
 * File:        CodeRage/Test/Suite.php
 * Date:        Wed Mar 14 06:38:17 MDT 2007
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use Throwable;
use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Represents a hierarchical collection of test cases
 */
class Suite extends Component {

    /**
     * the target namespace of the schema testSuite.xsd
     *
     * @var string
     */
    const NAMESPACE_URI = 'http://www.coderage.com/2007/testsuite';

    /**
     * constructs am empty CodeRage\Test\Suite with the given name and description
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     * @param array $components An optional list of instances of
     * CodeRage\Test\Component
     */
    public function __construct($name, $description, $components = [])
    {
        parent::__construct($name, $description);
        foreach($components as $c)
            $this->add($c);
    }

    /**
     * Adds a component to this suite's collection of components
     *
     * @param CodeRage\Test\Component $component
     */
    public final function add(Component $component)
    {
        $this->components[] = $component;
    }

    /**
     * Returns the underlying list of components
     *
     * @return array A list of instance of CodeRage\Test\Component
     */
    public final function components()
    {
        return $this->components;
    }

    /**
     * Called at the beginning of the implementation of execute() before any
     * components are executed.
     */
    protected function suiteInitialize() { }

    /**
     * Called at the end of the implementation of execute() after all
     * components are executed.
     */
    protected function suiteCleanup() { }

    /**
     * Called before each component is executed
     *
     * @param CodeRage\Test\Component $component The component to be executed
     */
    protected function componentInitialize($component) { }

    /**
     * Called after each component is executed
     *
     * @param CodeRage\Test\Component $component The component that has been executed
     */
    protected function componentCleanup($component) { }

    /**
     * Returns TYPE_SUITE
     *
     * @return int
     */
    protected final function doType() { return self::TYPE_SUITE; }

    /**
     * Executes this suite's sequence of test components, in order, and writes
     * an XML report to standard output. Does not throw exceptions.
     *
     * @param array $params an associate array of parameters. The following
     * values are supported
     *
     * <ul>
     *   <li> timeout: The number of miliseconds to wait before aborting;
     *        defaults to CodeRage\Test\Component::DEFAULT_TIMEOUT
     *   <li> error-level: A value suitable for passing to error_reporting().
     *   <li> cwd: the working directory, for test cases or suites that
     *        run external commands.
     * </ul>
     *
     * @return boolean
     */
    protected final function doExecute($params)
    {
        $result = true;

        // Start building the document
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $testSuite = $dom->createElement('testSuite');
        $testSuite->setAttribute('name', $this->name());
        $testSuite->setAttribute('xmlns', self::NAMESPACE_URI);
        $testSuite->setAttribute('begin', date(DATE_W3C));
        $testSuite->appendChild(
            $dom->createElement('description', $this->description())
        );
        $dom->appendChild($testSuite);

        // Construct component executor
        $executor =
            new ComponentExecutor(
                    $params,
                    [$this, 'componentInitializeImpl'],
                    [$this, 'componentCleanupImpl'],
                    $dom
                );

        // Initialize suite
        try {
            $this->suiteInitialize();
        } catch (Throwable $e) {
            $inner = Error::wrap($e);
            $error =
                new Error([
                        'message' => 'Failed initializing suite',
                        'inner' => $inner
                    ]);
            $aborted = $dom->createElement('aborted');
            $aborted->appendChild($executor->createExceptionElement($error));
            $testSuite->appendChild($aborted);
            print $dom->saveXML();
            return false;
        }

        // Execute each sub component
        foreach ($this->components as $component) {
            list($status, $element) = $executor->execute($component);
            $result = $result && $status;
            $testSuite->appendChild($element);
        }

        // Add ending timestamp
        $testSuite->setAttribute('end', date(DATE_W3C));

        // Clean up suite
        try {
            $this->suiteCleanup();
        } catch (Throwable $e) {
            $inner = Error::wrap($e);
            $error =
                new Error([
                        'message' => 'Failed cleaning up suite',
                        'inner' => $inner
                    ]);
            $exception = $executor->createExceptionElement($error);
            $name = $this->name();
            $case =
                $executor->createCaseElement(
                    "$name Cleanup",
                    "Cleanup for test suite $name",
                    null,
                    null,
                    '',
                    false,
                    [$exception]
                );
            $testSuite->appendChild($case);
        }

        // Output XML
        print $dom->saveXML();

        return $result;
    }
    /**
     * Public wrapper for componentInitialize(); would be private if PHP
     * access control allowed it
     *
     * @param CodeRage\Test\Component $component
     */
    public function componentInitializeImpl($component)
    {
        $this->componentInitialize($component);
    }

    /**
     * Public wrapper for componentCleanup(); would be private if PHP
     * access control allowed it
     *
     * @param CodeRage\Test\Component $component
     */
    public function componentCleanupImpl($component)
    {
        $this->componentCleanup($component);
    }

    private $components = [];
}
