<?php

/**
 * Defines the class CodeRage\Test\Operations\ScheduleSpec
 *
 * File:        CodeRage/Test/Operations/ScheduleSpec.php
 * Date:        Mon March 13 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Represents the column of the schedule in crontab-like syntax
 *
 */
class ScheduleSpec {

    /**
     * Represents the minute column in a crontab job
     *
     * @var int
     */
    const MINUTE = 1;

    /**
     * Represents the hour column in a crontab job
     *
     * @var int
     */
    const HOUR = 2;

    /**
     * Represents the day of month column in a crontab job
     *
     * @var int
     */
    const DAY = 3;

    /**
     * Represents the month column in a crontab job
     *
     * @var int
     */
    const MONTH = 4;

    /**
     * Represents the weekdays column in a crontab job
     *
     * @var int
     */
    const WEEKDAY = 5;

    /**
     * Constructs an instance of CodeRage\Test\Operations\ScheduleSpec
     *
     * @param string $type One of the constants MINUTE, HOUR, DAY, MONTH or
     *   WEEKDAY
     * @param string $definition An expression suitable for inclusing in the
     *   column of a cron job corresponding to $type, with possible leading
     *   and trailing whitespace. Legal values are
     *     * - The wildcard symbol
     *     *&#42;n - A wildcard with a positive integral modulus, or
     *     a,b,c,... - A comma-separated list of integers, in increasing order,
     *       with maximum value determined by the column type.
     */
    public function __construct($type, $definition)
    {
        Args::check($type, 'int', 'type');
        Args::check($definition, 'string', 'definition');
        $this->type = $type;
        $this->definition = $definition;
        $field = trim($definition);
        if ($field == '*') {
            $this->isWildcard = true;
        } elseif (preg_match('#^\*\/(\d+)$#', $field, $match)) {
            $modulus = (int) $match[1];
            list($begin, $end) = self::range($type);
            if ($modulus > $end - $begin)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Modulus $modulus exceeds the range of type " .
                            self::translateType($type)
                    ]);
            $this->isWildcard = false;
            $this->values = range($begin, $end, $modulus);
        } elseif (preg_match('#^\d+(,\d+)*$#', $field)) {
            $values =
                array_map(
                    function ($v) { return (int)$v; },
                    explode(',', $field)
                );
            list($begin, $end) = self::range($type);
            $prev = -INF;
            foreach ($values as $v) {
                if ($v < $begin || $v > $end)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Value $v is invalid for type " .
                                self::translateType($type)
                        ]);
                 if ($v <= $prev)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid definition '$field'; values must " .
                                "appear in ascending order"
                        ]);
                 $prev = $v;
            }
            $this->isWildcard = false;
            $this->values = array_map('intval', explode(',', $field));
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid definition: $field"
                ]);
        }
    }

    /**
     * Returns one of the constants MINUTE, HOUR, DAY, MONTH or WEEKDAY
     *
     * @return int
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns the the list of integers that match this instance, in
     * ascending order, unless this instance is a wildcard
     *
     * @return array
     */
    public function values()
    {
        return $this->values;
    }

    /**
     * Returns true if this instance matches all integers in the
     * range corresponding to its type
     *
     * @return boolean
     */
    public function isWildcard()
    {
        return $this->isWildcard;
    }

    /**
     * Returns the string from which this instance was constructed
     *
     * @return string
     */
    public function definition()
    {
        return $this->definition;
    }

    /**
     * Returns a human-readable type name
     *
     * @param int $type
     * @return string
     */
    private static function translateType($type)
    {
        switch ($type) {
      case self::MINUTE:
          return 'MINUTE';
      case self::HOUR:
          return 'HOUR';
        case self::DAY:
          return 'DAY';
        case self::MONTH:
          return 'MONTH';
        case self::WEEKDAY:
          return 'WEEKDAY';
        default:
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid type: $type"
                ]);
        }
    }

    /**
     * Returns a pair of integers [$begin, $end] representing the range of the
     * given type
     *
     * @param int $type One of the MINUTE, HOUR, DAY, MONTH or WEEKDAY constants
     * @return array
     */
    private static function range($type)
    {
        switch ($type) {
      case self::MINUTE:
          return [0, 59];
      case self::HOUR:
          return [0, 23];
        case self::DAY:
          return [1, 31];
        case self::MONTH:
          return [1, 12];
        case self::WEEKDAY:
          return [0, 6];
        default:
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid type: $type"
                ]);
        }
    }


    /**
     * One of the type constant
     *
     * @var int
     */
    private $type;

    /**
     * An array of ints, in incresing order representing the legal values
     * for the coresponding type
     *
     * @var array
     */
    private $values;

    /**
     * true if this instance matches all integers in the range corresponding to
     * $type
     *
     * @var boolean
     */
    private $isWildcard;

    /**
     * The string from which this instance was constructed
     *
     * @var string
     */
    private $definition;
}
