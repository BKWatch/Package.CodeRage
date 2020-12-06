<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\Notmatch_
 *
 * File:        CodeRage/WebService/Search/Operation/Notmatch_.php
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
 * Represents the "notmatch" operation
 */
final class Notmatch_ extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\Notmatch_
     */
    public function __construct()
    {
        parent::__construct('notmatch', self::FLAG_TEXTUAL, 'NOT REGEXP');
    }

    public function translate(Field $field, $value, \CodeRage\Db $db)
    {
        $sql = $field->definition() . ' NOT REGEXP %s';
        return [$sql, [self::transformRegex($value)]];
    }
}
