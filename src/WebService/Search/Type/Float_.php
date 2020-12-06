<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Float_
 *
 * File:        CodeRage/WebService/Search/Type/Float.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search\Type;

use CodeRage\Error;


/**
 * Represents the floating-point type
 */
final class Float_ extends \CodeRage\WebService\Search\BasicType {

    /**
     * @var string
     */
    const MATCH_FLOAT = '/^[-+]?(\.[0-9]+|(0|[1-9][0-9]*)(\.[0-9]+)?)$/';

    public function __construct()
    {
        parent::__construct('float', 'float', 'float', self::FLAG_ORDERED);
    }

    public function toInternal($value)
    {
        if (!preg_match(self::MATCH_FLOAT, $value))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid floating-point value: ' .
                        Error::formatValue($value)
                ]);
        return (float) $value;
    }
}
