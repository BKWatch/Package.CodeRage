<?php

/**
 * Defines the class CodeRage\Text
 *
 * File:        CodeRage/Text.php
 * Date:        Thu Sep 17 12:28:44 UTC 2020
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;

/**
 * Container for static methods for text processing
 */
final class Text {

    /**
     * Pattern for use with Text::split()
     *
     * @var string
     */
    public const SPACE = '#\s+#';

    /**
     * Pattern for use with Text::split()
     *
     * @var string
     */
    public const COMMA = '#\s*,\s*#';

    /**
     * @var array
     */
    private const NO_CAPS =
        [ 'a' => 1, 'an' => 1, 'and' => 1, 'but' => 1, 'by' => 1, 'for' => 1,
          'in' => 1, 'of' => 1, 'on' => 1, 'the' => 1 ];

    /**
     * Expands expressions embedded in the string, delimited by curly braces
     *
     * @param string $value
     * @param callable $eval An optional expression evaluator taking a string
     *   argument and returning a string; expressions of the form config.xxx are
     *   handled handled automatically
     * @return string
     */
    public static function expandExpressions(string $value,
        ?callable $eval = null) : string
    {
        if (strpbrk($value, '{}') === false)
            return $value;

        // States:
        //   0=initial;
        //   1=parsing character following right bracket
        //   2=parsing character following left bracket
        $result = '';
        $state = 0;
        for ($z = 0, $n = strlen($value); $z < $n; ++$z) {
            $c = $value[$z];
            switch ($state) {
            case 0:
                switch ($c) {
                case '{':
                    $state = 1;
                    break;
                case '}':
                    $state = 2;
                    break;
                default:
                    $result .= $c;
                    break;
                }
                break;
            case 1:
                if ($c == '{') {
                    $result .= '{';
                } else {
                    $end = strpos($value, '}', $z);
                    if ($end === false)
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' =>
                                    "Unmatched opening bracket at position " .
                                    "$z in attribute value template: $value"
                            ]);
                    $expr = substr($value, $z, $end - $z);
                    if ( preg_match(
                             '/^config\.([_a-zA-Z0-9.]+)(?::(.*))?$/',
                             $expr,
                             $match) )
                    {
                        $config = \CodeRage\Config::current();
                        $result .= isset($match[2]) ?
                            $config->getProperty($match[1], $match[2]) :
                            $config->getRequiredProperty($match[1]);
                    } elseif ($eval !== null) {
                        $result .= $eval($expr);
                    } else {
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' =>
                                    "Invalid expression '$expr' at position " .
                                    "$z in attribute value template '$value'"
                            ]);
                    }
                    $z = $end;
                }
                $state = 0;
                break;
            case 2:
                if ($c == '}') {
                    $result .= '}';
                    $state = 0;
                } else {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Unmatched closing bracket at position $z in " .
                                "attribute value template: $value"
                        ]);
                }
            default:
                break;
            }
        }
        if ($state != 0) {
            $type = $state == 1 ? 'opening' : 'closing';
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Unmatched $type bracket at position " . ($n - 1) .
                        "in attribute value template: $value"
                ]);
        }
        return $result;
    }

    /**
     * Formats the given list of items joined by commas and the specified
     * conjunction if there are more than two items
     *
     * @param array A list of items convertible to strings
     * @param string $conj The conjunction; defaults to 'and'
     * @param string $quote The quote character, if any
     * @return string
     */
    public static function formatList(array $items, string $conj = 'and',
        ?string $quote = null) : string
    {
        if ($quote !== null)
            $items =
                Array_::map(
                    function($i) use($quote) { return "$quote$i$quote"; },
                    $items
                );
        $count = count($items);
        switch (count($items)) {
        case 0:
            return '';
        case 1:
            return (string) $items[0];
        case 2:
            return "{$items[0]} $conj {$items[1]}";
        default:
            return join(', ', array_slice($items, 0, $count - 1)) . ", $conj " .
                   $items[$count - 1];
        }
    }

    /**
     * Strips tags, replaces entity references and normalizes whitespace
     *
     * @param sting $html The text to process
     * @param array $options Supports the following options:
     *     preserveLinebreaks - true if HTML line breaks should be replaced with
     *     newline characters; defaults to false
     * @return The processed text
     */
    public static function htmlToText(string $html, array $options = [])
        : string
    {
        // Remove script tag along with its content
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);

        // Replace '<' with ' <' to create output same as the former Perl
        // implementation of cleanHtml()
        $html = str_replace('<', ' <', $html);
        $html = preg_replace('/(&#[0-9]+)([^0-9;])/', '$1;$2', $html);
        $html = preg_replace('#(&nbsp;)+#', ' ', $html);
        if ( isset($options['preserveLinebreaks']) &&
             $options['preserveLinebreaks'])
        {
            $lines = [];
            foreach (preg_split('#<BR\s*/?>#i', $html) as $line) {
                $line = strip_tags($line);
                $line = preg_replace('#\(\s+#', '(', $line);
                $line = preg_replace('#\s+([.,:;)])#', "$1", $line);
                $line = preg_replace('#\s+#', ' ', trim($line));
                $lines[] = $line;
            }
            $html = join("\n", $lines);
            $html = preg_replace('#^\n*(.*?)\n*$#', "$1", $html);

        } else {
            $html = strip_tags($html);
            $html = preg_replace('#\(\s+#', '(', $html);
            $html = preg_replace('#\s+([.,:;)])#', "$1", $html);
            $html = preg_replace('#\s+#', ' ', $html);
        }
        return trim(html_entity_decode($html, ENT_QUOTES));
    }

    /**
     * Returns the result of splitting the given string using the given
     * regular expression. By default, trims the given string and splits on
     * whitespace
     *
     * @param string $value The string to split
     * @param string $pattern The Perl-compatible regular expression
     * @param boolean $trim true if the string should be trimmed before
     *   splitting; defaults to true
     * @return array An array of strings
     */
    public static function split(string $value, string $pattern = self::SPACE,
        bool $trim = true) : array
    {
        return $trim ?
            ( $value === '' || ctype_space($value) ?
                  [] :
                  preg_split($pattern, trim($value)) ) :
            preg_split($pattern, $value);
    }

    /**
     * Removes diacritical marks from text using the Roman alphabet encoded in
     * UTF-8
     *
     * @param string $value The input string
     * @return string The result of stripping accents
     */
    public static function stripAccents(string $value) : string
    {
        return iconv("utf-8", "ascii//TRANSLIT", $value);
    }

    /**
     * Removes diacritical marks and unprintable characters from the given
     * string
     *
     * @param string $value The input string
     * @return string The result of converting $val to ASCII
     */
    public static function toAscii(string $value) : string
    {
        if (preg_match('/[^ -~]/', $value)) {
            $value = self::stripAccents($value);
            preg_replace('/[^[:print:]]/', '', $value);
        }
        return $value;
    }

    /**
     * Returns the result of converting the given string to title case
     *
     * @param string $val
     * @return string
     */
    public static function titleCase(string $value) : string
    {
        $result = $cur = null;
        $first = 1;
        for ($z = 0; $n = strlen($value), $z <= $n; ++$z) {
            $c = $z < $n ? $value[$z] : null;
            if ($z === $n || preg_match('/[[:space:][:punct:]]/', $c) && $c != "'") {
                if ($cur) {
                    $result .=
                            $first ||
                            $z === $n ||
                            !array_key_exists($cur, self::NO_CAPS) ?
                        ucfirst($cur) :
                        $cur;
                    $first = 0;
                }
                if ($z != $n) {
                    $cur = '';
                    $result .= $c;
                }
            } else {
                $cur .= strtolower($c);
            }
        }
        return $result;
    }

    /**
     * Transforms the given identifier to camel case.
     *
     * @param string $value The text to transform
     * @param string $upper true if the returned value should use upper camel
     *   case; defaults to true
     * @return string The result of the transformation
     */
    public static function toCamelCase(string $value, bool $upper = true)
        : string
    {
        $result = '';
        for ($z = 0, $n = strlen($value); $z < $n; ++$z) {
            $c = $value[$z];
            if ($c == '_' || $c == '-') {
                $upper = true;
            } else {
                $result .= $upper ? strtoupper($c) : strtolower($c);
                $upper = false;
            }
        }
        return $result;
    }

    /**
     * Returns the result of inserting line breaks into the given text and
     * prefixing each line with the appropriate prefix from the given list in
     * such a way that line-breaks occur between words, if possible, and no line
     * exceeds the given length.
     *
     * Uses a greedy algorithm.
     *
     * @param string $text The text to wrap
     * @param int $lineLength The line length
     * @param string $prefix An array of prefixes; the first term will be
     *   prefixed to the first line, the second to the second, etc. When the
     *   list of prefixes is exhausted, each remaining line will be prefixed
     *   with the last item in the array. An empty array is equivalent to an
     *   array containing a single empty string.
     */
    public static function wrap(string $text, int $lineLength,
        array $prefixes = []) : string
    {
        // Resolve $prefixes
        if (is_string($prefixes))
            $prefixes = [$prefixes];
        if (sizeof($prefixes) == 0)
            $prefixes = [''];

        // Normalize space
        $text = preg_replace('/\s+/', ' ', trim($text));

        $line = 0;
        $result = '';
        while (true) {

            // Add prefix
            $prefix = $line < sizeof($prefixes) ?
                $prefixes[$line] :
                $prefixes[sizeof($prefixes) - 1];
            $text = $prefix . $text;

            // Handle case where line is short
            $length = strlen($text);
            if ($length <= $lineLength) {
                $result .= "$text\n";
                break;
            }

            // Search for last space before end of line
            $sp = strrpos($text, ' ', -($length - $lineLength));
            $hasSpace = $sp !== false && $sp > strlen($prefix);
            $br = $hasSpace ? $sp : $lineLength;

            // Update loop variables
            ++$line;
            $result .= substr($text, 0, $br) . "\n";
            $text = substr($text, $hasSpace ? $br  + 1: $br);
        }
        return $result;
    }
}
