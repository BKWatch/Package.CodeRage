<?php

/**
 * Defines the function CodeRage\Build\Config\equals.
 *
 * File:        CodeRage/Build/Config/equals.php
 * Date:        Thu Jan 01 18:00:49 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

use CodeRage\Build\ProjectConfig;

/**
 * Returns true if the given instances of CodeRage\Build\ProjectConfig have a common
 * set of proeprty names and assign the same value to each property.
 *
 * @param CodeRage\Build\ProjectConfig $lhs
 * @param CodeRage\Build\ProjectConfig $rhs
 * @return boolean
 */
function equals(ProjectConfig $lhs,
    ProjectConfig $rhs)
{
    $lNames = $lhs->propertyNames();
    $rNames = $rhs->propertyNames();
    foreach ($lNames as $n)
        if ( !key_exists($n, $rNames) ||
             $lhs->lookupProperty($n)->value() !==
                 $rhs->lookupProperty($n)->value() )
        {
            return false;
        }
    foreach ($rNames as $n)
        if ( !key_exists($n, $lNames) ||
             $lhs->lookupProperty($n)->value() !==
                 $rhs->lookupProperty($n)->value() )
        {
            return false;
        }
    return true;
}
