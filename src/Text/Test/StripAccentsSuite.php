<?php

/**
 * Defines the class CodeRage\Text\Test\StripAccentsSuite
 * 
 * File:        CodeRage/Text/Test/StripAccentsSuite.php
 * Date:        Wed Feb 21 14:56:19 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Text\Test;

use CodeRage\Test\Assert;

/**
 * @ignore
 */

/**
 * Test suite for the function CodeRage\Text\stripAccents()
 */
class StripAccentsSuite extends \CodeRage\Test\Suite {

    /**
     * File containing English words with diacritics
     *
     * @var string
     */
    const WORDS_WITH_DIACRITICS = 'words-with-diacritics.csv';

    /**
     * Constructs an instance of CodeRage\Text\Test\StripAccentsSuite
     */
    public function __construct()
    {
        parent::__construct(
            "stripAccents Test Suite",
            "Tests the function CodeRage\Text\stripAccents()"
        );
    }

    protected function suiteInitialize()
    {
        // Construct cases
        $path = __DIR__ . '/' . self::WORDS_WITH_DIACRITICS;
        $file = fopen($path, 'r');
        $testCount = 0;
        while (($data = fgetcsv($file)) !== false) {
            $testCount++;
            $this->add(new StripAccentsCase($data[0], $data[1]));
        }
    }

}
