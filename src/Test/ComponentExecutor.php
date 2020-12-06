<?php

/**
 * Defines the class CodeRage\Test\ComponentExecutor
 *
 * File:        CodeRage/Test/ComponentExecutor.php
 * Date:        Sun Jul 15 00:28:53 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Util\ErrorHandler;
use CodeRage\Util\Time;
use CodeRage\Xml;


/**
 * Executes a component and returns an XML element
 */
class ComponentExecutor {

    /**
     * The target namespace of the schema testSuite.xsd
     *
     * @var string
     */
    const NAMESPACE_URI = 'http://www.coderage.com/2007/testsuite';

    /**
     * Constructs an instance of CodeRage\Test\ComponentExecutor
     *
     * @param array $params The associative array of parameters to be passed to
     *   CodeRage\Test\Component::execute()
     * @param callable $componentInitialize The callable, if any, that is
     *   invoked before the main work of the component is performed
     * @param callable $componentCleanup The callable, if any, that is
     *   invoked after the main work of the component is performed
     * @param DOMDocument $doc The instance of DOMDocument used to create new
     *   elements, text nodes, and CDATA sections
     */
    public function __construct(
        $params, $componentInitialize, $componentCleanup, $doc)
    {
        $this->params = $params;
        $this->componentInitialize = $componentInitialize;
        $this->componentCleanup = $componentCleanup;
        $this->doc = $doc;
        $this->errorLevel =
            $this->params['error-level'] =
                isset($params['error-level']) ?
                    $params['error-level'] | E_ERROR | E_USER_ERROR :
                    E_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR |
                    E_WARNING | E_USER_WARNING;
    }

    /**
     * Executes the given component and returns a pair ($status, $element)
     * where $status is a boolean and $element is an instance of DOMElement
     *
     * @param CodeRage\Test\Component $component
     * @return array
     */
    public function execute($component)
    {
        // Track execution status
        $element = $status = null;
        $cleanedUp = false;
        $failures = [];

        // Register error and assertion handlers
        $level = defined('E_DEPRECATED') ?
            E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED :
            E_ALL;
        ErrorHandler::register($level);
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_CALLBACK, [$this, 'assertHandler']);

        // Capture output
        ob_start();

        // Start timer
        $begin = Time::real();

        try {
            if ($this->componentInitialize)
                ($this->componentInitialize)($component);
            $status = $component->execute($this->params) !== false;
            foreach ($this->errors as $e)
                if ($e['errno'] & $this->errorLevel)
                    $status = false;
        } catch (SuitePhpError $e) {
            $status = false;
            try {
                if ($this->componentCleanup)
                    ($this->componentCleanup)($component);
                $cleanedUp = true;
            } catch (Throwable $e) {
                $failures[] = $this->createExceptionElement($e);
            }
        } catch (Throwable $e) {
            $status = false;
            $failures[] = $this->createExceptionElement($e);
            try {
                if ($this->componentCleanup)
                    ($this->componentCleanup)($component);
                $cleanedUp = true;
            } catch (Throwable $e) {
                $failures[] = $this->createExceptionElement($e);
            }
        }
        if (!$cleanedUp) {
            try {
                if ($this->componentCleanup)
                    ($this->componentCleanup)($component);
            } catch (Throwable $e) {
                $status = false;
                $failures[] = $this->createExceptionElement($e);
            }
        }

        // Stop timer
        $end = Time::real();

        // Restore output redirection
        $output = ob_get_contents();
        ob_end_clean();

        // Restore error handler
        restore_error_handler();

        // Collect errors
        foreach ($this->errors as $e) {
            $failures[] =
                $this->createErrorElement(
                    $e['errno'],
                    $e['message'],
                    $e['trace']
                );
        }
        $this->errors = [];

        // Construct XML element
        if ($component->type() == Component::TYPE_CASE) {
            if ($component->isTimedOut()) {
                $elapsed = $this->doc->createElement('timeout');
                $elapsed->setAttribute(
                    'elapsed',
                    $component->getElapsed()
                );
                $failures[] = $elapsed;
            }
            $element =
                $this->createCaseElement(
                    $component->name(),
                    $component->description(),
                    $begin,
                    $end,
                    $output,
                    $status,
                    $failures
                );
        } else {
            $schema = Suite::SCHEMA_PATH;
            try {
                $doc = Xml::loadDocumentXml($output, $schema);
                $suite = $doc->documentElement;
                $name = $suite->localName;
                if ($name != 'testSuite')
                    throw new
                        Error([
                            'status' => 'UNEXPECTED_CONTENT',
                            'message' =>
                                "Expected document element " .
                                "'testSuite': found $suite->localName"
                        ]);
                $element = $this->doc->importNode($suite, true);
            } catch (Throwable $e) {
                $status = false;
                $failures[] = $this->createExceptionElement($e);
                if ($component->isTimedOut()) {
                    $timeout = $this->doc->createElement('timeout');
                    $timeout->setAttribute(
                        'elapsed',
                        $component->getElapsed()
                    );
                    $failures[] = $timeout;
                }
                $element =
                    $this->createSuiteElement(
                        $component->name(),
                        $component->description(),
                        $begin,
                        $end,
                        $output,
                        $status,
                        $failures
                    );
            }
        }

