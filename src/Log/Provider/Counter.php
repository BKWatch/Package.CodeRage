<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\Counter
 *
 * File:        CodeRage/Log/Provider/Counter.php
 * Date:        Thu Jan 31 20:33:13 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

use CodeRage\Log;

/**
 * @ignore
 */

/**
 * Implementation of CodeRage\Log\Provider that counts log entries
 */
final class Counter implements \CodeRage\Log\Provider {

    public function name() { return 'counter'; }

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    public function dispatchEntry(\CodeRage\Log\Entry $entry)
    {
        $level = $entry->level();
        $tags = $entry->tags();

        // Increment global count
        if (!isset(self::$count[$level]))
            self::$count[$level] = 0;
        ++self::$count[$level];

        // Increment tag-specific count
        foreach ($tags as $t) {
            if (!isset(self::$countByTag[$t]))
                self::$countByTag[$t] = [];
            if (!isset(self::$countByTag[$t][$level]))
                self::$countByTag[$t][$level] = 0;
            ++self::$countByTag[$t][$level];
        }
    }

    /**
     * Returns the number of log entries with the specified properties
     *
     * @param array $options The options array; supports the following options:
     *   tag - The tag, if any
     *   minLevel - The minimum level; defaults to CodeRage\Log::CRITICAL
     *   maxLevel - The maximum level; defaults to CodeRage\Log::DEBUG
     */
    public static function getCount($options)
    {
        $options +=
            [
                'tag' => null,
                'minLevel' => Log::CRITICAL,
                'maxLevel' => Log::DEBUG
            ];
        if ($options['maxLevel'] < $options['minLevel'])
            throw new
                \CodeRage\Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' => 'Maximum level is lower than minimum level'
                ]);
        $result = 0;
        for ($z = $options['minLevel']; $z <= $options['maxLevel']; ++$z)
            $result += isset($options['tag']) ?
                ( isset(self::$countByTag[$options['tag']][$z]) ?
                      self::$countByTag[$options['tag']][$z] :
                      0 ) :
                ( isset(self::$count[$z]) ?
                      self::$count[$z] :
                      0 );
        return $result;
    }

    /**
     * @var array
     */
    private static $count = [];

    /**
     * @var array
     */
    private static $countByTag = [];
}
