<?php

/**
 * Defines the class CodeRage\WebService\Test\Suite
 *
 * File:        CodeRage/WebService/Test/Suite.php
 * Date:        Fri Mar  6 00:42:38 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CodeRage
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Test;

/**
 * Test suite for the package CodeRage\WebService
 *
 * @package CodeRage\WebService\Test
 */
class Suite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\WebService\Test\Suite
     */
    public function __construct()
    {
        parent::__construct(
            "coderage.webservice",
            "Test suite for the package CodeRage\WebService"
        );
        $this->add(new SearchSuite);
    }
}
