<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\Ne_
 *
 * File:        CodeRage/WebService/Search/Operation/Ne_.php
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
 * Represents the "ne" operation
 */
final class Ne_ extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\Ne_
     */
    public function __construct()
    {
        parent::__construct('ne', self::FLAG_DISTINGUISHED, '!=');
    }
}
