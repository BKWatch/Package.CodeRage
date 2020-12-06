<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\Notlike_
 *
 * File:        CodeRage/WebService/Search/Operation/Notlike_.php
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
 * Represents the "notlike" operation
 */
final class Notlike_ extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\Notlike_
     */
    public function __construct()
    {
        parent::__construct('notlike', self::FLAG_TEXTUAL, 'NOT LIKE');
    }

    public function translate(Field $field, $value, \CodeRage\Db $db)
    {
        $sql = $field->definition() . " NOT LIKE %s ESCAPE '\\\\'";
        return [$sql, [self::transformWildcards($value)]];
    }
}
