<?php

/**
 * Defines the class CodeRage\Tool\Test\CleanHtmlSuite
 * 
 * File:        CodeRage/Tool/Test/CleanHtmlSuite.php
 * Date:        Wed Feb 21 14:56:19 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

use CodeRage\Test\Assert;

/**
 * @ignore
 */

/**
 * Test suite for cleanHtml
 */
class CleanHtmlSuite extends \CodeRage\Test\Suite {

    /**
     * File containing test htmls
     *
     * @var string
     */
    const CLEAN_HTML = 'clean-html.csv';

    /**
     * Constructs an instance of CodeRage\Tool\Test\cleanHtmlSuite
     */
    public function __construct()
    {
        parent::__construct(
            "cleanHtml Test Suite",
            "Tests the class CodeRage\Tool\cleanHtml"
        );
    }

    protected function suiteInitialize()
    {
        // Construct cases
        $path = __DIR__ . '/' . self::CLEAN_HTML;
        $file = fopen($path, 'r');
        $testCount = 0;
        while (($data = fgetcsv($file, 0, "\t")) !== false) {
            $testCount++;
            $this->add(
                new cleanHtmlCase([
                    'testHtml' => $data[0],
                    'expected' => $data[1],
                    'testName' => "test$testCount"
                ]));
        }
    }

}
