<?php

/**
 * Defines the class CodeRage\Tool\Test\RobotSuite
 *
 * File:        CodeRage/Tool/Test/RobotSuite.php
 * Date:        Mon Apr 16 11:12:16 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

/**
 * Test suite for the trait CodeRage\Tool\Robot and the class
 * CodeRage\Tool\BasicRobot
 */
class RobotSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * @var string
     */
    private const PROXY_CONFIG_VAR = 'test.robot_proxy';

    /**
     * Constructs an instance of CodeRage\Tool\Test\RobotSuite
     */
    public function __construct()
    {
        parent::__construct(
            "Robot Test Suite",
            "Tests the class CodeRage\Tool\Test\RobotSuite"
        );
        $this->add(new RobotSubSuite([
            'name' => 'Basic Suite',
            'description' => 'Basic robot test suite'
        ]));
        $proxy = \CodeRage\Config::current()->getProperty(self::PROXY_CONFIG_VAR);
        if ($proxy !== null) {
            $this->add(new RobotSubSuite([
                'name' => 'Proxy Suite',
                'description' => 'Tests robots with proxy options',
                'robotOptions' => ['proxy' => $proxy],
                'skipLongCases' => true
            ]));
        }
    }
}
