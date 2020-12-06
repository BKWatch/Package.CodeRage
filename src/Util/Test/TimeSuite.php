<?php

/**
 * Defines the class CodeRage\Util\Time
 *
 * File:        CodeRage/Util/Test/TimeSuite.php
 * Date:        Mon Mar  2 00:39:20 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CodeRage
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use DateTime;
use DateTimeZone;
use CodeRage\Test\Assert;
use CodeRage\Util\Time;

/**
 * Test suite for the class CodeRage\Util\Time
 */
final class TimeSuite extends \CodeRage\Test\ReflectionSuite  {

    /**
     * Constructs an instance of CodeRage\Util\Test\TimeSuite
     */
    public function __construct()
    {
        parent::__construct(
            "coderage.util.time",
            "Test suite for CodeRage\Util\Time"
        );
    }

    public function testCreateDateTime1()
    {
        $this->checkDateTime(
            [
                'timezone' => 'UTC',
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T11:05:36+00:00'
        );
    }

    public function testCreateDateTime2()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('UTC'),
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T11:05:36+00:00'
        );
    }

    public function testCreateDateTime3()
    {
        $this->checkDateTime(
            [
                'timezone' => '+0000',
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T11:05:36+00:00'
        );
    }

    public function testCreateDateTime4()
    {
        $this->checkDateTime(
            [
                'timezone' => '-0000',
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T11:05:36+00:00'
        );
    }

    public function testCreateDateTime5()
    {
        $this->checkDateTime(
            [
                'timezone' => 'America/New_York',
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T06:05:36-05:00'
        );
    }

    public function testCreateDateTime6()
    {
        $this->checkDateTime(
            [
                'timezone' => '-0500',
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T06:05:36-05:00'
        );
    }

    public function testCreateDateTime7()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00')
            ],
            '2001-02-28T06:05:36-05:00'
        );
    }

    public function testCreateDateTime8()
    {
        $this->checkDateTime(
            [
                'timezone' => 'America/New_York'
            ],
            '2001-02-28T06:05:36-05:00',
             $this->timestamp('2001-02-28T11:05:36+00:00')
        );
    }

    public function testCreateDateTime9()
    {
        $this->checkDateTime(
            [
                'timezone' => '-0500',
            ],
            '2001-02-28T06:05:36-05:00',
             $this->timestamp('2001-02-28T11:05:36+00:00')
        );
    }

    public function testCreateDateTime10()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
            ],
            '2001-02-28T06:05:36-05:00',
             $this->timestamp('2001-02-28T11:05:36+00:00')
        );
    }

    public function testCreateDateTime11()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'year' => 2001,
                'month' => 2,
                'day' => 28,
                'hour' => 6,
                'minute' => 5,
                'second' => 36
            ],
            '2001-02-28T06:05:36-05:00'
        );
    }

    public function testCreateDateTime12()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'year' => 2001,
                'month' => 2,
                'day' => 28
            ],
            '2001-02-28T00:00:00-05:00'
        );
    }

    public function testCreateDateTime13()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'year' => 2001,
                'month' => 2,
                'day' => 28,
                'hour' => 6
            ],
            '2001-02-28T06:00:00-05:00'
        );
    }

    public function testCreateDateTime14()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'year' => 2001,
                'month' => 2,
                'day' => 28,
                'hour' => 6,
                'minute' => 5
            ],
            '2001-02-28T06:05:00-05:00'
        );
    }

    public function testCreateDateTime15()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'year' => 2001,
                'month' => 2,
                'day' => 28,
                'hour' => 6,
                'minute' => 5,
                'second' => 45
            ],
            '2001-02-28T06:05:45-05:00'
        );
    }

    public function testCreateDateTime16()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'timestamp' => $this->timestamp('2001-02-28T22:22:22+00:00'),
                'hour' => 6
            ],
            '2001-02-28T06:00:00-05:00'
        );
    }

    public function testCreateDateTime17()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'timestamp' => $this->timestamp('2001-02-28T22:22:22+00:00'),
                'hour' => 6,
                'minute' => 5
            ],
            '2001-02-28T06:05:00-05:00'
        );
    }

    public function testCreateDateTime18()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('America/New_York'),
                'timestamp' => $this->timestamp('2001-02-28T22:22:22+00:00'),
                'hour' => 6,
                'minute' => 5,
                'second' => 45
            ],
            '2001-02-28T06:05:45-05:00'
        );
    }

    public function testCreateDateTime19()
    {
        $this->checkDateTime(
            [
                'timezone' => new DateTimeZone('UTC'),
                'timestamp' => $this->timestamp('2001-02-28T11:05:36+00:00'),
                'modify' => '-1 year'
            ],
            '2000-02-28T11:05:36+00:00'
        );
    }


    public function testCreateDateTime20()
    {
        $date =
            Time::createDateTime([
                'timezone' => new DateTimeZone('America/New_York'),
                'timestamp' => $this->timestamp('2001-02-28T22:22:22+00:00'),
                'hour' => 6,
                'minute' => 5,
                'second' => 45,
                'format' => 's:i:H d-m-Y'
            ]);
        Assert::equal($date, '45:05:06 28-02-2001', 'Incorrect datetime');
    }

    public function testCreateDateTimeInvalidTimezoneFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'timezone' => -0.00023,
            'timestamp' => 0
        ]);
    }

    public function testCreateDateTimeInvalidYearFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 0.00213,
            'month' => 1,
            'day' => 1
        ]);
    }

    public function testCreateDateTimeInvalidYearFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 0,
            'month' => 1,
            'day' => 1
        ]);
    }

    public function testCreateDateTimeInvalidYearFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 10000,
            'month' => 1,
            'day' => 1
        ]);
    }

    public function testCreateDateTimeInvalidMonthFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2000,
            'month' => 0.00213,
            'day' => 1
        ]);
    }

    public function testCreateDateTimeInvalidMonthFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2000,
            'month' => 0,
            'day' => 1
        ]);
    }

    public function testCreateDateTimeInvalidMonthFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2000,
            'month' => 13
        ]);
    }

    public function testCreateDateTimeInvalidDayFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2000,
            'month' => 1,
            'day' => 0.00213
        ]);
    }

    public function testCreateDateTimeInvalidDayFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2000,
            'month' => 1,
            'day' => 0
        ]);
    }

    public function testCreateDateTimeInvalidYearMonthDayFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2001,
            'month' => 2,
            'day' => 29
        ]);
    }

    public function testCreateDateTimeIncompleteCalendarDayFailure1()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'year' => 2001,
            'month' => 2
        ]);
    }

    public function testCreateDateTimeIncompleteCalendarDayFailure2()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'year' => 2001,
            'day' => 28
        ]);
    }

    public function testCreateDateTimeIncompleteCalendarDayFailure3()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'month' => 2,
            'day' => 28
        ]);
    }

    public function testCreateDateTimeIncompleteCalendarDayFailure4()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'year' => 2001
        ]);
    }

    public function testCreateDateTimeIncompleteCalendarDayFailure5()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'month' => 2
        ]);
    }

    public function testCreateDateTimeIncompleteCalendarDayFailure6()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'day' => 28
        ]);
    }

    public function testCreateDateTimeInvalidCalendarDayFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Time::createDateTime([
            'year' => 2000,
            'month' => 1,
            'day' => 32
        ]);
    }

    public function testCreateDateTimeIncompleteTimeOfDayFailure1()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'hour' => 0,
            'second' => 0
        ]);
    }

    public function testCreateDateTimeIncompleteTimeOfDayFailure2()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'minute' => 0,
            'second' => 0
        ]);
    }

    public function testCreateDateTimeIncompleteTimeOfDayFailure3()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Time::createDateTime([
            'second' => 28
        ]);
    }

    private function checkDateTime(array $options, string $formatted, ?int $now = null) : void
    {
        if ($now !== null)
            Time::set($now);
        try {
            Assert::equal(
                Time::createDateTime($options)->format(DATE_W3C),
                $formatted,
                'Incorrect datetime'
            );
        } finally {
            Time::reset();
        }
    }

    /**
     * Takes a timestamp in DATE_W3C format and returns a UNIX timestamp
     *
     * @param string $formatted
     * @return int
     */
    private function timestamp(string $formatted) : int
    {
        return (new DateTime($formatted))->getTimestamp();
    }
}
