<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Datetime_
 *
 * File:        CodeRage/WebService/Search/Type/Datetime.php
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
 * Represents the datetime type
 */
final class Datetime_ extends \CodeRage\WebService\Search\BasicType {

    public function __construct()
    {
        parent::__construct(
            'datetime', 'int', 'string',
            self::FLAG_DISTINGUISHED | self::FLAG_ORDERED
        );
    }

    public function toInternal($value)
    {
        if (!Args::check($value, 'datetime', 'datetime', true))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid datetime: ' .
                        Error::formatValue($value)
                ]);
        return (int) (new \DateTime($value))->format('U');
    }

    public function toExternal($value)
    {
        return (new \DateTime(null, new \DateTimeZone('UTC')))
                   ->setTimestamp($value)
                   ->format(DATE_W3C);
    }
}
