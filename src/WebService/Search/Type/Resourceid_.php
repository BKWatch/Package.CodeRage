<?php

/**
 * Defines the class CodeRage\WebService\Search\Type\Resouceid_
 *
 * File:        CodeRage/WebService/Search/Type/Resourceid.php
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
 * Represents the resourceid type
 */
final class Resourceid_ extends \CodeRage\WebService\Search\BasicType {

    /**
     * Constructs a CodeRage\WebService\Search\Type\Resourceid_
     *
     * @param array $options The options array; supports the following options:
     *     name - The data type name
     *     abbrev - The short form of the type name, for use in resource IDs of
     *       the form type-xxxxxxxx
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'name', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'abbrev', 'string', [
            'default' => $options['name']
        ]);
        parent::__construct(
            $options['name'], 'int', 'string',
            self::FLAG_DISTINGUISHED | self::FLAG_UNSORTABLE
        );
        $this->abbrev = $options['abbrev'];
    }

    public function toInternal($value)
    {
        return \CodeRage\Access\ResourceId::decode($value, $this->abbrev, $this->name());
    }

    public function toExternal($value)
    {
        return \CodeRage\Access\ResourceId::encode($value, $this->abbrev);
    }

    /**
     * Returns an instance of CodeRage\WebService\Search\Type\Resourceid_
     * constructed from the given list of strings; used to support the syntax
     * "resourceid[name]" and "resourceid[name,abrrev]"
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
                    'details' => "Missing parameters to type 'resourceid'"
                ]);
        if (count($params) > 2)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Too many parameters to type 'resourceid'"
                ]);
        return new
            Resourceid_([
                'name' => $params[0],
                'abbrev' => isset($params[1]) ?
                    $params[1] :
                    $params[0]
            ]);
    }

    /**
     * @var string
     */
    private $abbrev;
}
