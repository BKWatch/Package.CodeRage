<?php

/**
 * Defines the class CodeRage\Test\Test\Operation\Instance
 * 
 * File:        CodeRage/Test/Test/Operation/Instance.php
 * Date:        Thu May 24 00:38:06 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Test\Operation;

use CodeRage\Error;
use CodeRage\Xml;


/**
 * Used by CodeRage\Test\Test\Operation\Instance to test top-level functions
 */
function execute($params)
{
    $instance = new Instance($params);
    return $instance->execute();
}

/**
 * Used to test CodeRage\Test\Operation
 */
class Instance {

    /**
     * Constructs an instance of CodeRage\Test\Test\Operation\Instance
     *
     * @param array $params an options array; supports the following options:
     *   echo - true if execute() should return its own input
     *   returnValue - The XML-encoded return value, if any
     *   returnObject - true if an object supporting the method
     *     nativeDataEncode() should be returned
     *   exceptionClass - The class of exception to be thrown, if any
     *   exceptionStatus - The status code of the exception to be thrown, if any
     *   exceptionMessage - The error message of the exception to be thrown, if
     *     any
     */
    public function __construct($params = [])
    {
        // Validate
        if ( (!isset($params['echo']) || $params['echo'] == 'false') &&
             !isset($params['returnValue']) &&
             !isset($params['exceptionClass']) )
        {
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' =>
                        "Must specify echo, return value, or exception"
                ]);
        }
        if ( isset($params['echo']) &&
             $params['echo'] == 'true' &&
             sizeof($params) > 1 )
        {
            $other = isset($params['returnValue']) ?
                'exceptionClass' :
                'returnValue';
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'echo' and '$other' are incompatible"
                ]);
        }
        if ( isset($params['echo']) &&
             $params['echo'] != 'true' &&
             $params['echo'] != 'false' )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid value of the option 'echo': " .
                        "expected 'true' or 'false'; found " .
                        "'{$params['returnObject']}'"
                ]);
        }
        if (isset($params['returnValue']) && isset($params['exceptionClass']))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'returnValue' and 'exceptionClass' " .
                        "are incompatible"
                ]);
        if ( isset($params['returnObject']) &&
             $params['returnObject'] != 'true' &&
             $params['returnObject'] != 'false' )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid value of the option 'returnObject': " .
                        "expected 'true' or 'false'; found " .
                        "'{$params['returnObject']}'"
                ]);
        }
        if ( isset($params['exceptionClass']) &&
             $params['exceptionClass'] != 'Exception' &&
             $params['exceptionClass'] != 'CodeRage\Error' )
        {
            $class = $params['exceptionClass'];
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "The exception class '$class' is not supported"
                ]);
        }
        if ( isset($params['exceptionClass']) &&
             !isset($params['exceptionMessage']) )
        {
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => "Missing option 'exceptionMessage'"
                ]);
        }
        if ( isset($params['exceptionClass']) &&
             $params['exceptionClass'] == 'CodeRage\Error' &&
             !isset($params['exceptionStatus']) )
        {
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => "Missing option 'exceptionStatus'"
                ]);
        }
        if (isset($params['returnValue']) && isset($params['exceptionStatus']))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'returnValue' and 'exceptionStatus' " .
                        "are incompatible"
                ]);
        if ( isset($params['exceptionStatus']) &&
             $params['exceptionClass'] != 'CodeRage\Error' )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The option 'exceptionStatus' may only be " .
                        "supplied for exceptions of type 'CodeRage\Error'"
                ]);
        }

        // Appy defaults
        if (isset($params['returnValue']) && !isset($params['returnObject']))
            $params['returnObject'] = 'false';
        if (!isset($params['echo']))
            $params['echo'] = 'false';
        $this->params = $params;
    }

    /**
     * Returns a value deduced from the constructor arguments
     *
     * @return array
     */
    public function execute($input = null)
    {
        $params = $this->params;
        if ($input !== null && $params['echo'] == 'false')
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'message' =>
                        "Input to execute() may only be specified if " .
                        "echo mode is enabled"
                ]);
        if ($input === null && $params['echo'] == 'true')
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "If echo-mode is enabled, execute() requires a " .
                        "non-null argument"
                ]);
        if ($params['echo'] == 'true') {
            return (object) $input;
        } elseif (isset($params['returnValue'])) {
            $encoder = new \CodeRage\Util\XmlEncoder;
            $elt = Xml::loadDocumentXml($params['returnValue'])->documentElement;
            $result = $encoder->decode($elt);
            return $params['returnObject'] == 'true' ?
                new InstanceReturnValue($result) :
                $result;
        } elseif ($params['exceptionClass'] == 'Exception') {
            throw new \Exception($params['exceptionMessage']);
        } else {
            throw new
                Error([
                    'status' => $params['exceptionStatus'],
                    'message' => $params['exceptionMessage']
                ]);
        }
    }

    /**
     * The constructor argument
     *
     * @var array
     */
    private $params;
}
