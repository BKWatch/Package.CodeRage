<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Decimal_
 *
 * File:        CodeRage/WebService/Search/Type/Decimal.php
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
 * Represents the decimal type
 */
final class Decimal_ extends \CodeRage\WebService\Search\BasicType {

    /**
     * @var string
     */
    const MATCH_DECIMAL = '/^[-+]?(\.[0-9]+|(0|[1-9][0-9]*)(\.[0-9]+)?)$/';

    public function __construct()
    {
        parent::__construct('decimal', 'string', 'string', self::FLAG_ALL);
    }

    public function toInternal($value)
    {
        if (!preg_match(self::MATCH_DECIMAL, $value))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid decimal value: ' .
                        Error::formatValue($value)
                ]);
        return $value;
    }
}
