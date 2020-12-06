<?php

/**
 * Defines the class CodeRage\Test\Operations\Event
 *
 * File:        CodeRage/Test/Operations/Test/Event.php
 * Date:        Mon March 20 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations\Test;

use DateTime;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Stores a list of instances of DateTime representing calls to the method
 * trigger(). Used to test CodeRage\Util\Operations\ScheduledOperationList.
 */
final class Event {

    /**
     * Return the stored list of times
     *
     * @return array A list of instances of DateTime
     */
    public static function history()
    {
        return self::$history;
    }

    /**
     * Clears the stored list of times
     */
    public static function clearHistory()
    {
        self::$history = [];
        return true;
    }

    /**
     * Appends the current time to stored list of times and returns true
     *
     * @return boolean
     */
    public static function trigger()
    {
        $date =  new \DateTime();
        $date->setTimestamp(\CodeRage\Util\Time::get());
        self::$history[] = $date;
        return true;
    }

    /**
     * Returns true if the specified list of timestamps has the same
     * length as the stored list of times, and if each item in the specified
     * list is within the given number of seconds of the corresponding item in
     * the stored list
     *
     * @param array $options The options array; supports the following options:
     *     history - A list of timestamps, as strings or instances of
     *       DateTime
     *     tolerance - The maximum difference in seconds between items in the
     *       specified list and the corresponding items in the stored list
     * @throws Error
     */
    public static function verifyHistory($options)
    {
        // Validate and process options
        Args::checkKey($options, 'history', 'array', ['required' => true]);
        $history = $options['history'];
        foreach ($history as $i => $timestamp)
            if (!$timestamp instanceof \DateTime)
                $history[$i] = new \DateTime($timestamp);
        if (!isset($options['tolerance']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing tolerance'
                ]);
        $tolerance = $options['tolerance'];
        if ( !is_int($tolerance) &&
             (!is_string($tolerance) || !ctype_digit($tolerance)) )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Invalid tolerance: ' . Error::formatValue($tolerance)
                ]);
        }

        // Compare $history with self::$history
        if (count($history) != count(self::$history))
            return false;
        foreach ($options['history'] as $i => $timestamp) {
            $timestamp = new \DateTime($timestamp);
            $lower = $timestamp->add(new \DateInterval("PT{$tolerance}S"));
            $upper = $timestamp->sub(new \DateInterval("PT{$tolerance}S"));
            if (self::$history[$i] < $lower || self::$history[$i] > $upper)
                return false;
        }
        return true;
    }

    /**
     * A list of instance of DateTime
     *
     * @var array
     */
    private static $history;
}
