<?php

/**
 * Defines the class CodeRage\WebService\Search\Operation\In_
 *
 * File:        CodeRage/WebService/Search/Operation/In_.php
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
 * Represents the "in" operation
 */
final class In_ extends \CodeRage\WebService\Search\Operation\InBase {

    /**
     * Constructs a CodeRage\WebService\Search\Operation\In_
     */
    public function __construct()
    {
        parent::__construct('in', 'IN');
    }
}
