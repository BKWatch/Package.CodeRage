<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\Lt_
 *
 * File:        CodeRage/WebService/Search/Operation/Lt_.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search\Operation;

/**
 * Represents the "lt" operation
 */
final class Lt_ extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\Lt_
     */
    public function __construct()
    {
        parent::__construct('lt', self::FLAG_ORDERED, '<');
    }
}
