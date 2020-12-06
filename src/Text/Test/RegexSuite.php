<?php

/**
 * Defines the class CodeRage\Text\Test\RegexSuite
 * 
 * File:        CodeRage/Text/Test/RegexSuite.php
 * Date:        Wed Jun 26 20:33:54 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Text\Test;

use drupol\phpermutations\Generators\Permutations;
use CodeRage\Test\Assert;
use CodeRage\Text\Regex;

/**
 * Test suite for CodeRage\Text\Test\Regex
 */
class RegexSuite extends \CodeRage\Test\ReflectionSuite  {

    /**
     * @var array
     */
    const PATTERNS =
        [
            [
                'pattern' => '/A B C/',
                'captures' => [],
                'matches' =>
                    [
                        'A B C D E F' => [0 => 'A B C'],
                        'D E F' => []
                    ]
            ],
            [
                'pattern' => '/(A) (B) (C)/',
                'captures' => [1, 2, 3],
                'matches' =>
                    [
                        'A B C D E F' =>
                            [0 => 'A B C', 1 => 'A', 2 => 'B', 3 => 'C'],
                        'D E F' => []
                    ]
            ],
            [
                'pattern' => '/(?<A>A) (B) (?<C>C)/',
                'captures' => [1, 2, 3, 'A', 'C'],
                'matches' =>
                    [
                        'A B C D E F' =>
                            [
                                0 => 'A B C',
                                1 => 'A',
                                2 => 'B',
                                3 => 'C',
                                'A' => 'A',
                                'C' => 'C'
                            ],
                        'D E F' => []
                    ]
            ],
            [
                'pattern' => '/(?<A>A)(?: B (?<C>C))? D(?: E (?<F>F))?/',
                'captures' => [1, 2, 3, 'A', 'C', 'F'],
                'matches' =>
                    [
                        'A B C D E F' =>
                            [
                                0 => 'A B C D E F',
                                1 => 'A',
                                2 => 'C',
                                3 => 'F',
                                'A' => 'A',
                                'C' => 'C',
                                'F' => 'F'
                            ],
                        'A B C D' =>
                            [
                                0 => 'A B C D',
                                1 => 'A',
                                2 => 'C',
                                'A' => 'A',
                                'C' => 'C'
                            ],
                        'A D E F' =>
                            [
                                0 => 'A D E F',
                                1 => 'A',
                                3 => 'F',
                                'A' => 'A',
                                'F' => 'F'
                            ],
                        'A D' =>
                            [
                                0 => 'A D',
                                1 => 'A',
                                'A' => 'A'
                            ],
                        'D E F' => []
                    ]
            ]
        ];

    /**
     * Constructs an instance of CodeRage\Util\Test\Base62
     */
    public function __construct()
    {
        parent::__construct(
            'CodeRage.Text.Regex',
            'Tests the class CodeRage\Text\Regex'
        );
    }

    public function testHasMatchInvalidPattern1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::hasMatch(['hello'], '');
    }

    public function testHasMatchInvalidPattern2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::hasMatch('/hello', '');
    }

    public function testHasMatchInvalidSubject()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::hasMatch('/hello/', 9.99999);
    }

    public function testHasMatchInvalidOffset()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::hasMatch('/hello/', '', 9.99999);
    }

    public function testHasMatch1()
    {
        Assert::isTrue(Regex::hasMatch('/hello/', 'hello'));
    }

    public function testHasMatch2()
    {
        Assert::isFalse(Regex::hasMatch('/hello/', 'hello', 1));
    }

    public function testGetMatchInvalidPattern1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getMatch(['hello'], '');
    }

    public function testGetMatchInvalidPattern2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getMatch('/hello', '');
    }

    public function testGetMatchInvalidSubject()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getMatch('/hello/', 9.99999);
    }

    public function testGetMatchInvalidOffset()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getMatch('/hello/', '', true, 9.99999);
    }

    public function testGetMatchWithOffset()
    {
        Assert::isTrue(Regex::getMatch('/hello/', 'hello', false, 1) === null);
    }

    public function testGetAllMatchesInvalidPattern1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getAllMatches(['hello'], '', PREG_SET_ORDER);
    }

    public function testGetAllMatchesInvalidPattern2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getAllMatches('/hello', '', PREG_SET_ORDER);
    }

    public function testGetAllMatchesInvalidSubject()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getAllMatches('/hello/', 9.99999, PREG_SET_ORDER);
    }

    public function testGetAllMatchesInvalidFlags()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getAllMatches('/hello/', '', true);
    }

    public function testGetAllMatchesInvalidOffset()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::getAllMatches('/hello/', '', 0, 9.99999);
    }

    public function testGetAllMatchesFailedMatch1()
    {
        Assert::equal(
            Regex::getAllMatches('/hello/', 'goodbye', PREG_SET_ORDER),
            null
        );
    }

    public function testGetAllMatchesFailedMatch2()
    {
        Assert::equal(
            Regex::getAllMatches('/hello/', 'hello', PREG_SET_ORDER, 1),
            null
        );
    }

    public function testGetAllMatchesSuccessfulMatch()
    {
        Assert::equal(
            Regex::getAllMatches('/\b[abc]\b/', 'a d c b', PREG_SET_ORDER),
            [['a'], ['c'], ['b']]
        );
    }

    public function testDelimitInvalidTemplate1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('', 'hello');
    }

    public function testDelimitInvalidTemplate2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('/', 'hello');
    }

    public function testDelimitInvalidTemplate3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('/#', 'hello');
    }

    public function testDelimitInvalidTemplate4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('(>', 'hello');
    }

    public function testDelimitInvalidTemplate5()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('{>', 'hello');
    }

    public function testDelimitInvalidTemplate6()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('[>', 'hello');
    }

    public function testDelimitInvalidTemplate7()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('<)', 'hello');
    }

    public function testDelimitInvalidTemplate8()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('/#i', 'hello');
    }

    public function testDelimitInvalidTemplate9()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('(>i', 'hello');
    }

    public function testDelimitInvalidTemplate10()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('{>i', 'hello');
    }

    public function testDelimitInvalidTemplate11()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('[>i', 'hello');
    }

    public function testDelimitInvalidTemplate12()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('<)i', 'hello');
    }

    public function testDelimitInvalidTemplate13()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('//T', 'hello');
    }

    public function testDelimitInvalidTemplate14()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('//iTJ', 'hello');
    }

    public function testDelimitInvalidPattern1()
    {
        $this->setExpectedStatusCode('MISSING_PARAMETER');
        Regex::delimit('//');
    }

    public function testDelimitInvalidPattern2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('//', 9.9999);
    }

    public function testDelimitInvalidPattern3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Regex::delimit('//', 'hello', 9.9999);
    }

