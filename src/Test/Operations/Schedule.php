<?php

/**
 * Defines the class CodeRage\Test\Operations\Schedule
 *
 * File:        CodeRage/Test/Operations/Schedule.php
 * Date:        Mon March 13 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use DateTime;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Describes a finite sequence of times when event should occur. This can
 * consist of a single fixed time, or a sequence of times within a range
 * that match a pattern defined with crontab-like syntax.
 */
final class Schedule extends Base {

    /**
     * Regular expression matching legal values of the "repeat" constructor
     * option
     *
     * @var string
     */
    const MATCH_REPEAT =
        '#^(\*\/\d+|(\d+(,\d+)*)|\d+|\*)(\s+(\*\/\d+|(\d+(,\d+)*)|\d+|\*)){4}$#';

    /**
     * Constructs an instance of CodeRage\Test\Operations\Schedule
     *
     * @param array $option Supports the following options:
     *     time - The time at which events goverened by the schedule occur, as
     *       a string or as an instance of DateTime, for schedules representing
     *       a single fixed time
     *     from - The beginning date of the schedule, as a string or as an
     *       instance of DateTime, for repeating schedules
     *     to - The ending date of the schedule, as a string or as an
     *       instance of DateTime, for repeating schedules
     *     repeat - A whitespace-separated list of five schedule specifiers
     *       in a format similar to the five columns of a cron job schedule
     *   The option "time" must occur alone; if it is not supplied, the other
     *   three options must all be supplied.
     */
    public function __construct($options)
    {
        Args::uniqueKey($options, ['time', 'from']);
        if ( isset($options['from']) != isset($options['to']) ||
             isset($options['from']) != isset($options['repeat']) )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                         "The options 'from', 'to', and 'repeat' must be " .
                         "used together"
                ]);
        }

        // Validate time, from, to options
        foreach (['time', 'from', 'to'] as $date) {
            if (!isset($options[$date]))
                continue;
            if (is_string($options[$date])) {
                try {
                    $options[$date] = new DateTime($options[$date]);
                } catch (\Throwable $e) {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' => "Invalid date: {$options[$date]}"
                        ]);
                }
            } elseif (!($options[$date] instanceof DateTime)) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid $date",
                        'details' =>
                            "Invalid $date: expected DateTime or string; " .
                            'found ' . Error::formatValue($options[$date])
                    ]);
            }
        }
        if (isset($options['time'])) {
            $this->time = $options['time'];
        } else {
            Args::check($options['repeat'], 'string', 'schedule specifiers');
            if (preg_match('#(^\s)|(\s$)#', $options['repeat']))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The option 'repeat' does not allow leading or " .
                            'trailing white spaces'
                    ]);
            if ($options['from'] >= $options['to'])
                throw new
                    Error([
                        'status' => 'INCONSISTENT_PARAMETERS',
                        'details' => 'Begin date must be earlier than end date'
                    ]);
            $this->from = $options['from'];
            $this->to = $options['to'];
            $repeat = $options['repeat'];
            if (!preg_match(self::MATCH_REPEAT, $repeat))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Invalid schedule specifiers: $repeat"
                    ]);
            $columns = preg_split('#(?<=\s)(?=\S)#', $options['repeat']);
            if ( !preg_match('/\*\s*/', $columns[2]) &&
                 !preg_match('/\*\s*/', $columns[4]) )
            {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Invalid schedule specifiers '$repeat': at least " .
                            "one of the columns 'day' and 'weekday' must be " .
                            "a wildcard"
                    ]);
            }
            for ( $type = ScheduleSpec::MINUTE;
                  $type <= ScheduleSpec::WEEKDAY;
                  ++$type )
            {
                $this->specifiers[$type] =
                    new ScheduleSpec($type, $columns[$type - 1]);
            }
        }
    }

    /**
     * Retruns the time at which events goverened by this schedule occur, for
     * schedules representing a single fixed time
     *
     * @return DateTime
     */
    public function time()
    {
        return $this->time;
    }

    /**
     * Retruns the beginning date of this schedule, for repeating schedules
     *
     * @return DateTime
     */
    public function from()
    {
        return $this->from;
    }

    /**
     * Retruns the ending date of this schedule, for repeating schedules
     *
     * @return DateTime
     */
    public function to()
    {
        return $this->to;
    }

    /**
     * Returns a schedule specifier corresponding to the minute column of a
     * cron job schedule, for repeating schedules
     *
     * @return CodeRage\Test\Operations\ScheduleSpec A schedule specifier with
     *   type CodeRage\Test\Operations\ScheduleSpec::MINUTE
     */
    public function minute()
    {
        return $this->specifier(ScheduleSpec::MINUTE);
    }

    /**
     * Returns a schedule specifier corresponding to the hour column of a
     * cron job schedule, for repeating schedules
     *
     * @return CodeRage\Test\Operations\ScheduleSpec A schedule specifier with
     *   type CodeRage\Test\Operations\ScheduleSpec::HOUR
     */
    public function hour()
    {
        return $this->specifier(ScheduleSpec::HOUR);
    }

    /**
     * Returns a schedule specifier corresponding to the day of month column of
     * a cron job schedule, for repeating schedules
     *
     * @return CodeRage\Test\Operations\ScheduleSpec A schedule specifier with
     *   type CodeRage\Test\Operations\ScheduleSpec::DAY
     */
    public function day()
    {
        return $this->specifier(ScheduleSpec::DAY);
    }

    /**
     * Returns a schedule specifier corresponding to the month column of a
     * cron job schedule, for repeating schedules
     *
     * @return CodeRage\Test\Operations\ScheduleSpec A schedule specifier with
     *   type CodeRage\Test\Operations\ScheduleSpec::MONTH
     */
    public function month()
    {
        return $this->specifier(ScheduleSpec::MONTH);
    }

    /**
     * Returns a schedule specifier corresponding to the weekday column of a
     * cron job schedule, for repeating schedules
     *
     * @return CodeRage\Test\Operations\ScheduleSpec A schedule specifier with
     *   type CodeRage\Test\Operations\ScheduleSpec::WEEKDAY
     */
    public function weekday()
    {
        return $this->specifier(ScheduleSpec::WEEKDAY);
    }

    /**
     * Returns the associated schedule specifier with the given type, for
     * repeating schedules
     *
     * @return CodeRage\Test\Operations\ScheduleSpec
     */
    public function specifier($type)
    {
        return isset($this->specifiers[$type]) ?
            $this->specifiers[$type] :
            null;
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\Schedule newly
     * constructed from the given "schedule" element
     *
     * @param DOMElement $elt An element with localName "schedule"
     *   conforming to the schema "operation.xsd"
     * @return CodeRage\Test\Operations\Schedule
     */
    public static function load(\DOMElement $elt)
    {
        $options = [];
        $time = $elt->getAttribute('time');
        if ($time)
            $options['time'] = new DateTime($time);
        $from = $elt->getAttribute('from');
        if ($from)
            $options['from'] = new DateTime($from);
        $to = $elt->getAttribute('to');
        if ($to)
            $options['to'] = new DateTime($to);
        $repeat = $elt->getAttribute('repeat');
        if ($repeat)
            $options['repeat'] = $repeat;
        return new self($options);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'schedule');
        if ($time = $this->time())
            $elt->setAttribute('time', $time->format('Y-m-d\TH:i:sP'));
        if ($from = $this->from())
            $elt->setAttribute('from', $from->format('Y-m-d\TH:i:sP'));
        if ($to = $this->to())
            $elt->setAttribute('to', $to->format('Y-m-d\TH:i:sP'));
        $repeat = '';
        foreach (['minute', 'hour', 'day', 'month', 'weekday'] as $type)
        {
            $spec = $this->$type();
            if ($spec !== null)
            $repeat .= $spec->definition();
        }
        if ($repeat != '')
            $elt->setAttribute('repeat', $repeat);
        return $elt;
    }

    /**
     * The time at which events goverened by this schedule occur, as an instance
     * of DateTime, for schedules representing a single fixed time
     *
     * @var DateTime
     */
    private $time;

    /**
     * The beginning date of this schedule, for repeating schedules
     *
     * @var DateTime
     */
    private $from;

    /**
     * The ending date of this schedule, for repeating schedules
     *
     * @var DateTime
     */
    private $to;

    /**
     * An array mapping the constants MINUTE, HOUR, DAY, MONTH, and WEEKDAY,
     * defined in CodeRage\Test\Operations\ScheduleSpec, to instances of
     * CodeRage\Test\Operations\ScheduleSpec
     *
     * @var array
     */
    private $specifiers = [];

}
