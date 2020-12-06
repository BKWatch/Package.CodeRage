<?php

/**
 * Defines the class CodeRage\Test\ReflectionCase.
 *
 * File:        CodeRage/Test/ReflectionCase.php
 * Date:        Mon Feb 02 13:37:02 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use Exception;
use Throwable;
use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Test case whose doExexcute() method invokes a method specified at
 * construction.
 */
class ReflectionCase extends Case_ {

    /**
     * The object whose method is to be invoked.
     *
     * @var object
     */
    private $object;

    /**
     * The name of the method to be invoked.
     *
     * @var string
     */
    private $method;

    /**
     * true if this case should be stripped from test reports, if
     * successful.
     *
     * @var string
     */
    private $hidden;

    /**
     * Constructs a CodeRage\Test\ReflectionCase.
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     * @param object The object whose method is to be invoked.
     * @param string $method The name of the method to be invoked.
     */
    public function __construct($name, $description, $object, $method)
    {
        parent::__construct($name, $description);
        $this->object = $object;
        $this->method = $method;
    }

    /**
     * Invokes the 'beforeCase' method of the underlying object, if it exists,
     * followed by the method specified at construction, followed by the
     * 'afterCase' method.
     *
     * @param array $params an associate array of parameters.
     *
     * @return boolean true for success
     * @throws Exception if an error occurs
     */
    protected final function doExecute($params)
    {
        // Call beforeCase()
        if (method_exists($this->object, 'beforeCase')) {
            try {
                $this->object->beforeCase();
            } catch (Throwable $e) {
                throw new Exception("Failed preparing case: $e");
            }
        }

        // Invoke method
        $result = null;
        $exception = null;
        try {
            $this->object->setExpectedException(null);
            $this->object->setExpectedStatusCode(null);
            $method = $this->method;
            $result = $this->object->$method();
        } catch (Throwable $e) {
            $exception = $e;
        }

        // Analyze results
        $foundClass = $exception ?
            get_class($exception) :
            null;
        $foundStatus = $exception && $exception instanceof Error ?
            $exception->status() :
            null;
        $expectedTest = $this->object->expectedException();
        $expectedStatus = $this->object->expectedStatusCode();
        if (!$foundClass && !$expectedTest) {

            // Case 1: No exception caught and none expected
            $this->cleanup();
            return $result !== null ?
                $result :
                true;
        } elseif ($foundClass && !$expectedTest) {

            // Case 2: Caught exception but no exception expected
            $this->cleanup($exception); // Cleanup and throw
        } elseif ($expectedTest && !$foundClass) {

            // Case 3: Expected exception but none caught
            $desc = is_string($expectedTest) ?
                " of type '$expectedTest'" :
                "";
            echo "Expected exception$desc" .
                 ( $foundStatus ?
                       " with status code '$expectedStatus'" :
                       "" ) .
                 "; none caught";
            $this->cleanup();
            return false;
        } elseif ( is_string($expectedTest) &&
                   $foundClass != $expectedTest &&
                   !is_subclass_of($foundClass, $expectedTest) )
        {
            // Case 4: Exception of unexpected type caught
            echo "Expected exception of type '$expectedTest'; caught " .
                 "exception of type '$foundClass'";
            $this->cleanup($exception); // Cleanup and throw
        } elseif ( $expectedStatus !== null &&
                   $foundStatus !== $expectedStatus )
        {
            // Case 5: Exception with wrong status caught
            echo "Expected exception with status code '$expectedStatus'; " .
                 "found exception " .
                 ( $foundStatus ?
                       "with status code '$foundStatus'" :
                       "of type '$class'" );
            $this->cleanup($exception); // Cleanup and throw
        } elseif (is_callable($expectedTest) && !$expectedTest($exception)) {

            // Case 6: Exception with wrong status caught
            echo "Caught exception " .
                 ( $foundStatus ?
                       "with status code '$foundStatus' " :
                       "of type '$class' " ) .
                 "that doesn't meet the specified criteria";
            $this->cleanup($exception); // Cleanup and throw
        } else {

            // Caught exception matching specified criteria
            echo "Caught expected exception of type '$foundClass' with " .
                 ( $foundStatus ?
                       "status code '$foundStatus' and " :
                       "" ) .
                 "message '" .
                 ( $exception instanceof Error ?
                       $exception->details() :
                       $exception->getMessage() ) .
                 "'";
            return true;
        }
    }

    /**
     * Returns true if the given excpeption is expected
     *
     * @param Throwable $e The exception
     */
    private function exceptionExpected(Throwable $e)
    {
        $expectedStatus = $this->object->expectedStatusCode();
        $actualStatus = $e instanceof Error ?
            $e->status() :
            null;
        return
            ($test = $this->object->expectedException()) &&
            ( is_string($test) && $e instanceof $test ||
              is_callable($test) && $test($e) ) &&
            ($expectedStatus === null || $expectedStatus === $actualStatus);
    }

    /**
     * Invokes the 'afterCase' method on the underlying object, if it exists.
     *
     * @param Throwable $e An exception
     * @throws Exception if $e is non-null or an error occurs.
     */
    private function cleanup(?Throwable $e = null)
    {
        if (method_exists($this->object, 'afterCase'))
            $this->object->afterCase();
        if ($e)
            throw $e;
    }
}
