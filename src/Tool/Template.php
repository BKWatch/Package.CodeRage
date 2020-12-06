<?php

/**
 * Defines the class CodeRage\Tool\Template
 *
 * File:        CodeRage/Tool/Template.php
 * Date:        Fri Jan 12 16:39:25 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;


/**
 * Extracts structured data from HTML
 */
abstract class Template {

    /**
     * Constructs a CodeRage\Tool\Template
     */
    protected function __construct() { }

    /**
     * Parses the given HTML fragment, returning a list of associative
     * arrays whose lists of keys are the same as the list of parameters of this
     * template
     *
     * @param string $html An HTML fragment
     * @return array
     */
    public abstract function apply($html);

    /**
     * Returns the first associative array that would be returned by apply(), or
     * null if apply() would return an empty array
     *
     * @param string $html An HTML fragment
     * @return array An associative array whose lists of keys are the same as
     *   the list of parameters of this template
     */
    public final function applyOnce($html)
    {
        $matches = $this->apply($html);
        return count($matches) > 0 ? $matches[0] : null;
    }

    public final function value($html)
    {
        $match = $this->applyOnce($html);
        if ($match === null)
            return null;
        $keys = array_keys($match);
        if (count($keys) != 1)
            throw new
                \CodeRage\Error([
                    'status' => 'STATE_ERROR',
                    'details' =>
                        'The method \CodeRage\Tool\Template::value() only ' .
                        'works for templates with a single parameter'
                ]);
        return $match[$keys[0]];
    }
}
