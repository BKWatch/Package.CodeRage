<?php

/**
 * Defines the class CodeRage\Tool\Test\cleanHtmlCase
 * 
 * File:        CodeRage/Tool/Test/cleanHtmlCase.php
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
use CodeRage\Text;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test case for CodeRage\Tool\cleanHtml()
 */
class cleanHtmlCase extends \CodeRage\Test\Case_ {

    /**
     * Constructs an instance of CodeRage\Tool\Test\cleanHtmlCase
     *
     * @param array $options Supports the following options
     *     testHtml - A HTML for test
     *     expected - Expected HTML
     *     testName - A test case name
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'testHtml', 'string', [
            'label' => 'test html',
            'required' => true
        ]);
        Args::checkKey($options, 'expected', 'string', [
            'label' => 'expected html',
            'required' => true
        ]);
        Args::checkKey($options, 'testName', 'string', [
            'label' => 'test case name',
            'required' => true
        ]);
        parent::__construct(
            $options['testName'],
            "Testing html"
        );
        $this->options = $options;
    }

    protected function doExecute($params)
    {
        $result = Text::htmlToText($this->options['testHtml']);
        Assert::equal(
            $result,
            $this->options['expected']
        );
    }

    /**
     * The options array
     *
     * @var array
     */
    private $options;
}
