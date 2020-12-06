<?php

/**
 * Defines the class CodeRage\Tool\Runner
 *
 * File:        CodeRage/Tool/CleanHtml.php
 * Date:        Thu Mar 12 04:42:29 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;

/**
 * Strips tags, replaces entity references and normalizes whitespace
 *
 * @param sting $html The text to process
 * @param array $options Supports the following options:
 *     preserveLinebreaks - true if HTML line breaks should be replaced with
 *     newline characters; defaults to false
 * @return The processed text
 */
function cleanHtml($html, $options = [])
{
    // Remove script tag along with its content
    $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);

    // Replace '<' with ' <' to create output same as the perl implementation
    // of cleanHtml()
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
