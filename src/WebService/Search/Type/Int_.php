<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Int_
 *
 * File:        CodeRage/WebService/Search/Type/Int_.php
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
 * Represents the integer type
 */
final class Int_ extends \CodeRage\WebService\Search\BasicType {

    /**
     * @var string
     */
    const MATCH_INT = '/^-?(0|[1-9][0-9]*)$/';

    public function __construct()
    {
        parent::__construct('int', 'int', 'int', self::FLAG_ALL);
    }

    public function toInternal($value)
    {
        if (!preg_match(self::MATCH_INT, $value))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid integer: ' . Error::formatValue($value)
                ]);
        return (int) $value;
    }
}
