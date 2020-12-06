<?php

/**
 * Defines the class CodeRage\WebService\Search\BasicOperation
 * 
 * File:        CodeRage/WebService/Search/BasicOperation.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search;

use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Simple implementation of CodeRage\WebService\Search\Operation
 */
class BasicOperation implements Operation {

    /**
     * Constructs a CodeRage\WebService\Search\BasicOperation
     *
     * @param string $name The operation name
     * @param int $flags A bitwise OR of one or more of the constants FLAG_XXX
     * @param string $translation The SQL operation used to translate this
     *   operation into SQL, if any; if not supplied, the method translate()
     *   will throw an exception unless overridden
     */
    public function __construct($name, $flags, $translation = null)
    {
        Args::check($name, 'string', 'name');
        Args::check($flags, 'int', 'flags');
        if ($translation !== null)
            Args::check($translation, 'string', 'translation');
        $count = 0;
        foreach ([self::FLAG_DISTINGUISHED, self::FLAG_ORDERED, self::FLAG_TEXTUAL] as $f)
            if (($flags & $f) != 0)
                ++$count;
        if ($count != 1)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETERS',
                    'details' =>
                        'Flags must include exactly one of the constants ' .
                        'FLAG_DISTINGUISHED, FLAG_ORDERED, and FLAG_TEXTUAL'
                ]);
        $this->name = $name;
        $this->flags = $flags;
        $this->translation = $translation;
    }

    public function name() { return $this->name; }

    public function flags() { return $this->flags; }

    public function translate(Field $field, $value, \CodeRage\Db $db)
    {
        if ($this->translation === null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' => 'No translation available'
                ]);
        $sql = $field->definition() . ' ' . $this->translation;
        $params = [];
        if (($this->flags & self::FLAG_UNARY) == 0) {
            $type = $field->type();
            $textual = ($this->flags & self::FLAG_TEXTUAL) != 0;
            $sql .= ' ' . ($textual ? '%s' : '%' . $type->specifier());
            $params[] = $textual ?
                $this->transformValue($value) :
                $type->toInternal($value);
        }
        return [$sql, $params];
    }

    /**
     * Returns the SQL operation used to translate this operation into SQL, if
     * any
     *
     * @return string
     */
    protected function translation()
    {
        return $this->translation;
    }

    /**
     * Transforms the field value before use as a placeholder replacement
     *
     * @param string $value
     * @return string
     */
    protected function transformValue($value)
    {
        return $value;
    }

    /**
     * Transforms a wildcard expression using the placeholders '*' and '?' into
     * a string suitable for use with the SQL LIKE operator and the escape
     * character "\"
     *
     * @param string $value The wildcard expression
     * @return string The transformed value
     */
    protected static function transformWildcards($value)
    {
        $result = '';
        $esc = false;
        for ($i = 0, $n = strlen($value); $i < $n; ++$i) {
            $c = $value[$i];
            if ($c == '\\') {
                if ($esc) {
                    $result .= '\\\\';
                    $esc = false;
                } else {
                    $esc = true;
                }
            } else {
                if ($esc) {
                    if ($c != '*' && $c != '?')
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Unsupported escape sequence \\$c in " .
                                    "wildcard expression '$value'"
                            ]);
                    $result .= $c;
                    $esc = false;
                } else {
                    if ($c == '*') {
                        $result .= '%';
                    } elseif ($c == '?') {
                        $result .= '_';
                    } elseif ($c == '%' || $c == '_') {
                        $result .= "\\$c";
                    } else {
                        $result .= $c;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Transforms a regular expression for use with the MySQL REGEXP operator
     *
     * @param string $value The regular expression
     * @return string The transformed value
     */
    protected static function transformRegex($value)
    {
        $regex = null;
        try {
            $regex = new \CodeRage\WebService\Regex($value);
        } catch (Error $e) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid regular expression: $value",
                    'inner' => $e
                ]);
        }
        return (new \CodeRage\WebService\Regex($value))->toMySql();
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var string
     */
    private $translation;
}
