<?php

/**
 * Defines the class CodeRage\Tool\Template\Regex
 *
 * File:        CodeRage/Tool/Template/Regex.php
 * Date:        Fri Jan 12 16:39:25 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Template;

use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Extracts structured data from HTML using regular expressions
 */
class Regex extends \CodeRage\Tool\Template {

    /**
     * @var string
     */
    const MATCH_PARAM = '/%%([_A-Z][_A-Z0-9]*)(?::(.*?))?%%/i';

    /**
     * Constructs a CodeRage\Tool\Template\Regex
     *
     * @param string $definition A regular expression, without delimiters, with
     *   embedded placeholders of the form "%%PARAM%%" or "%%PARAM:REGEX%%",
     *   where PARAM is a parameter name and REGEX is a regular expression; all
     *   parenthesized sub-expressions must use the non-capturing syntax
     *   "(?:...)"
     */
    public function __construct($definition)
    {
        Args::check($definition, 'string', 'template definition');
        $match = null;
        if ( preg_match_all(
                self::MATCH_PARAM,
                $definition,
                $match,
                PREG_PATTERN_ORDER ) === false )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARMETER',
                    'details' =>
                        "Failed extracting parameter names from '$definition'"
                ]);
        }
        $params = [];
        foreach ($match[1] as $i => $n) {
            if (!isset($params[$n]))
                $params[$n] = [];
            $params[$n][] = $i;
        }
        $pattern =
            preg_replace_callback(
                self::MATCH_PARAM,
                function($match)
                {
                    $inner = isset($match[2]) ? $match[2] : '.*?';
                    return "\s*($inner)\s*";
                },
                $definition
            );
        if ($pattern === null)
            throw new
                Error([
                    'status' => 'INVALID_PARMETER',
                    'details' =>
                        "Failed processing template definition: $definition"
                ]);
        $pattern = \CodeRage\Text\Regex::delimit('``iu', $pattern);
        try {
            Args::check($pattern, 'regex', 'xxx');
        } catch (Error $e) {
            if ($e->status() == 'INVALID_PARAMETER')
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Invalid regular expression syntax in '$definition'"
                    ]);
             throw $e;
        }
        parent::__construct();
        $this->params = $params;
        $this->pattern = $pattern;
    }

    public final function apply($html)
    {
        $match = null;
        if ( preg_match_all(
                 $this->pattern,
                 $html,
                 $match,
                 PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) === false )
        {

            return [];

            // Throwing an exception is a more appropriate response if the
            // pattern doesn't match, but the PHP code translated from Perl
            // is not prepared to handle exceptions thrown here
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' => 'Failed applying template'
                ]);
        }
        $result = [];
        foreach ($match as $m) {
            $row = [];
            foreach ($this->params as $name => $pos) {
                foreach ($pos as $i)
                    if (isset($m[$i + 1]) && $m[$i + 1][1] != -1)
                        $row[$name] = $m[$i + 1][0];
                if (!isset($row[$name]))
                    $row[$name] = null;
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * An associative array mapping template parameter names to the lists of
     * the integral positions at which they appear in the template definition
     *
     * @var array
     */
    private $params;

    /**
     * The regular expression used to extract data, constructed from the
     * template definition
     *
     * @var string
     */
    private $pattern;
}
