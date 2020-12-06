<?php

/**
 * Defines the class CodeRage\WebService\Search\BasicType
 *
 * File:        CodeRage/WebService/Search/BasicType.php
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
 * Simple implementation of CodeRage\WebService\Search\Type
 */
class BasicType implements Type {

    /**
     * @var array
     */
    const INTERNAL_TYPES = [ 'int' => 1, 'float' => 1, 'string' => 1 ];

    /**
     * @var array
     */
    const EXTERNAL_TYPES =
        [ 'boolean' => 1, 'int' => 1, 'float' => 1, 'string' => 1 ];

    /**
     * Constructs a CodeRage\WebService\Search\BasicType
     *
     * @param string $internal The internal type name
     * @param string $extenal The external type name
     * @param int $flags A bitwise OR of zero or more of the constants FLAG_XXX
     */
    public function __construct($name, $internal, $external, $flags)
    {
        Args::check($name, 'string', 'name');
        Args::check($internal, 'string', 'internal type');
        if (!array_key_exists($internal, self::INTERNAL_TYPES))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Unsupported internal type: $internal"
                ]);
        Args::check($external, 'string', 'external type');
        if ( !array_key_exists($external, self::EXTERNAL_TYPES) &&
             !\CodeRage\Util\Factory::classExists($external) )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Unsupported external type: $external"
                ]);
        }
        Args::check($flags, 'int', 'flags');
        $this->name = $name;
        $this->internal = $internal;
        $this->external = $external;
        $this->flags = $flags;
    }

    public function name() { return $this->name; }

    public function internal() { return $this->internal; }

    public function external() { return $this->external; }

    public function flags() { return $this->flags; }

    public function toInternal($value)
    {
        settype($value, $this->internal);
        return $value;
    }

    public function toExternal($value)
    {
        settype($value, $this->external);
        return $value;
    }

    public function specifier()
    {
        switch ($this->internal) {
        case 'int':
            return 'i';
        case 'float':
            return 'f';
        default:
            return 's';
        }
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $internal;

    /**
     * @var string
     */
    private $external;

    /**
     * @var int
     */
    private $flags;
}
