<?php

/**
 * Defines the class CodeRage\Sys\Engine\ParameterDescriptor
 *
 * File:        CodeRage/Sys/Engine/ParameterDescriptor.php
 * Date:        Mon Nov 16 21:57:26 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use CodeRage\Sys\Base;

/**
 * Stores information about an event listener parameter
 */
final class ParameterDescriptor extends Base
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'type' => ['type' => 'string', 'required' => true],
        'allowsNull' => ['type' => 'boolean', 'required' => true]
    ];

    /**
     * Constructs an instance of CodeRage\Sys\Engine\ParameterDescriptor
     *
     * @param array $options The options array; supports the following options:
     *     type - The parameter type, as a class name or service ID
     *     allowsNull - true if the parameter accepts null values
     *     validate - true to process and validate options; defaults to true
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    /**
     * Returns the parameter type, as a class name or service ID
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->options['type'];
    }

    /**
     * Returns true if the parameter accepts null values
     *
     * @return string
     */
    public function allowsNull(): string
    {
        return $this->options['allowsNull'];
    }
}
