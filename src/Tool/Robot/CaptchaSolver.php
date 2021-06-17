<?php

/**
 * Defines the interface CodeRage\Tool\Robot\CaptchaSolver
 *
 * File:        CodeRage/Tool/Robot/CaptchaSolver.php
 * Date:        Mon Jun 14 01:00:04 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Robot;

use CodeRage\Error;

/**
 * Interface for components that solve CAPTCHA challenges
 */
interface CaptchaSolver {

    /**
     * Returns true if this instance can solve the CAPTCHA challenge associated
     * with the current form of the given robot
     *
     * @param CodeRage\Tool\Tool $robot A robot
     * @return boolean
     */
    public function canSolve(Tool $robot): bool;

    /**
     * Returns the solution to the CAPTCHA challenge associated with the current
     * form of the given robot. The implementation may assume that canSolve()
     * will be called before solve() and that the most recent call to canSolve()
     * relates to the same CAPTCHA challenge as the call to solve().
     *
     * @param CodeRage\Tool\Tool $robot A robot
     * @return array An associative array with keys among:
     *     fields - An associative array mapping form field names to strings or
     *       lists of strings (optional)
     *     headers - An associative array of HTTP headers (optional)
     *     metadata - An associative array of additional data obtained during
     *       CAPTCHA solving (optional)
     * @throws Exception if a solution could not be found
     */
    public function solve(Tool $robot): array;
}
