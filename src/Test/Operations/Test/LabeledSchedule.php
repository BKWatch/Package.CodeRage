<?php

/**
 * Defines the class CodeRage\Test\Operations\LabeledSchedule
 *
 * File:        CodeRage/Test/Operations/Test/LabeledSchedule.php
 * Date:        Tue Match 14 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations\Test;

/**
 * Interface implemented by components with schedules
 */
class LabeledSchedule implements \CodeRage\Test\Operations\Schedulable {

    /**
     * Constructs an instance of CodeRage\Test\Operations\LabeledSchedule
     *
     * @param string $label The label
     * @param CodeRage\Test\Operations\Schedule $schedule The schedule
     *
     */
    public function __construct($label, $schedule)
    {
        $this->label = $label;
        $this->schedule = $schedule;
    }

    /**
     * Returns the label
     *
     * @return string
     */
    public function label()
    {
        return $this->label;
    }

    /**
     * Alias of label()
     *
     * @return string
     */
    public function description()
    {
        return $this->label;
    }

    /**
     * Returns the schedule
     *
     * @return CodeRage\Test\Operations\Schedule
     */
    public function schedule()
    {
        return $this->schedule;
    }

    /**
     * The label
     *
     * @var string
     */
    private $label;

    /**
     * The schedule
     *
     * @var CodeRage\Test\Operations\Schedule
     */
    private $schedule;
}
