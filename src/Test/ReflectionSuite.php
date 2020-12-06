<?php

/**
 * Defines the class CodeRage\Test\ReflectionSuite.
 *
 * File:        CodeRage/Test/ReflectionSuite.php
 * Date:        Mon Feb 02 13:37:02 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test suite whose cases are constructed from the public methods of the form
 * testXXX, discovered using the Reflection extension.
 */
abstract class ReflectionSuite extends Suite {

    /**
     * true if the Reflection extension is loaded.
     *
     * @var boolean
     */
    private $hasReflection;

    /**
     * A string containing an exception class name or a callable taking a
     * single argument and returing a boolean, to be used to be called with an
     * exception object if one is caught
     *
     * @var mixed
     */
    private $expectedException;

    /**
     * The status code of the expected exception, if any.
     *
     * @var string
     */
    private $expectedStatusCode;

    /**
     * Constructs a CodeRage\Test\ReflectionSuite.
     *
     * @param string $name The suite name
     * @param string $description The suite description
     */
    public function __construct($name = null, $description = null)
    {
        // Check for Reflection extension and call parent ctor
        $this->hasReflection = in_array('Reflection', get_loaded_extensions());
        if (!$name) {
            $match = null;
            $name = preg_match('/[a-z][a-z0-9]*$/i', get_class($this), $match) ?
                $this->translateCamelCase($match[0]) :
                '';
        }
        if (!$description && $this->hasReflection)
            $description =
                $this->readDocComment(new \ReflectionClass($this));
        parent::__construct($name, $description);

        // Add cases
        $orig = clone $this;
        foreach (get_class_methods($this) as $method)
            if (strncmp($method, 'test', 4) == 0)
                $this->addReflectionCase(clone $orig, $method);
    }

    /**
     * Returns the class name of the expected exception, if any.
     *
     * @return string
     */
    public final function expectedException()
    {
        return $this->expectedException;
    }

    /**
     * Returns status code of the expected exception, if any.
     *
     * @return string
     */
    public final function expectedStatusCode()
    {
        return $this->expectedStatusCode;
    }

    /**
     * Specifies a test for the expected exception, if any
     *
     * @param mixed $test A string containing an exception class name or a
     *   callable taking a single argument and returing a boolean, to be used
     *   to be called with an exception object if one is caught
     */
    public final function setExpectedException($test = 'Exception')
    {
        if ($test !== null)
            Args::check($test, 'string|callable', 'test');
        $this->expectedException = $test;
    }

    /**
     * Specifies the status code of the expected exception; if the status code
     * is non-null and the expected exception class has not been set, the
     * expected exception class will be set to 'CodeRage\Error'.
     *
     * @param string $status The status code;
     */
    public final function setExpectedStatusCode($status = null)
    {
        $this->expectedStatusCode = $status;
        if ($status !== null && $this->expectedException === null)
            $this->setExpectedException('CodeRage\Error');
    }

    /**
     * Adds a case of type CodeRage\Test\ReflectionCase based on the named method.
     *
     * @param object $object
     * @param string $method
     */
    private function addReflectionCase($object, $method)
    {
        $reflector = $this->hasReflection ?
            new \ReflectionMethod($object, $method) :
            null;
        $name = $this->translateCamelCase(substr($method, 4));
        $description = $this->hasReflection ?
            $this->readDocComment($reflector) :
            '';
        $case = new ReflectionCase($name, $description, $this, $method);
        $this->add($case);
    }

    /**
     * Returns the content of ths the doc comment associated with the given
     * reflection class or method
     *
     * @param Reflector $reflector
     * @return string
     */
    private function readDocComment($reflector)
    {
        $comment = $reflector->getDocComment();
        if (!$comment)
            return '';
        $comment =
            preg_replace(
                '#\A\s*/\*\*(.*?)^\s*\*(\s*@|/).*#sm', '$1', $comment
            );
        $comment = preg_replace('#^\s*\*#m', '', $comment);
        $comment = preg_replace('/\s+/', ' ', trim($comment));
        return $comment;
    }

    /**
     * Splits a camel case identifier into hyphen-delimited words
     *
     * @param string $value The identifier
     * @return string
     */
    private function translateCamelCase($value)
    {
        $result = '';
        $digit = false;  // Was last character a digit?
        $upper = false;  // Was last character uppercase?
        for ($z = 0, $n = strlen($value); $z < $n; ++$z) {
            $c = $value[$z];
            if ($z) {
                if ( ctype_digit($c) && !$digit ||
                     ctype_upper($c) &&
                        ( !$upper ||
                          $z < $n - 1 && !ctype_upper($value[$z + 1]) ) )
                {
                    $result .= '-';
                }
            }
            $result .= $c;
            $digit = ctype_digit($c);
            $upper = ctype_upper($c);
        }
        return strtolower($result);
    }
}
