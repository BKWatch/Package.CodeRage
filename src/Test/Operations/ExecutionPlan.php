<?php

/**
 * Defines the class CodeRage\Test\Operations\ExecutionPlan
 *
 * File:        CodeRage/Test/Operations/ExecutionPlan.php
 * Date:        Tue March 14 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use DOMDocument;
use DateInterval;
use DateTime;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Util\Time;


/**
 * Represents the schedule for the Schedulable operation
 */
final class ExecutionPlan {
    use XmlSupport;

    /**
     * @var int
     */
    const MINUTE = ScheduleSpec::MINUTE;

    /**
     * @var int
     */
    const HOUR = ScheduleSpec::HOUR;

    /**
     * @var int
     */
    const DAY = ScheduleSpec::DAY;

    /**
     * @var int
     */
    const MONTH = ScheduleSpec::MONTH;

    /**
     * @var int
     */
    const WEEKDAY = ScheduleSpec::WEEKDAY;

    /**
     * @var string
     */
    const MATCH_BOOLEAN = '/^(1|0|true|false)$/';

    /**
     * @var string
     */
    const MATCH_TRUE_BOOLEAN = '/^(1|true)$/';

    /**
     * Constructs an instance of CodeRage\Test\Operations\ExecutionPlan
     *
     * @param string $description The description of the execution plan
     * @param array $operations An array of instances of
     *   CodeRage\Test\Operations\Schedulable
     */
    public function __construct($description, $operations)
    {
        $this->description = $description;
        $this->steps = [];
        $start = INF;
        foreach ($operations as $i => $op) {
            $schedule = $op->schedule();
            $time = $schedule->time();
            if ($time !== null) {
                $start = min($start, (int) $time->format('U'));
                $step = new ExecutionPlanStep($time, $op);
                $step->setProperty('position', $i);
                $this->steps[] = $step;
            } else {
                $from = $schedule->from();
                $to = $schedule->to();
                $timezone = $from->getTimezone();
                $years = range($from->format('Y'), $to->format('Y'));
                $months = $this->getRange($schedule, self::MONTH);
                $hours = $this->getRange($schedule, self::HOUR);
                $minutes = $this->getRange($schedule, self::MINUTE);
                foreach ($years as $year) {
                    foreach ($months as $month) {
                        $days =
                            $this->getRange($schedule, self::DAY, $month, $year);
                        foreach ($days as $day) {
                            if (!checkdate($month, $day, $year))
                                continue;
                            foreach ($hours as $hour) {
                                foreach ($minutes as $minute) {
                                    $time = new DateTime(null, $timezone);
                                    $time->setDate($year, $month, $day);
                                    $time->setTime($hour, $minute, 0);
                                    if ($from > $time)
                                        continue;
                                    if ($to < $time)
                                        break 5;
                                    $start = min($start, (int) $time->format('U'));
                                    $step = new ExecutionPlanStep($time, $op);
                                    $step->setProperty('position', $i);
                                    $this->steps[] = $step;
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->start = $start;
        usort(
            $this->steps,
            function($a, $b)
            {
                if ($a->time() == $b->time())
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                'Failed constructing execution plan: ' .
                                'operations at position ' .
                                $a->getProperty('position') . ' and ' .
                                $b->getProperty('position') . ' are both ' .
                                'scheduled for execution at ' .
                                $a->time()->format(DATE_W3C)
                        ]);
                return $a->time()->getTimestamp() - $b->time()->getTimestamp();
            }
        );
    }

    /**
     * Returns the start time of the execution plan, as a UNIX timestamp
     *
     * @return int
     */
    public function start()
    {
        return $this->start;
    }

    /**
     * Returns an array of instances of
     * CodeRage\Test\Operations\ExecutionPlanStep
     *
     * @return array
     */
    public function steps()
    {
        return $this->steps;
    }

    /**
     * Returns the description
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Executes this execution plan by iterating over the underlying collection
     * of step, passing the value of the each step's time property to
     * CodeRage\Util\Time::set(), and invoking the given callable with this
     * instance and the step as arguments
     *
     * @param callable $action A callable taking two arguments, $plan, and
     *   $step
     */
    public function execute($action)
    {
        $config = Config::current();
        Time::set($this->start);
        foreach ($this->steps() as $step) {
            $this->setTime($step);
            $step->setProperty('begin', Time::real());
            $action($this, $step);
            $step->setProperty('end', Time::real());
        }
    }

    /**
     * Creates an XML document conforming to operations.xsd with top-level
     * element "planExecution", describing the most recent execution of this
     * plan, and saves it to the given path
     *
     * @param string $path A path to a file
     */
    public function save($path)
    {
        $ns = self::NAMESPACE_URI;
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $plan = $dom->createElementNS($ns, 'executionPlanLog');

        // Handle description
        $plan->appendElement($dom, $plan, 'description', $this->description());

        // Handle steps
        foreach ($this->steps as $i => $step) {
            $elt = $dom->createElementNS($ns, 'step');
            $elt->appendChild(
                $dom->createElementNS(
                    $ns, 'description', $step->operation()->description()
                )
            );
            $end = $step->getProperty('end');
            $begin = $step->getProperty('begin');
            if (!is_int($begin))
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' =>
                            "Invalid begin date: expected integer; found " .
                            Error::formatValue($begin)
                    ]);
            if (!is_int($end))
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' =>
                            "Invalid end date: Expected integer; found: " .
                            Error::formatValue($begin)
                    ]);
            $elt->setAttribute('scheduled', $step->time()->format(DATE_W3C));
            $elt->setAttribute('begin', date(DATE_W3C, $begin));
            $elt->setAttribute('end', date(DATE_W3C, $end));
            $plan->appendChild($elt);
        }
        $dom->appendChild($plan);

        $dom->save($path);
    }

    /**
     * Returns an array of integers in increasing order representing the subset
     * of the possible values for a schedule specifier with the given type that
     * are consistent with the given schedule
     *
     * @param CodeRage\Test\Operations\Schedule $schedule
     * @param string $type One of the constants MINUTE, HOUR, DAY, or MONTH,
     *   defined in the class CodeRage\Tet\Operations\ScheduleSpec
     * @param string $month The 2 digit month, for use with $type DAY
     * @param string $year The 4 digit year, for use with $type DAY
     *   'weekday'
     * @return array
     */
    private function getRange($schedule, $type, $month = null, $year = null)
    {
        $spec = $schedule->specifier($type);
        if ($type == self::DAY || $type == self::WEEKDAY) {
            if ( $schedule->day()->isWildCard() &&
                 $schedule->weekday()->isWildCard() )
            {
                return range(1, 31);
            } elseif (!$schedule->day()->isWildCard()) {
                return array_map('trim', $spec->values());
            } else {
                $start = (new DateTime)->setDate($year, $month, 1);
                $end = (new DateTime)->setDate($year, $month, 1);
                $end->add(new DateInterval('P1M'));
                $interval = new DateInterval('P1D');
                $period = new \DatePeriod($start, $interval, $end);
                $values = $schedule->weekday()->values();
                array_walk(
                    $values,
                    function (&$v) { $v = str_replace(0, 7, $v); }
                );
                $range = [];
                foreach($period as $date) {
                    if (in_array($date->format('N'), $values))
                        $range[] = $date->format('d');
                }
                return $range;
            }
        } elseif ($spec->isWildCard()) {
            switch ($type) {
            case self::MINUTE:
                return range(0, 59);
            case self::HOUR:
                return range(0, 23);
            case self::MONTH:
                return range(1, 12);
            default:
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Invalid type: $type"
                    ]);
            }
        } else {
            return array_map('trim', $spec->values());
        }
    }

    /**
     * Sets the current time based on the execution step time
     *
     * @param step \CodeRage\Test\Operations\ExecutionPlanStep
     */
    private function setTime(ExecutionPlanStep $step)
    {
        $current = (new DateTime)->setTimestamp(Time::get());
        $scheduled = $step->time();
        $index = $step->operation()->index();
        if ($index !== 0 && $step->time() < $current) {
            $repeating = $step->operation()->schedule()->time() === null ?
                ' repeating' :
                '';
            $scheduledDate = $step->time()->format(DATE_W3C);
            $currentDate = $current->format(DATE_W3C);
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' =>
                        "Cannot execute$repeating operation $index at time " .
                        "$scheduledDate; Scheduled execution time has passed " .
                        "(current time is $currentDate)"
                ]);
        }
        $timestamp = (int)$step->time()->format('U');
        $offset = $timestamp - Time::real();
        $config =
            new Config(
                ['coderage.util.time.offset' => (string)$offset],
                Config::current()
            );
        Config::setCurrent($config);
        Time::set($timestamp);
    }

    /**
     * The description
     *
     * @var string
     */
    private $description;

    /**
     * The start time of the execution plan, as a UNIX timestamp
     *
     * @var int
     */
    private $start;

    /**
     * An array of instances of CodeRage\Test\Operations\ExecutionPlanStep
     *
     * @var array
     */
    private $steps;
}
