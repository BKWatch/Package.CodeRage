<?php

/**
 * Defines the class CodeRage\Test\Test\Operation\InstanceReturnValue
 * 
 * File:        CodeRage/Test/Test/Operation/InstanceReturnValue.php
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


/**
 * Used by CodeRage\Test\Test\Operation\Instance as a return value of execute()
 */
class InstanceReturnValue {

    /**
     * Constructs an instance of CodeRage\Test\Test\Operation\InstanceReturnValue
     *
     * @param mixed $output The return value of nativeDataEncode()
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    public function nativeDataEncode(\CodeRage\Util\NativeDataEncoder $encoder)
    {
        return $this->output;
    }

    /**
     * The return value of nativeDataEncode()
     *
     * @var mixed
     */
    private $output;
}
