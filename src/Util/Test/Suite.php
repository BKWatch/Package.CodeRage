<?php

/**
 * Defines the class CodeRage\WebService\Test\Suite
 *
 * File:        CodeRage/Util/Test/Suite.php
 * Date:        Mon May 21 13:34:26 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

/**
 * @ignore
 */

/**
 * Test suite for the package CodeRage\Util
 */
class Suite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\Util\Test\Suite
     */
    function __construct()
    {
        parent::__construct(
            "coderage.util",
            "Test suite for the package CodeRage\Util"
        );
        $this->add(new Base62Suite);
        $this->add(new BracketObjectNotationSuite);
        $this->add(new CommandLineSuite);
        $this->add(new ExponentialBackoffSuite);
        $this->add(new JsonSuite);
        $this->add(new NativeDataEncoderSuite);
        //$this->add(new SmtpSuite);
        $this->add(new TimeSuite);
        $this->add(new ValidateSuite);
        $this->add(new XmlEncoderSuite);
    }
}
