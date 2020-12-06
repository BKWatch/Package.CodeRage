<?php

/**
 * Defines the class CodeRage\Test\Operations\Schedulable
 *
 * File:        CodeRage/Test/Operations/Schedulable.php
 * Date:        Tue March 14 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

/**
 * Interface implemented by components with schedules
 */
interface Schedulable {

    /**
     * Returns the decription
     *
     * @return string
     */
    function description();

    /**
     * Returns an instance of CodeRage\Test\Operations\Schedule
     *
     * @return CodeRage\Test\Operations\Schedule
     */
    function schedule();
}
