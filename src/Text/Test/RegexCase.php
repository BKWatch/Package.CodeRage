<?php

/**
 * Defines the class CodeRage\Text\Test\RegexCase
 *
 * File:        CodeRage/Text/Test/RegexCase.php
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

final class RegexCase extends \CodeRage\Test\Case_ {

    public function __construct($patternIndex, $subjectIndex, $forceOrCaptures)
    {
        $foc = $forceOrCaptures === true ?
            'forced matched' :
            ( $forceOrCaptures === false ?
                  'null return' :
                  'captures ' . join(',', $forceOrCaptures) );
        parent::__construct(
            "pattern-$patternIndex-subject-$subjectIndex-" .
                preg_replace('/\W/', '-', $foc),
            "Tests match() with pattern $patternIndex, subject $subjectIndex " .
                "and $foc"
        );
        $this->patternIndex = $patternIndex - 1;
        $this->subjectIndex = $subjectIndex - 1;
        $this->forceOrCaptures =
                is_array($forceOrCaptures) &&
                count($forceOrCaptures) == 1 ?
            $forceOrCaptures[0] :
            $forceOrCaptures;
    }

    protected function doExecute($params)
    {
        static $patterns;
        if ($patterns === null) {
            $patterns = RegexSuite::PATTERNS;
            foreach ($patterns as &$p) {
                $matches = [];
                foreach ($p['matches'] as $s => $m)
                    $matches[] = [$s, $m];
                $p['matches'] = $matches;
            }
        }

        // Perform match
        $pattern = $patterns[$this->patternIndex]['pattern'];
        $captures = $patterns[$this->patternIndex]['captures'];
        list($subject, $matches) =
            $patterns[$this->patternIndex]['matches'][$this->subjectIndex];
        $actual = Regex::getMatch($pattern, $subject, $this->forceOrCaptures);

        // Check result
        $expected = null;
        $success = !empty($matches);
        if ($success) {
            if (is_bool($this->forceOrCaptures)) {
                $expected = $matches;
                for ($i = 1, $n = substr_count($pattern, '('); $i <= $n; ++$i)
                    if (!isset($expected[$i]))
                        $expected[$i] = null;
                $hasMatch = false;
                foreach (array_reverse($captures) as $c) {
                    if (isset($expected[$c]))
                        $hasMatch = true;
                    if ($hasMatch && !isset($expected[$c]))
                        $expected[$c] = null;
                }
            } else {
                $expected = $matches[$this->forceOrCaptures] ?? null;
            }
        } else {
            if ($this->forceOrCaptures === false) {
                $expected = null;
            } elseif ($this->forceOrCaptures === true) {
                $expected = array_fill(0, substr_count($pattern, '(') + 1, null);
            } else {
                $expected = $matches[$this->forceOrCaptures] ?? null;
            }
        }
        if (is_array($actual))
            ksort($actual);
        if (is_array($expected))
            ksort($expected);
        Assert::equal($actual, $expected);
    }

    /**
     * @var int
     */
    private $patternIndex;

    /**
     * @var int
     */
    private $subjectIndex;

    /**
     * @var mixed
     */
    private $forceOrCaptures;
}
