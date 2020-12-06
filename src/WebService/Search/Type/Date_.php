<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Date_
 *
 * File:        CodeRage/WebService/Search/Type/Date.php
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
use CodeRage\Util\Args;


/**
 * Represents the date type
 */
final class Date_ extends \CodeRage\WebService\Search\BasicType {

    public function __construct()
    {
        parent::__construct('date', 'string', 'string', self::FLAG_ALL);
    }

    public function toInternal($value)
    {
        if (!Args::check($value, 'date', 'date', true))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid date: ' . Error::formatValue($value)
                ]);
        return $value;
    }
}
