<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Boolean_
 *
 * File:        CodeRage/WebService/Search/Type/Boolean.php
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
 * Represents the boolean type
 */
final class Boolean_ extends \CodeRage\WebService\Search\BasicType {

    /**
     * @var string
     */
    const MATCH_BOOLEAN = '/^(1|0|true|false)$/';

    public function __construct()
    {
        parent::__construct('boolean', 'int', 'boolean', self::FLAG_DISTINGUISHED);
    }

    public function toInternal($value)
    {
        if (!preg_match(self::MATCH_BOOLEAN, $value))
            throw new
                \CodeRage\Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid boolean value: expected 1, 0, true, or ' .
                        'false; found ' . Error::formatValue($value)
                ]);
        return $value == 'true' || $value == '1';
    }
}
