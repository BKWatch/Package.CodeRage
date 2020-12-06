<?php

/**
 * Defines the class CodeRage\Text\Test\StripAccentsCase
 * 
 * File:        CodeRage/Text/Test/StripAccentsCase.php
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
use CodeRage\Text;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test case for CodeRage\Text\stripAccents()
 */
class StripAccentsCase extends \CodeRage\Test\Case_ {

    /**
     * Constructs an instance of CodeRage\Text\Test\StripAccentsCase
     *
     * @param string $before An english word or phrase before stripping accents
     * @param string $after An english word or phrase before stripping accents
     */
    public function __construct($before, $after)
    {
        Args::check($before, 'string', 'word');
        Args::check($after, 'string', 'word');
        parent::__construct(
            'test-' . str_replace(' ', '-', $after),
            "Tests stripping accents from $before"
        );
        $this->before = $before;
        $this->after = $after;
    }

    protected function doExecute($params)
    {
        $result = Text::stripAccents($this->before);
        Assert::equal($result, $this->after);
    }

    /**
     * An english word or phrase before stripping accents
     *
     * @var string
     */
    private $before;

    /**
     * TAn english word or phrase before stripping accents
     *
     * @var string
     */
    private $after;
}
