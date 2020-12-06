<?php

/**
 * Test suite for the CodeRage build system
 *
 * File:        CodeRage/Build/Test/Suite.php
 * Date:        Tue Mar 17 11:22:16 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test;

/**
 * @ignore
 */

/**
 * Test suite that combines the config suite and the target suite.
 */
class Suite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\Build\Test\Suite.
     */
    function __construct()
    {
        parent::__construct(
            "CodeRage.Build Test Suite",
            "Tests the CodeRage.Build package"
        );
        $this->add(new ConfigSuite);
        $this->add(new TargetSuite);
    }
}