//     public function testDelimitInvalidPattern4()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         Regex::delimit('//', '/');
//     }

//     public function testDelimitInvalidPattern5()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         Regex::delimit('//', 'hello', '/');
//     }

    public function testDelimit1()
    {
        Assert::equal(
            Regex::delimit('//', 'hello'),
            '/hello/'
        );
    }

    public function testDelimit2()
    {
        Assert::equal(
            Regex::delimit('//i', 'hello'),
            '/hello/i'
        );
    }

    public function testDelimit3()
    {
        Assert::equal(
            Regex::delimit('//', 'hello', 'goodbye'),
            '/hello|goodbye/'
        );
    }

    public function testDelimit4()
    {
        Assert::equal(
            Regex::delimit('//i', 'hello', 'goodbye'),
            '/hello|goodbye/i'
        );
    }

    public function testDelimit5()
    {
        Assert::equal(
            Regex::delimit('{}iJ', 'hello', 'goodbye'),
            '{hello|goodbye}iJ'
        );
    }

    public function testReplaceDelimiter1()
    {
        Assert::isTrue(preg_match(Regex::delimit('//i', 'hel/lo'), 'HEL/LO'));
    }

    public function testReplaceDelimiter2()
    {
        Assert::isTrue(preg_match(Regex::delimit('##i', 'hel#lo'), 'HEL#LO'));
    }

    public function testReplaceDelimiter3()
    {
        Assert::isTrue(preg_match(Regex::delimit('@@i', 'hel@lo'), 'HEL@LO'));
    }

    public function testReplaceDelimiter4()
    {
        Assert::isTrue(preg_match(Regex::delimit('%%i', 'hel%lo'), 'HEL%LO'));
    }

    public function testReplaceDelimiter5()
    {
        Assert::isTrue(preg_match(Regex::delimit('~~i', 'hel~lo'), 'HEL~LO'));
    }

    public function testReplaceDelimiter6()
    {
        Assert::isTrue(preg_match(Regex::delimit('``i', 'hel`lo'), 'HEL`LO'));
    }

    public function testReplaceDelimiter7()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`hello'), '/#@%~`HELLO')
        );
    }

    public function testReplaceDelimiter8()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`hel\\/lo'), '/#@%~`HEL/LO')
        );
    }

    public function testReplaceDelimiter9()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`hel\\\\/lo'), '/#@%~`HEL\\/LO')
        );
    }

    public function testReplaceDelimiter10()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`he\\nl/lo'), "/#@%~`HE\nL/LO")
        );
    }

    public function testReplaceDelimiter11()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`he\\\\nl/lo'), "/#@%~`HE\\nL/LO")
        );
    }

    public function testReplaceDelimiter12()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`he\\w/lo'), "/#@%~`HEL/LO")
        );
    }

    public function testReplaceDelimiter13()
    {
        Assert::isTrue(
            preg_match(Regex::delimit('//i', '/#@%~`he\\\\w/lo'), "/#@%~`HE\\w/LO")
        );
    }



    protected function suiteInitialize()
    {
        return;
        $patterns = RegexSuite::PATTERNS;
        $pIndex = 1;
        foreach ($patterns as $patternSpec) {
            $pattern = $patternSpec['pattern'];
            $captures = $patternSpec['captures'];
            $sIndex = 1;
            foreach ($patternSpec['matches'] as $subject => $matches) {
                $this->add(new RegexCase($pIndex, $sIndex, false));
                $this->add(new RegexCase($pIndex, $sIndex, true));
                for ($i = 1, $n = count($captures); $i <= $n; ++$i) {
                    $j = 0;
                    foreach ((new Permutations($captures, $i))->generator() as $p) {
                        $this->add(new RegexCase($pIndex, $sIndex, $p));
                         if (++$j == 4)
                             break;
                    }
                }
                ++$sIndex;
            }
            ++$pIndex;
        }
    }
}
