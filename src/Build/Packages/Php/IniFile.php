<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\IniFile
 * 
 * File:        CodeRage/Build/Packages/Php/IniFile.php
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
 * Represents an .ini file.
 */
class IniFile {

    /**
     * The pathname of the underlying file.
     *
     * @var string
     */
    private $path;

    /**
     * The text that occurs before any directives.
     *
     * @var string
     */
    private $prefix;

    /**
     * A list of instance of CodeRage\Build\Packages\Php\IniLine
     *
     * @var array
     */
    private $lines = [];

    /**
     * The collection of instances of CodeRage\Build\Packages\Php\IniDirective
     *
     * @var array
     */
    private $insertedDirectives = [];

    /**
     * The list of the names of the extensions added by the build system.
     *
     * @var array
     */
    private $insertedExtensions = [];

    /**
     * The line ending sequence.
     *
     * @var string
     */
    private $lineEnding;

    /**
     * true if this .ini file has been modified.
     *
     * @var boolean
     */
    private $modified = false;

    /**
     * Constructs a CodeRage\Build\Packages\Php\IniFile.
     *
     * @param string $path
     * @throws CodeRage\Error
     */
    function __construct($path)
    {
        $this->path = $path;
        $this->parse();
    }

    /**
     * Returns true if this .ini file contains an enabled occurrence of the
     * named directive, either pre-existing or added by the build system.
     *
     * @param string $name
     * @return boolean
     */
    function hasDirective($name)
    {
        if (isset($this->insertedDirectives[$name]))
            return true;
        foreach ($this->lines as $line) {
            if (  $line->directive() &&
                  $line->name() == $name &&
                 !$line->disabled() )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the final non-disabled occurrence of the named directive in this
     * .ini file, or null if there is no such occurrence.
     *
     * @param string $name
     * @return CodeRage\Build\Packages\Php\IniDirective
     */
    function lookupDirective($name)
    {
        if (isset($this->insertedDirectives[$name]))
            return $this->insertedDirectives[$name];
        for ($z = sizeof($this->lines) - 1; $z != -1; --$z) {
            $line = $this->lines[$z];
            if (  $line->directive() &&
                  $line->name() == $name &&
                 !$line->disabled() )
            {
                return $line->directive();
            }
        }
        return null;
    }

    /**
     * Sets the value of the given directive in this .ini file, by enabling
     * a disabled directive or inserting a new directive as appropriate.
     *
     * @param string $name
     * @param mixed $value
     * @param boolean $insert true if a directive should be inserted if
     * necessary
     * @return boolean true if the directive has the desired value after
     * function execution.
     * @throws CodeRage\Error if the given value is invalid
     */
    function insertDirective($name, $value, $insert = true)
    {
        if ($this->enableDirective($name, $value))
            return true;

        if (($d = $this->lookupDirective($name)) && $d->value() === $value) {

            // Directive already exists
            return true;

        } elseif (isset($this->insertedDirectives[$name])) {

            // Directive has been inserted, but has wrong value
            $d->setValue($value);
            return true;

        } else {

            // Disable existing occurrences of directive
            $found = false;
            for ($z = sizeof($this->lines) - 1; $z != -1; --$z) {
                $line = $this->lines[$z];
                if ($line->directive() && $line->name() == $name) {
                    $found = true;
                    $line->setDisabled(true);
                }
            }

            if (!$insert && !$found)
                return false;

            // Insert directive
            $d = IniDirective::create($name);
            $d->setValue($value);
            $this->insertedDirectives[$name] = $d;
            $this->modified = true;
            return true;
        }
    }

    /**
     * Disables or removes all occurrences of the named directive in this .ini
     * file.
     *
     * @param string $name
     */
    function removeDirective($name)
    {
        if (isset($this->insertedDirectives[$name])) {
            unset($this->insertedDirectives[$name]);
            $this->modified = true;
        }
        for ($z = sizeof($this->lines) - 1; $z != -1; --$z) {
            $line = $this->lines[$z];
            if (  $line->directive() &&
                  $line->name() == $name &&
                 !$line->disabled() )
            {
                $line->setDisabled(true);
            }
        }
    }

    /**
     * Returns true if this .ini file contains a directive enabling the named
     * extension, either pre-existing or added by the build system.
     *
     * @param string $name
     * @return boolean
     */
    function hasExtension($name)
    {
        if (isset($this->insertedExtensions[$name]))
            return true;
        foreach ($this->lines as $line) {
            if (  $line->extension() &&
                  $line->name() == $name &&
                 !$line->disabled() )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Enables the given extension, either by enabling a disabled directive or
     * inserting a new directive.
     *
     * @param string $name
     * @param boolean $insert true if a directive should be inserted if
     * necessary
     * @return boolean true if the extension is enabled after execution of the
     * function.
     */
    function enableExtension($name, $insert = true)
    {
        // Check whether directive enabling extension has been inserted by the
        // build system
        if (isset($this->insertedExtensions[$name]))
            return true;

        // Look for pre-existing occurrences of extension, enabled or disabled
        for ($z = sizeof($this->lines) - 1; $z != -1; --$z) {
            $line = $this->lines[$z];
            if ($line->extension() && $line->name() == $name) {
                if ($line->disabled())
                    $line->setDisabled(false);
                return true;
            }
        }

        if (!$insert)
            return false;

        // Insert extension
        $this->insertedExtensions[$name] = $name;
        $this->modified = true;
        return true;
    }

    /**
     * Disables or removes all occurrences of the named extension in this .ini
     * file.
     *
     * @param string $name
     */
    function disableExtension($name)
    {
        if (isset($this->insertedExtensions[$name])) {
            unset($this->insertedExtensions[$name]);
            $this->modified = true;
        }
        for ($z = sizeof($this->lines) - 1; $z != -1; --$z) {
            $line = $this->lines[$z];
            if (  $line->extension() &&
                  $line->name() == $name &&
                 !$line->disabled() )
            {
                $line->setDisabed(true);
            }
        }
    }

    /**
     * Saves changes to this .ini file.
     *
     * @throws CodeRage\Error
     */
    function save()
    {
        $modified = $this->modified;
        if (!$modified) {
            foreach ($this->lines as $line) {
                if ($line->modified()) {
                    $modified = true;
                    break;
                }
            }
            foreach ($this->insertedDirectives as $dir) {
                if ($dir->modified()) {
                    $modified = true;
                    break;
                }
            }
        }
        if ($modified) {
            $handler = new ErrorHandler;
            $result = $handler->_file_put_contents($this->path, (string) $this);
            if ($result === false || $handler->errno())
                throw new
                    Error(['message' =>
                        $handler->formatError("Failed writing to '$this->path'")
                    ]);
            $this->parse();
        }
    }

    /**
     * Returns a string representation of this .ini file.
     *
     * @return string
     */
    function __toString()
    {
        $result = $this->prefix;
        foreach ($this->lines as $line)
            $result .= $line;
        if ( sizeof($this->insertedDirectives) ||
             sizeof($this->insertedExtensions) )
        {
            $result = preg_replace('/\n+$/', '', $result);
            $result .=
                "\n\n[CodeRage]\n" .
                "; Updated " . date(Ini::DIRECTIVE_DISABLED_TIMESTAMP) .
                "\n";
            foreach ($this->insertedDirectives as $n => $d)
                $result .= (string) $d;
            foreach ($this->insertedExtensions as $e)
                $result .=
                    "extension = " .
                    (PHP_SHLIB_SUFFIX == 'dll' ? 'php_' : '') . "$e." .
                    PHP_SHLIB_SUFFIX;
        }

        return str_replace("\n", $this->lineEnding, $result);
    }

    /**
     * Looks for a disabled directive with the given name and value and
     * enables it.
     *
     * @param string $name
     * @param mixed $value
     * @return boolean true if a directive was enabled
     */
    function enableDirective($name, $value)
    {
        for ($z = sizeof($this->lines) - 1; $z != -1; --$z) {
            $line = $this->lines[$z];
            if (  $line->directive() &&
                  $line->disabled() &&
                  $line->name() == $name &&
                  $line->value() === $value )
            {
                $line->setDisabled(false);
                unset($this->insertedDirectives[$name]);
                return true;
            }
        }
    }

    /**
     * Parses the underlying .ini file.
     *
     * @throws CodeRage\Error.
     */
    private function parse()
    {
        $this->reset();
        $parser = new IniParser($this->path);
        $this->lineEnding = $parser->lineEnding();
        $name = $value = $curPrefix = $nextPrefix = $curBlock = $nextBlock =
            null;
        $text = $suffix = '';
        $first = true;
        while ($token = $parser->nextToken()) {
            switch ($token->id()) {
            case Ini::TOKEN_NAME:
            case Ini::TOKEN_DISABLED_NAME:
                if ($name || $first) {
                    if ($name)
                        $this->processLine(
                            $curBlock, $curPrefix, $text, $suffix, $name, $value
                        );
                    $curBlock = $nextBlock;
                    $curPrefix = $nextPrefix;
                    $nextPrefix = null;
                    $name = $value;
                    $text = $suffix = '';
                }
                $first = false;
                $name = $token->value();
                $text .= $token->text();
                break;
            case Ini::TOKEN_VALUE:
            case Ini::TOKEN_DISABLED_VALUE:
                $value = $token->value();
                $text .= $token->text();
                break;
            case Ini::TOKEN_BLOCK:
                if ($name || $first) {
                    if ($token->value() != 'CodeRage')
                        $suffix .= $token->text();
                    $nextBlock = $token->value();
                } else {
                    $this->prefix .= $token->text();
                    $curBlock = $nextBlock = $token->value();
                }
                break;
            case Ini::TOKEN_COMMENT:
            case Ini::TOKEN_SPACE:
                if ($name || $first) {
                    if ($nextBlock != 'CodeRage')
                        $suffix .= $token->text();
                } else {
                    $this->prefix .= $token->text();
                }
                break;
            case Ini::TOKEN_DISABLED_NOTICE:
                $curPrefix = $nextPrefix;
                $nextPrefix = $token->text();
                break;
            }
        }
        if ($name)
            $this->processLine(
                $curBlock, $curPrefix, $text, $suffix, $name, $value
            );
    }

    /**
     * Clears this .ini file's member variables.
     */
    private function reset()
    {
        $this->prefix = '';
        $this->lines = [];
        $this->insertedDirectives = [];
        $this->insertedExtensions = [];
        $this->lineEnding = null;
        $this->modified = false;
    }

    /**
     * Processes a directive together with surrounding lines.
     *
     * @param string $block
     * @param string $prefix
     * @param string $text
     * @param string $suffix
     * @param string $name
     * @param string $value
     */
    private function processLine(
        $block, $prefix, $text, $suffix, $name, $value)
    {
        $directive = $name != 'extension' ?
            IniDirective::create($name) :
            null;
        if ($directive)
            $directive->setValueImpl($directive->fromString($value));
        $extension = $name == 'extension' ?
            preg_replace('/(^php_)|(\.[^.]+$)/', '', $value) : null;
        if ($block == 'CodeRage') {
            if ($directive)
                $this->insertedDirectives[$name] = $directive;
            else
                $this->insertedExtensions[$extension] = $extension;
        } else {
            $line =
                new IniLine(
                        $prefix, $text, $suffix, $directive, $extension
                    );
            $this->lines[] = $line;
        }
    }
}
