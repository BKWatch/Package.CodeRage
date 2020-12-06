<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\String_
 *
 * File:        CodeRage/WebService/Search/Type/String_.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search\Type;

/**
 * Represents the string type
 */
final class String_ extends \CodeRage\WebService\Search\BasicType {
    public function __construct()
    {
        parent::__construct('string', 'string', 'string', self::FLAG_ALL);
    }
}
