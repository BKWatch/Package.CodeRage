<?php

/**
 * Defines the class CodeRage\Util\DateTime
 *
 * File:        CodeRage/Util/DateTime.php
 * Date:        Mon Mar  2 22:10:16 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

/**
 * Subclass of DateTime providing additional accessor methods
 */
final class DateTime extends \DateTime {

    /**
     * Constructs an instance of CodeRage\Util\DateTIme
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    /**
     * Returns the year
     *
     * @return int
     */
    public function year() : int
    {
        return (int) $this->format('Y');
    }

    /**
     * Returns true if the underlying year is a leap year
     *
     * @return bool
     */
    public function isLeapYar() : bool
    {
        return (boolean) $this->format('L');
    }

    /**
     * Returns the month
     *
     * @return int
     */
    public function month() : int
    {
        return (int) $this->format('n');
    }

    /**
     * Returns the day
     *
     * @return int
     */
    public function day() : int
    {
        return (int) $this->format('j');
    }

    /**
     * Returns a numeric representation of the day of the week
     *
     * @param boolean $iso8601 true to use the ISO-8601 representation,
     *   according to which Sunday has the value 7; defaults to true
     * @return int 1 (for Monday) through 7 (for Sunday)
     */
    public function weekday(bool $iso8601 = true) : int
    {
        return (int) $this->format($iso8601 ? 'N' : 'w');
    }

    /**
     * Returns the number of days in the underlying month
     *
     * @return int
     */
    public function daysInMonth() : int
    {
        return (int) $this->format('t');
    }

    /**
     * Returns the hour
     *
     * @return int
     */
    public function hour() : int
    {
        return (int) $this->format('G');
    }

    /**
     * Returns the minute
     *
     * @return int
     */
    public function minute() : int
    {
        return (int) $this->format('i');
    }

    /**
     * Returns the second
     *
     * @return int
     */
    public function second() : int
    {
        return (int) $this->format('s');
    }
}
