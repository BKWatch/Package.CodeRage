<?php

/**
 * Defines the interface CodeRage\Build\Dialog
 *
 * File:        CodeRage/Build/Dialog.php
 * Date:        Mon Feb 25 12:25:33 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Obtains input from the user.
 */
interface Dialog {

    /**
     * Obtains input from the user.
     *
     * @param string $label The text to display to the user.
     * @return mixed The value, if any, obtained from the user.
     * @throws CodeRage\Error if an error occurs
     */
    function query($label);

    /**
     * Returns the maximum number of times query() should be called to fulfill a
     * single request for information.
     *
     * @return int
     */
    function maxQueries();
}
