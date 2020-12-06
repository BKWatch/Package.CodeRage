<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\IniLine
 * 
 * File:        CodeRage/Build/Packages/Php/IniLine.php
 * Date:        Wed Feb 06 14:57:45 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Php;

use CodeRage\Error;
use CodeRage\Util\ErrorHandler;
use function CodeRage\Util\printScalar;

/**
 * @ignore
 */
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/Util/printScalar.php');

/**
 * Represents a directive in an .ini file, possibly commented-out, together with
 * some text preceding and following the directive.
 */
class IniLine {

    /**
     * The line, if any, containing the comment stating that the underlying
     * directive has been commented out by the build system.
     *
     * @var string
     */
    private $prefix;

    /**
     * The line containing the underlying .ini directive.
     *
     * @var string
     */
    private $text;

    /**
     * The text, if any, following the underlying directive and preceding the
     * next directive.
     *
     * @var string
     */
    private $suffix;

    /**
     * The underlying .ini directive, or null if the directive is of the form
     * extension=xxx.
     *
     * @var CodeRage\Build\Packages\Php\IniDirective
     */
    private $directive;

    /**
     * The simple name of the PHP extension contained in the underlying .ini
     * directive, if it has the form extension=xxx, and null otherwise.
     *
     * @var string
     */
    private $extension;

    /**
     * true if the underlying directive has been disabled by the build system.
     *
     * @var bools
     */
    private $disabled;

    /**
     * Constructs a CodeRage\Build\Packages\Php\IniLine.
     *
     * @param string $prefix The line, if any, containing the comment stating
     * that the underlying directive has been commented out by the build system.
     * @param string $text The line containing the underlying .ini directive.
     * @param string $suffix  The text, if any, following the underlying
     * directive and preceding the next directive.
     * @param CodeRage\Build\Packages\Php\IniDirective $directive The underlying .ini
     * directive, or null if the directive is of the form extension=xxx.
     * @param string $extension The simple name of the PHP extension contained
     * in the underlying .ini directive, if it has the form extension=xxx, and
     * null otherwise.
     */
    function __construct($prefix, $text, $suffix, $directive, $extension)
    {
        $this->prefix = $prefix;
        $this->text = $text;
        $this->suffix = $suffix;
        $this->directive = $directive;
        $this->extension = $extension;
        $this->disabled = $prefix !== null;
    }

    /**
     * Returns the directive name, unless the directive name is 'extension',
     * in which case returns the simple extension name.
     *
     * @return string
     */
    function name()
    {
        return $this->directive ?
            $this->directive->name() :
            $this->extension;
    }

    /**
     * Returns the line, if any, containing the comment stating that the
     * underlying directive has been commented out by the build system.
     *
     * @return string
     */
    function prefix()
    {
        return $this->prefix;
    }

    /**
     * Returns the line containing the underlying .ini directive.
     *
     * @return string
     */
    function text()
    {
        return $this->text;
    }

    /**
     * Returns the text, if any, following the underlying
     * directive and preceding the next directive.
     *
     * @return string
     */
    function suffix()
    {
        return $this->suffix;
    }

    /**
     * Returns the underlying .ini directive, or null if the directive is of the
     * form extension=xxx.
     *
     * @return CodeRage\Build\Packages\Php\IniDirective
     */
    function directive()
    {
        return $this->directive;
    }

    /**
     * Returns the value of the underlying directive; requires the
     * underlying directrive to be non-null.
     *
     * @return mixed
     * @throws CodeRage\Error if this directive has the form extension=xxx.
     */
    function value()
    {
        return $this->directive->value();
    }

    /**
     * Sets the value of the underlyings directive; requires the
     * underlying directrive to be non-null.
     *
     * @param mixed $value
     * @throws CodeRage\Error if this directive has the form extension=xxx.
     */
    function setValue($value)
    {
        $this->directive->setValue($value);
    }

    /**
     * Returns the simple name of the PHP extension contained in the underlying
     * .ini directive, if it has the form extension=xxx, and null otherwise.
     *
     * @return string
     */
    function extension()
    {
        return $this->extension;
    }

    /**
     * Returns true if the underlying directive has been disabled.
     *
     * @return boolean
     */
    function disabled()
    {
        return $this->disabled;
    }

    /**
     * Marks the underlying directive as disabled or enabled.
     *
     * @param boolean $disabled
     */
    function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * Returns true if this directive has been modified.
     *
     * @return boolean.
     */
    function modified()
    {
        return $this->disabled === ($this->prefix === null) ||
               $this->directive && $this->directive->modified();
    }

    /**
     * Returns a string representation of this
     * CodeRage\Build\Packages\Php\IniLine suitable for inclusion in an .ini
     * file.
     *
     * @return string
     */
    function __toString()
    {
        $prefix = $text = null;
        if ($this->disabled == ($this->prefix !== null)) {
            $prefix = $this->prefix;
            $text = $this->directive && $this->directive->modified() ?
                (string) $this->directive :
                $this->text;
        } elseif ($this->disabled) {
            $prefix =
                Ini::DIRECTIVE_DISABLED_NOTICE . ': ' .
                date(Ini::DIRECTIVE_DISABLED_TIMESTAMP) .
                "\n";
            $text = ';' . preg_replace("/\n(?!$)/", "\n;", $this->text);
        } else {
            $prefix = '';
            $text = $this->directive && $this->directive->modified() ?
                (string) $this->directive :
                str_replace("\n;", "\n", substr($this->text, 1));
        }
        return $prefix . $text . $this->suffix;
    }
}
