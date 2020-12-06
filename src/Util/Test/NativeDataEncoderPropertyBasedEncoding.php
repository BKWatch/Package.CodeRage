<?php

/**
 * Defines the class CodeRage\Util\Test\NativeDataEncoderPropertyBasedEncoding
 * 
 * File:        CodeRage/Util/Test/NativeDataEncoderPropertyBasedEncoding.php
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
 * Represents an object with a nativeDataProperties() method, as expected by
 * CodeRage\Util\NativeDataEncoder::encode()
 */
class NativeDataEncoderPropertyBasedEncoding
    extends NativeDataEncoderRuleBasedEncoding
{

    /**
     * Constructs a CodeRage\Util\Test\NativeDataEncoderPropertyBasedEncoding
     *
     * @param mixed $properties The unconditional return value of
     *   nativeDataProperties(), if any
     */
    public function __construct($properties = null)
    {
        $this->addRule('/.*/', $properties);
    }

    /**
     * Returns either a list of property names or an associative array mapping
     * property names to method names or callable objects.
     *
     * @param CodeRage\Util\NativeDataEncoder $encoder The native data encoder
     */
    public function nativeDataProperties(NativeDataEncoder $encoder)
    {
        return $this->execute($encoder);
    }
}
