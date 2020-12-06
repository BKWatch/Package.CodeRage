<?php

/**
 * Defines the class CodeRage\Text\Regex
 *
 * File:        CodeRage/Text/Regex.php
 * Date:        Thu Feb 15 17:38:05 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Text;

use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;


/**
 * Container static wrappers for some preg_xxx functions, with some additional
 * features for convenience
 */
final class Regex {

    /**
     * When passed as the third argument to match(), indicates that an array
     * of null values should be returned in the event that the pattern does not
     * match
     *
     * @var boolean
     */
    const FORCE = true;

    /**
     * Equivalent to PREG_PATTERN_ORDER
     *
     * @var int
     */
    const PATTERN = PREG_PATTERN_ORDER;

    /**
     * Equivalent to PREG_SET_ORDER
     *
     * @var int
     */
    const SET = PREG_SET_ORDER;

    /**
     * @var string
     */
    const MATCH_TEMPLATE =
        '/^(?:\(\)|\{\}|\[\]|<>|(?<X>[^({(<])(?P=X))[imsxADSUXJu]*$/';

    /**
     * @var array
     */
    const DELIMITERS = ['`', '~', '%', '@', '#', '/'];

    /**
     * Returns true if the given regular expression matches the given string
     *
     * @param string $pattern The regular expression
     * @param string $subject The input string
     * @param int offset The position within $subject at which the search
     *   should begin
     * @return boolean
     */
    public function hasMatch($pattern, $subject, $offset = 0)
    {
        Args::check($pattern, 'regex', 'pattern');
        Args::check($subject, 'string', 'subject');
        Args::check($offset, 'int', 'offset');
        $match = null;
        return preg_match($pattern, $subject, $match, 0, $offset) > 0;
    }

    /**
     * Calls preg_match() and returns the match results
     *
     * @param string $pattern The regular expression
     * @param string $subject The input string
     * @param mixed $forceOrCaptures An boolean, integer, string, or array
     *   specifying how the matches will be returned:
     *     - The boolean value false (the default) indicates that if there is
     *       no match the return value will be null, and otherwise it should
     *       be an array with the same meaning as the $match argument to
     *       preg_match(), except that offsets corresponding to positional
     *       capture expressions that do not match contain null values
     *     - The boolean value true indicates that if there is no match the
     *       return value will be an indexed array consisting of null values,
     *       and otherwise it will be an array with the same meaning as the
     *       $match argument to preg_match(), except that offsets corresponding
     *       to positional capture expressions that do not match contain null
     *       values; the constant FORCE is provided for this purpose to help
     *       make code self explanatory
     *     - An integer n indicates that the strings matching the nth capture
     *       expression will be returned; if the pattern does not match, or if
     *       the nth capture expression does not match, null will be returned;
     *       the value 0 indicates that the text matching the entire expression
     *       should be returned, if the pattern matches, and null otherwise
     *     - A string s has the same effect as an integer n, except the string
     *       is interpretted as the name of a capture expression
     *     - An array of integers or strings indicates that the list of strings
     *       matching the capture expressions indicated by the integers or
     *       strings in the array should be returned; if the pattern does not
     *       match, the returning array will consist entirely of null values;
     *       the returned array will have the same collection of keys as
     *       $captures
     * @param int offset The position within $subject at which the search
     *   should begin
     * @return mixed
     */
    public static function getMatch($pattern, $subject, $forceOrCaptures = false,
         $offset = 0)
    {
        // Validate arguments
        Args::check($pattern, 'regex', 'pattern');
        Args::check($subject, 'string', 'subject');
        Args::check($offset, 'int', 'offset');
        if ($forceOrCaptures !== null)
            Args::check($subject, 'string', 'bool|int|string|list[scalar]');

        // Decompose $forceOrCaptures for readability
        $hasCaptures = !is_bool($forceOrCaptures);
        $force = $forceOrCaptures === self::FORCE;
        $captures = $hasCaptures ? $forceOrCaptures : null;

        // Perform match
        $offsets = null;
        $success =
            preg_match(
                $pattern,
                $subject,
                $offsets,
                PREG_OFFSET_CAPTURE,
                $offset
            ) > 0;

        // Construct match results
        $match = null;
        if ($success) {
            $match = [];
            foreach ($offsets as $k => [$v, $o])
                $match[$k] = $o != -1 ? $v : null;
        } elseif ($force || $hasCaptures) {
            $match = [];
        }

        if ($success && !$hasCaptures || $force) {

            // Add null values for unmatched positional caputures
            for ($i = 0, $n = substr_count($pattern, '('); $i <= $n; ++$i)
                if (!isset($match[$i]))
                    $match[$i] = null;
        }

        return !$hasCaptures ?
            $match :
            ( is_scalar($captures) ?
                $match[$captures] ?? null:
                Array_::values($match, $captures) );
    }

