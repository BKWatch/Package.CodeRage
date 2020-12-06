<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\Le_
 *
 * File:        CodeRage/WebService/Search/Operation/Le_.php
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
 * Represents the "le" operation
 */
final class Le_ extends \CodeRage\WebService\Search\BasicOperation {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\Le_
     */
    public function __construct()
    {
        parent::__construct('le', self::FLAG_ORDERED, '<=');
    }
}
