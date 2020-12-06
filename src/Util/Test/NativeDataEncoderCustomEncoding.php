<?php

/**
 * Defines the class CodeRage\Util\Test\NativeDataEncoderCustomEncoding
 * 
 * File:        CodeRage/Util/Test/NativeDataEncoderCustomEncoding.php
 * Date:        Mon May 21 13:34:26 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\NativeDataEncoder;

/**
 * @ignore
 */

/**
 * Represents an object with a nativeDataEncode() method, as expected by
 * CodeRage\Util\NativeDataEncoder::encode()
 */
class NativeDataEncoderCustomEncoding
    extends NativeDataEncoderRuleBasedEncoding
{

    /**
     * Constructs a CodeRage\Util\Test\NativeDataEncoderCustomEncoding
     *
     * @param mixed $encoding The unconditional return value of
     *   nativeDataEncode(), if any
     */
    public function __construct($encoding = null)
    {
        $this->addRule('/.*/', $encoding);
    }

    /**
     * Returns the result of encoding this object as a native data structure
     *
     * @param CodeRage\Util\NativeDataEncoder $encoder The native data encoder
     */
    public function nativeDataEncode(NativeDataEncoder $encoder)
    {
        return $this->execute($encoder);
    }
}
