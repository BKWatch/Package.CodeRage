<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\Match_
 *
 * File:        CodeRage/WebService/Search/Operation/Match_.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search\Operation;

use CodeRage\WebService\Search\Field;

/**
 * Represents the "match" operation
 */
final class Match_ extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\Match_
     */
    public function __construct()
    {
        parent::__construct('match', self::FLAG_TEXTUAL, 'REGEXP');
    }

    public function translate(Field $field, $value, \CodeRage\Db $db)
    {
        $sql = $field->definition() . ' REGEXP %s';
        return [$sql, [self::transformRegex($value)]];
    }
}
