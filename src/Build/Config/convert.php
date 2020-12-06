<?php

/**
 * Defines the function CodeRage\Build\Config\convert.
 *
 * File:        CodeRage/Build/Config/convert.php
 * Date:        Fri Jan 25 15:43:07 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

use Exception;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');

/**
 * Converts the given value to the given type, if it is a string.
 *
 * @param mixed $value
 * @param int $type One of the constants CodeRage\Build\XXX, where XXX
 * is BOOLEAN, INT, STRING, or FLOAT.
 * @return The result of converting $value to type $type, if $value is a string,
 * and $value otherwise.
 * @throws Exception if $value cannot be converted
 */
function convert($value, $type)
{
    if (is_string($value)) {
        switch ($type) {
        case \CodeRage\Build\BOOLEAN:
            $value = strtolower($value);
            if ($value == 'true' || $value == 'yes' || $value == '1') {
                return true;
            } elseif ($value == 'false' || $value == 'no' || $value == '0') {
                return false;
            } else {
                throw new
                    Exception(
                        "Cannot convert value: expected boolean; found '$value'"
                    );
            }
        case \CodeRage\Build\INT:
            if (!is_numeric($value) || strval(intval($value)) != $value)
                throw new
                    Exception(
                        "Cannot convert value: expected integer; found '$value'"
                    );
            return intval($value);
        case \CodeRage\Build\FLOAT:
            if (!is_numeric($value))
                throw new
                    Exception(
                        "Cannot convert value: expected floating point value;" .
                        " found '$value'"
                    );
            return floatval($value);
        default:
            return $value;
        }
    } else {
        return $value;
    }
}
