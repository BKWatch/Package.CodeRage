<?php

/**
 * Defines the function CodeRage\WebService\validate()
 *
 * File:        CodeRage/WebService/Types.php
 * Date:        Wed Apr  6 22:08:29 MDT 2011
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use CodeRage\Error;

final class Types {

    /**
     * Throws an exception if the given value does not have the expected type
     *
     * @param $value The value
     * @param $type One of the values 'boolean', 'int', 'string', 'date',
     *   'datetime', 'id', 'array', or 'object'
     * @param $label Text to use in error messages
     */
    public static function validate($value, $type, $label)
    {
        $valid = true;
        switch ($type) {
        case 'boolean':
            if ( !is_bool($value) &&
                 $value !== 1 && $value !== 0 &&
                 $value !== 'true' && $value !== 'false' &&
                 $value !== '1' && $value !== '0' )
            {
                $valid = false;
            }
            break;
        case 'int':
            if ( !is_int($value) &&
                 ( !is_string($value) ||
                   !preg_match('/^(0|-?[1-9][0-9]*)$/', $value) ) )
            {
                $valid = false;
            }
            break;
        case 'id':
            if (!preg_match('/^[-a-z]*-[0-9a-f]+$/', $value)) {
                $valid = false;
            }
            break;
        case 'string':
            if (!is_string($value))
                $valid = false;
            break;
        case 'date':
            if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
                $valid = false;
            break;
        case 'datetime':
            $pattern =
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([-+]\d{2}:\d{2})?$/';
            if (!is_string($value) || !preg_match($pattern, $value))
                $valid = false;
            break;
        case 'array':
            if (!is_array($value))
                $valid = false;
            break;
        case 'object':
            if (!is_object($value))
                $valid = false;
            break;
        default:
            throw new Error(['details' => "Invalid type: $type"]);
        }
        if (!$valid) {
            $params = ['status' => 'INVALID_PARAMETER'];
            if ($type == 'id')
                $type = 'object ID';
            $message =
                "Invalid $label: expected $type; found " .
                Error::formatValue($value);
            if (is_scalar($value)) {
                $params['message'] = $message;
            } else {

                // End users should not see print_r output
                $params['message'] =
                    "Invalid $label: expected $type; found " .
                    (is_array($value) ? 'array' : 'object');
                $params['details'] = $message;
            }
            throw new Error($params);
        }
    }
}
