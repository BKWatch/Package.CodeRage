<?php

/**
 * Defines the class CodeRage\Util\Version
 *
 * File:        CodeRage/Util/Version.php
 * Date:        Wed Aug 10 20:20:22 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Validates and compares version strings
 */
final class Version {

    /**
     * Pattern version strings must match
     *
     * @var string
     */
    const MATCH_VERSION = '/^(?:(\d{4}-\d{2}-\d{2})(?:-([abdu])(\d+))?|dev)$/';

    /**
     * Date used instead of "dev" for version comparison
     *
     * @var string
     */
    const DEV_DATE = '9999-99-99';

    /**
     * Throws an exception if the given version string is malformed
     *
     * @param string $version The version string
     */
    public static function validate($version)
    {
        Args::check($version, 'string', 'version');
        if (!preg_match(self::MATCH_VERSION, $version)) {
            if (!empty($version)) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid version: $version"
                    ]);
            } else {
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => 'Missing version'
                    ]);
            }
        }
    }

    /**
     * Returns an integer that is less than 0, 0, or greated than zero
     * depending on whether the left-hand version string is less than, equal to,
     * or greater than the right-hand version string
     *
     * @param string $lhs
     * @param string $rhs
     */
    public static function compare($lhs, $rhs)
    {
        $matchLeft = $matchRight = null;
        if ($lhs == 'dev')
            $lhs = self::DEV_DATE;
        if ($rhs == 'dev')
            $rhs = self::DEV_DATE;
        if (preg_match(self::MATCH_VERSION, $lhs, $matchLeft)) {
            if (!isset($matchLeft[2])) {
                $matchLeft[2] = self::phase('r');
                $matchLeft[3] = 1;
            } else {
                $matchLeft[2] = self::phase($matchLeft[2]);
                $matchLeft[3] = (int)$matchLeft[3];
            }
        } else {
            if (!empty($lhs)) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid version: $lhs"
                    ]);
            } else {
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => 'Missing left-hand version'
                    ]);
            }
        }
        if (preg_match(self::MATCH_VERSION, $rhs, $matchRight)) {
            if (!isset($matchRight[2])) {
                $matchRight[2] = self::phase('r');
                $matchRight[3] = 1;
            } else {
                $matchRight[2] = self::phase($matchRight[2]);
                $matchRight[3] = (int)$matchRight[3];
            }
        } else {
            if (!empty($rhs)) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid version: $rhs"
                    ]);
            } else {
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => 'Missing right-hand version'
                    ]);
            }
        }
        array_shift($matchLeft);
        array_shift($matchRight);
        return self::lexCompare($matchLeft, $matchRight);
    }

    /**
     * Takes one of the phase codes "d", "a", "b", "r", or "u" and returns
     * an integer representing its position in the development cycle
     *
     * @param string $phase
     * @return int
     */
    private static function phase($phase)
    {
        switch ($phase) {
        case 'd': return 1;
        case 'a': return 2;
        case 'b': return 3;
        case 'r': return 4;
        case 'u':
        default:
            return 5;
        }
    }

    /**
     * Implements lexicogrphic comparison of equal-length arrays
     *
     * @param array $lhs
     * @param array $rhs
     */
    private static function lexCompare($lhs, $rhs)
    {
        for ($z = 0, $n = count($lhs); $z < $n; ++$z) {
            if ($lhs[$z] < $rhs[$z]) {
                return -1;
            }
            if ($lhs[$z] > $rhs[$z]) {
                return 1;
            }
        }
        return 0;
    }
}
