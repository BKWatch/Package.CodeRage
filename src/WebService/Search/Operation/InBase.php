<?php

/**
 * Defines the class CodeRage\WebService\Search\Operataion\InBase
 *
 * File:        CodeRage/WebService/Search/Operation/InBase.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search\Operation;

use CodeRage\Error;
use CodeRage\Util\Array_;
use CodeRage\WebService\Search\Field;

/**
 * Base class for CodeRage\WebService\Search\Operataion\In and
 * CodeRage\WebService\Search\Operataion\Notin
 */
class InBase extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\InBase
     *
     * @param string $name The operation name
     * @param string $translation The SQL operation used to translate this
     *   operation into SQL
     */
    public function __construct($name, $translation)
    {
        parent::__construct($name, self::FLAG_DISTINGUISHED, $translation);
    }

    public final function translate(Field $field, $value, \CodeRage\Db $db)
    {
        $mem = fopen('php://memory', 'rw');
        fwrite($mem, $value);
        rewind($mem);
        $values = fgetcsv($mem);
        if ($values === false || count($values) == 1 && $values[0] === null)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid field values for operation 'in': $value"
                ]);
        $sql =
            $field->definition() . ' ' . $this->translation() . ' (' .
            join(',', array_fill(0, count($values), '%s')) . ')';
        $type = $field->type();
        $params =
            Array_::map(function($v) use($type) { return $type->toInternal($v); }, $values);
        return [$sql, $params];
    }
}