    /**
     * Calls preg_match_all() and returns the reulting matches
     *
     * @param string $pattern The regular expression
     * @param string $subject The input string
     * @param int $flags The flags to pass to preg_match_all(); this parameter
     *   has no default value
     * @param int offset The position within $subject at which the search
     *   should begin
     * @return array
     */
    public static function getAllMatches($pattern, $subject, $flags, $offset = 0)
    {
        Args::check($pattern, 'regex', 'pattern');
        Args::check($subject, 'string', 'subject');
        Args::check($flags, 'int', 'flags');
        Args::check($offset, 'int', 'offset');
        if ($flags == self::SET)
            $flags |= PREG_OFFSET_CAPTURE;
        $raw = null;
        if (preg_match_all($pattern, $subject, $raw, $flags, $offset) == 0)
            return null;
        if (($flags & self::SET) == 0)
            return $raw;
        $expressions = substr_count($pattern, '(');
        $result = [];
        foreach ($raw as $offsets) {
            $match = [];
            foreach ($offsets as $k => [$v, $o])
                $match[$k] = $o != -1 ? $v : null;
            for ($i = 0; $i <= $expressions; ++$i)
                if (!isset($match[$i]))
                    $match[$i] = null;
            $result[] = $match;
        }
        return $result;
    }

    /**
     * Adds delimiters and flags to the given regular expression or sequence of
     * expressions
     *
     * @param string $template A string having the same syntax as a PHP PCRE
     *   regular expression, except that the expression body is empty, e.g.,
     *   "//i"
     * @param string ...$patterns One or more PCRE regular expressions, without
     *   delimiters or flags; if multiple expressions are specified, they will
     *   be treated as disjuncts and concatenated using "|"
     * @return string
     */
    public static function delimit($template, ...$patterns)
    {
        Args::check($template, 'string', 'regular expression template');
        Args::check($patterns, 'list[string]', 'list of regular expressions');
        if (!preg_match(self::MATCH_TEMPLATE, $template))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid regular expression template: $template"
                ]);
        if (empty($patterns))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing list of regular expressions'
                ]);
        $d1 = $template[0];
        $d2 = $template[1];
        $body = join('|', $patterns);
        if ( strpos($body, $d1) !== false ||
             ($d1 !== $d2 && strpos($body, $d2) !== false ) )
        {
            // Search for alternate delimiter
            $delim = null;
            foreach (self::DELIMITERS as $d) {
                if ($d !== $d1 && strpos($body, $d) === false) {
                    $delim = $d;
                    break;
                }
            }
            if ($delim !== null) {
                $d1 = $d2 = $delim;
            } else {
                $d1 = $d2 = '/';
                $body = self::escapeSlashes($body);
            }
        }
        $result = "$d1$body$d2" . substr($template, 2);
        Args::check($result, 'regex', 'delimited regular expression');
        return $result;
    }

    /**
     * Alias for getMatch()
     *
     * @deprecated
     */
    public static function match(...$args)
    {
        return self::getMatch(...$args);
    }

    /**
     * Alias for getAllMatches()
     *
     * @deprecated
     */
    public static function matchAll(...$args)
    {
        return self::getAllMatches(...$args);
    }

    /**
     * Helper for delimit() that ecapes forward slashes in the given regular
     * expression
     *
     * @param string $value
     * @return string
     */
    private static function escapeSlashes($pattern)
    {
        $result = '';
        $esc = false;
        for ($i = 0, $n = strlen($pattern); $i < $n; ++$i) {
            $c = $pattern[$i];
            if ($c !== '\\') {
                if (!$esc) {
                    $result .= $c == '/' ? '\\/' : $c;
                } else {
                    $result .= "\\$c";
                }
                $esc = false;
            } else {
                if ($esc)
                    $result .= '\\\\';
                $esc = !$esc;
            }
        }
        return $result;
    }
}
