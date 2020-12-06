<?php

/**
 * Defines the class CodeRage\Build\Test\Tool\Foo.
 *
 * File:        CodeRage/Build/Test/Tool/Foo.php
 * Date:        Sat Mar 21 13:08:15 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test\Tool;

/**
 * @ignore
 */

/**
 * Tool type used for testing.
 */
class Foo extends Basic {

    /**
     * Constructs a CodeRage\Build\Test\Tool\Foo.
     */
    function __construct()
    {
        parent::__construct('foo');
    }
}