        return [$status, $element];
    }

    /**
     * Builds a testSuite element.
     *
     * @param string $name The name of the test suite
     * @param string $description The description of the test suite
     * @param string $begin A UNIX timestamp
     * @param string $end A UNIX timestamp
     * @param string $output The combined output of standard output and standard
     *   error
     * @param boolean $success true if the test passed
     * @param array $failure An array of zero or more instances of DOMElement
     *   with local names "timeout", "exception", "signal", and "error"
     * @return DOMElement The testSuite element
     */
    public function createSuiteElement(
        $name, $description, $begin, $end, $output, $success,
        $failures = [])
    {
        // Construct suite element, with description
        $suite = $this->doc->createElement('testSuite');
        $suite->setAttribute('name', $name);
        $suite->setAttribute('xmlns', self::NAMESPACE_URI);
        if (isset($begin))
            $suite->setAttribute('begin', date(DATE_W3C, $begin));
        if (isset($end))
            $suite->setAttribute('end', date(DATE_W3C, $end));
        $suite->appendChild($this->createElement('description', $description));
        if (!$success) {

            // Construct and append aborted element
            $aborted =
                $suite->appendChild($this->doc->createElement('aborted'));

            // Append exception and errors
            foreach ($failures as $f)
                $aborted->appendChild($f);

            // Construct and append output element
            $aborted->appendChild($this->createOutputElement($output));
        }

        return $suite;
    }

    /**
     * Builds a testCase element.
     *
     * @param string $name The name of the test case
     * @param string $description The description of the test case
     * @param int $description The description of the test case
     * @param string $begin A UNIX timestamp
     * @param string $end A UNIX timestamp
     * @param string $output The combined output of standard output and standard
     *   error.
     * @param boolean $success true if the test passed
     * @param array $failure An array of zero or more instances of DOMElement
     *   with local names "timeout", "exception", "signal", and "error"
     * @return DOMElement The testCase element
     */
    public function createCaseElement(
        $name, $description, $begin, $end, $output, $success,
        $failures = [])
    {
        // Construct case element, with description
        $case = $this->doc->createElement('testCase');
        $case->setAttribute('name', $name);
        $case->setAttribute('xmlns', self::NAMESPACE_URI);
        if (isset($begin))
            $case->setAttribute('begin', date(DATE_W3C, $begin));
        if (isset($end))
            $case->setAttribute('end', date(DATE_W3C, $end));
        $case->appendChild($this->createElement('description', $description));

        // Construct and append status element
        $status = $this->doc->createElement('status');
        $status->setAttribute('success', $success ? 'true' : 'false');
        foreach ($failures as $f)
            $status->appendChild($f);
        $case->appendChild($status);

        // Construct and append output element
        $case->appendChild($this->createOutputElement($output));

        return $case;
    }

    /**
     * Returns an exception element constructed from the given exception
     *
     * @param Throwable $e The exception
     * @return DOMElement the exception element
     */
    public function createExceptionElement(Throwable $e)
    {
        $exception = $this->doc->createElement('exception');
        $exception->setAttribute('class', get_class($e));
        $message = $this->doc->createElement('message');
        $text =  $e instanceof Error ?
            $e->details() :
            $e->getMessage();
        $textNode = strpos($text, '</') !== false ?
            $this->doc->createCDataSection($text) :
            $this->doc->createTextNode($text);
        $message->appendChild($textNode);
        $exception->appendChild($message);
        if ($e instanceof Error) {
            $status = $this->createElement('status', $e->status());
            $exception->appendChild($status);
        }
        if (sizeof($e->getTrace()) > 0) {
            $trace = $e->getTrace();
            array_unshift(
                $trace,
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );
            $exception->appendChild(
                $this->createStackTraceElement($trace)
            );
        }
        if (method_exists($e, 'getPrevious') && ($inner = $e->getPrevious())) {
            $exception->appendChild($this->createExceptionElement($inner));
        } elseif (method_exists($e, 'inner') && ($inner = $e->inner())) {
            $exception->appendChild($this->createExceptionElement($inner));
        }
        return $exception;
    }

    /**
     * Returns an error element based on the given information
     *
     * @param int $errno One of the constants E_XXX, or null for assertion
     *   failures
     * @param string $message The error message
     * @param array $trace A stack trace
     * @return DOMElement The error element
     */
    private function createErrorElement($errno, $message, $trace)
    {
        // Construct error element
        $error = $this->doc->createElement('error');
        $type = $errno ?
            ErrorHandler::errorCategory($errno) :
            'assertion';
        $error->setAttribute(
            'type',
            $type ? strtolower($type) : 'unknown'
        );

        // Construct and append message element
        $messageElt = $this->doc->createElement('message');
        $content = strpbrk($message, '<>&%') !== false ?
            $this->doc->createCDATASection($message) :
            $this->doc->createTextNode($message);
        $messageElt->appendChild($content);
        $error->appendChild($messageElt);

        // Construct and append trace element
        if (sizeof($trace) > 0)
            $error->appendChild($this->createStackTraceElement($trace));

        return $error;
    }

    /**
     * Constructs an output element based on the given test output
     *
     * @param string $output The unformatted output
     * @return DOMElement the output element
     */
    private function createOutputElement($output)
    {
        $result = null;
        if (preg_match('/^[[:print:][:space:]]*$/', $output)) {
            $result = $this->doc->createElement('output');
            if ($output)
                $result->appendChild($this->doc->createCDATASection($output));
        } else {
            $result =
                $this->doc->createElement('output', base64_encode($output));
            $result->setAttribute('encoding', 'base64');
        }
        return $result;
    }

    /**
     * Builds a DOMElement representing a stack trace
     *
     * @param array $trace An array returned by debug_backtrace()
     * @return DOMElement The stack trace element
     */
    private function createStackTraceElement($trace)
    {
        $result = $this->doc->createElement('stackTrace');
        foreach (array_reverse($trace) as $t) {
            $frame = $this->doc->createElement('frame');
            if (isset($t['file']))
                $frame->setAttribute('file', $t['file']);
            if (isset($t['line']))
                $frame->setAttribute('line', $t['line']);
            if (isset($t['function']))
                $frame->setAttribute('function', $t['function']);
            if (isset($t['class']))
                $frame->setAttribute('class', $t['class']);
            $result->appendChild($frame);
        }
        return $result;
    }

    /**
     * Returns an element with the given name and textual content
     *
     * @param string $name The element name
     * @param string $content The textual content
     * @return DOMElement
     */
    private function createElement($name, $content)
    {
        $elt = $this->doc->createElement($name);
        $elt->appendChild($this->doc->createTextNode($content));
        return $elt;
    }

    /**
     * Intercepts and records errors that occur during the execution
     * of the test suite's cases and subsuites. This allows the suite execute
     * call to build a complete report in more circumstances and provides
     * more information in the report about case failures.
     *
     * @see http://us2.php.net/manual/en/function.set-error-handler.php
     *
     * @param int $errno One of the constants E_XXX
     * @param string $errstr The error message
     * @param string $errfile The file pathname
     * @param int $errline The line number
     * @return boolean true if the error has been handled by this function.
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (($errno & $this->errorLevel) != 0) {
            $category = ErrorHandler::errorCategory($errno);
            echo "PHP $category: $errstr in $errfile on line $errline\n";
            $this->errors[] =
                [
                    'errno' => $errno,
                    'message' => $errstr,
                    'trace' => debug_backtrace()
                ];
            throw new
                SuitePhpError($errno, $errstr, $errfile, $errline);
        }
        return true;
    }

    /**
     * Intercepts and records assertn failures that occur during the execution
     * of the test suite's cases and subsuites. This allows the suite execute
     * call to build a complete report in more circumstances and provides
     * more information in the report about case failures.
     *
     * @see http://us3.php.net/manual/en/function.assert.php
     *
     * @param string $file The file pathname
     * @param int $line The line number
     * @param string $code The code that evaluated to false
     */
    public function assertHandler($file, $line, $code)
    {
        $message = "Assertion \"$code\" failed in $file on line $line";
        $this->errors[] =
            [
                'errno' => null,
                'message' => $message,
                'trace' => debug_backtrace()
            ];
    }

    /**
     * The associative array of parameters to be passed to
     * CodeRage\Test\Component::execute()
     *
     * @var mixed
     */
    private $params;

    /**
     * The callable, if any, that is invoked before the main work of the
     * component is performed
     *
     * @var mixed
     */
    private $componentInitialize;

    /**
     * The callable, if any, that is invoked after the main work of the
     * component is performed
     *
     * @var mixed
     */
    private $componentCleanup;

    /**
     * The instance of DOMDocument used to create new elements, text nodes, and
     * CDATA sections
     *
     * @var int
     */
    private $doc;

    /**
     * A bitwise OR of zero or more of the constants E_XXX
     *
     * @var int
     */
    private $errorLevel;

    /**
     * A list of associative arrays with keys 'errno', 'message', and 'trace'
     *
     * @var array
     */
    private $errors = [];
}
