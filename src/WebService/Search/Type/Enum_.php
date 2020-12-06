<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Enum_
 *
 * File:        CodeRage/WebService/Search/Type/Enum.php
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
 * Represents the enum type
 */
final class Enum_ extends \CodeRage\WebService\Search\BasicType {

    /**
     * @var string
     */
    const MATCH_ENUMERATOR =
        '/^(?:([_a-zA-Z][_a-zA-Z0-9]+):)?(-?(?:0|[1-9][0-9]*))$/';

    /**
     * Constructs a CodeRage\WebService\Search\Type\Enum_
     *
     * @param array $options The options array; supports the following options:
     *     name - The data type name
     *     values - An associative array mapping strings to ints
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'name', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'values', 'map[int]', [
            'required' => true
        ]);
        $name = $options['name'];
        parent::__construct($name, 'int', 'string', self::FLAG_DISTINGUISHED);
        foreach ($options['values'] as $n => $v) {
            if (isset($this->toExternal[$v]))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETERS',
                        'details' =>
                            "More than one enumerator maps to value '$v'"
                    ]);
             $this->toInternal[$n] = $v;
             $this->toExternal[$v] = $n;
        }
    }

    public function toInternal($value)
    {
        if (!isset($this->toInternal[$value]))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'Invalid ' . $this->name() . ': ' . $value
                ]);
        return $this->toInternal[$value];
    }

    public function toExternal($value)
    {
        if (!isset($this->toExternal[$value]))
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' =>
                        "Can't convert $value to a member of the enumeration " .
                        $this->name()
                ]);
        return $this->toExternal[$value];
    }

    /**
     * Returns an instance of CodeRage\WebService\Search\Type\Enum_
     * constructed from the given list of strings; used to support the syntax
     * "enum[name,V1,V2,V3,...]" where each value V is an integer or a string
     * of the form "N:V", where N is a value name and V is an integer
     *
     * @param array $params A list of strings
     */
    public static function fromParameterList(array $params)
    {
        Args::check($params, 'list[string]', 'parameters');
        if (empty($params))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Missing enumeration name'
                ]);
        $name = array_shift($params);
        if (empty($params))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Missing values for enumeration '$name'"
                ]);
        $values = [];
        foreach ($params as $p) {
            $match = null;
            if (!preg_match(self::MATCH_ENUMERATOR, $p, $match))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Malformed enumerator specifier '$p' for " .
                            "enumeration '$name'"
                    ]);
            $n = !empty($match[1]) ?
                $match[1] :
                $match[2];
            $v = (int) $match[2];
            if (isset($values[$n])) {
                $values = json_encode($params);
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Invalid enumeration $values: the enumerator " .
                            "'$n' occurs multiple times"
                    ]);
            }
            $values[$n] = $v;
        }
        return new Enum_(['name' => $name, 'values' => $values]);
    }

    /**
     * @var array
     */
    private $toInternal = [];

    /**
     * @var array
     */
    private $toExternal = [];
}
