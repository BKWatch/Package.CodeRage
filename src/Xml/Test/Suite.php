<?php

/**
 * Defines the class CodeRage\Xml\Test\XsltProcessorSuite
 *
 * File:        CodeRage/Xml/Test/Suite.php
 * Date:        Sat Aug 6 02:43:17 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Xml\Test;

/**
 * @ignore
 */

/**
 * Test suite for the package CodeRage.Util
 */
class Suite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\Util\Test\Suite.
     */
    function __construct()
    {
        parent::__construct(
            "CodeRage\Xml Test Suite",
            "Test suite for CodeRage\Xml\XsltProcessor"
        );
        $this->add(new XsltProcessorSuite());
    }
}
