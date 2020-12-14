<?php

/**
 * Defines the class CodeRage\Build\Tool\Error.
 *
 * File:        CodeRage/Build/Tool/Error.php
 * Date:        Mon Jan 19 23:57:32 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Tool;

use CodeRage\Build\Info;
use CodeRage\Build\Run;
use CodeRage\File;
use CodeRage\Xml;

class Error extends Basic {

    /**
     * Constructs a CodeRage\Build\Tool\Error.
     */
    function __construct()
    {
        $info =
           new Info([
                   'label' => 'Status code parser',
                   'description' =>
                      'Generates runtime status code definitions'
               ]);
        parent::__construct($info);
    }

    /**
     * Returns true if $localName is 'error' and $namespace is the
     * CodeRage.Build project namespace.
     *
     * @param string $localName
     * @param string $namespace
     * @return boolean
     */
    function canParse($localName, $namespace)
    {
        return $localName == 'error' &&
               $namespace = \CodeRage\Build\NAMESPACE_URI;
    }

    /**
     * Returns a target that when executed generates runtime support files
     * for the status codes defined in the project description.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @param DOMElement $element
     * @param string $baseUri The URI for resolving relative paths referenced by
     * $elt
     * @return CodeRage\Build\Target
     * @throws CodeRage\Error
     */
    function parseTarget(Run $run, \DOMElement $elt, $baseUri)
    {
        if ($elt->hasAttribute('src')) {
            $baseUri =
                File::resolve(
                    $elt->getAttribute('src'),
                    $baseUri
                );
            $elt = Xml::loadDocument($src)->documentElement;
        }
        $statusCodes =& $this->statusCodes($run);
        foreach (Xml::childElements($elt) as $status) {
            if ($status->localName != 'status')
                continue;
            $code = $message = null;
            foreach (Xml::childElements($status) as $k) {
                if ($k->localName == 'code') {
                    if ($code !== null)
                        throw new
                            \CodeRage\Error(
                                "Malformed error status definition in " .
                                "'$baseUri': the element 'code' may occur " .
                                "only once"
                            );
                    $code = $k->nodeValue;
                    if (!preg_match('/[._a-z0-9]+$/i', $code))
                        throw new
                            \CodeRage\Error(
                                "Malformed status code '$code' in " .
                                "'$baseUri': status codes may contain only " .
                                "letters, digits, underscores, and dots"
                            );
                }
                if ($k->localName == 'message') {
                    if ($message !== null)
                        throw new
                            \CodeRage\Error(
                                "Malformed error status definition in " .
                                "'$baseUri': the element 'message' may occur " .
                                "only once"
                            );
                    $message = preg_replace('/\s+/', ' ', trim($k->nodeValue));
                    if (!ctype_print($message))
                        throw new
                            \CodeRage\Error(
                                "Malformed status message '$message' in " .
                                "'$baseUri': status messages may contain " .
                                "only printable characters"
                            );
                }
            }
            $statusCodes[] =
                [
                    'code' => $code,
                    'message' => $message,
                    'path' => $baseUri
                ];
        }
        return new
            \CodeRage\Build\Target\Callback(
                function() use($run) { return $this->generate($run); },
                null, [],
                new Info([
                        'label' => "Status code generator",
                        'description' =>
                            "Generates runtime definitions of status codes"
                    ])
            );
    }

    /**
     * Generates runtime definitions of status codes in PHP
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @throws CodeRage\Error
     */
    function generate(Run $run)
    {
        $cache = $this->cache($run);
        if (isset($cache->done))
            return;
        $cache->done = true;
        $statusCodes = [];
        foreach ($this->statusCodes($run) as $code) {
            if (isset($statusCodes[$code['code']])) {
                $prev = $statusCodes[$code['code']];
                throw new
                    \CodeRage\Error(
                        "Duplicate definition of the status code " .
                        "'{$code['code']}' in '{$code['path']}', previously " .
                        "defined in '{$prev['path']}'"
                    );
            }
            $statusCodes[$code['code']] = $code;
        }
        $php = "require_once('CodeRage/Error.php');\n";
        foreach ($statusCodes as $code => $status) {
            $message = addcslashes($status['message'], "'");
            $php .=
                "CodeRage\\Error::registerStatus('$code', '$message');\n";
        }
        $base = $run->projectRoot() . '/.coderage/error';
        $run->generateFile("$base.php", $php, 'php');
    }

    /**
     * Returns a list of associative arrays with keys 'status', 'message', and
     * 'path', build cummulatively during target parsing
     *
     * @return array
     */
    private function &statusCodes(Run $run)
    {
        $cache = $this->cache($run);
        if (!isset($cache->statusCodes))
            $cache->statusCodes = [];
        return $cache->statusCodes;
    }
}
