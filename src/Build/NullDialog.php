<?php

/**
 * Defines the class CodeRage\Build\NullDialog
 *
 * File:        CodeRage/Build/NullDialog.php
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
 * Implementation for CodeRage\Build\Dialog for use by a non-interactive build.
 *
 */
class NullDialog implements Dialog {

    /**
     * Returns null.
     *
     * @param string $label.
     * @return mixed
     */
    function query($label)
    {
       return null;
    }

    /**
     * Returns 1.
     *
     * @return int
     */
    function maxQueries()
    {
        return 1;
    }
}
