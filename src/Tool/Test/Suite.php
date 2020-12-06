<?php

/**
 * Defines the class CodeRage\Tool\Test\Suite
 *
 * File:        CodeRage/Tool/Test/Suite.php
 * Date:        Fri Mar  6 00:42:38 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CodeRage
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

/**
 * Test suite for the package CodeRage\Tool
 *
 * @package CodeRage\Tool\Test
 */
class Suite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\Tool\Test\Suite
     */
    public function __construct()
    {
        parent::__construct(
            "coderage.tool",
            "Test suite for the package CodeRage\Tool"
        );
        $this->add(new CleanHtmlSuite);
        $this->add(new OfflineSuite);
        $this->add(new RobotSuite);
    }
}
