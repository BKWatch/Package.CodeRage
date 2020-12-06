<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\Ini
 * 
 * File:        CodeRage/Build/Packages/Php/Ini.php
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
 * Represents the collection of .ini files associated with a PHP configuration.
 */
class Ini {

    /**
     * Used to indicate that a php.ini directive has type "boolean"
     */
    const BOOLEAN_ = 0;

    /**
     * Used to indicate that a php.ini directive has type "integer"
     */
    const INT_ = 1;

    /**
     * Used to indicate that a php.ini directive has type "string"
     */
    const STRING_ = 2;

    /**
     * Used to indicate that a php.ini directive represents a file pathname.
     */
    const PATH = 3;

    /**
     * Used to indicate that a php.ini directive represents a list of file
     * pathnames.
     */
    const PATHLIST = 4;

    /**
     * Represents the special value "none."
     */
    const NONE = 0;

    /**
     * Represents the special value "syslog," recognized by the directive
     * "error_log."
     */
    const SYSLOG = 1;

    /**
     * Represents a directive name.
     *
     * @var int
     */
    const TOKEN_NAME = 0;

    /**
     * Represents a directive value.
     *
     * @var int
     */
    const TOKEN_VALUE = 1;

    /**
     * Represents a line of the form [XXX].
     *
     * @var int
     */
    const TOKEN_BLOCK = 2;

    /**
     * Represent a line containing only a comment.
     *
     * @var int
     */
    const TOKEN_COMMENT = 3;

    /**
     * Represent a line containing only whitespace.
     *
     * @var int
     */
    const TOKEN_SPACE = 4;

    /**
     * Represents a comment indicating that a directive has been disabled.
     *
     * @var int
     */
    const TOKEN_DISABLED_NOTICE = 5;

    /**
     * Represents the name of a disabled directive.
     *
     * @var int
     */
    const TOKEN_DISABLED_NAME = 6;

    /**
     * Represents the value of a disabled directive.
     *
     * @var int
     */
    const TOKEN_DISABLED_VALUE = 7;

    /**
     * Indicates that the parser is in its initial state.
     *
     * @var int
     */
    const STATE_INITIAL = 0;

    /**
     * Indicates that the parser is expecting a directive value.
     *
     * @var int
     */
    const STATE_VALUE = 1;

    /**
     * Indicates that the parser is expecting a directive name in a disabled
     * directive.
     *
     * @var int
     */
    const STATE_NAME_DISABLED = 3;

    /**
     * Indicates that the parser is expecting a directive value in a disabled
     * directive.
     *
     * @var int
     */
    const STATE_VALUE_DISABLED = 4;

    /**
     * Notice inserted before a commented out directive.
     */
    const DIRECTIVE_DISABLED_NOTICE = '; Disabled by CodeRage';

    /**
     * Timestamp format for commented out directive.
     */
    const DIRECTIVE_DISABLED_TIMESTAMP = DATE_RFC1036;

    /**
     * A list of instances of CodeRage\Build\Packages\Php\Ini.
     *
     * @var array
     */
    private $files = [];

    /**
     * Constructs a CodeRage\Build\Packages\Php\IniFile.
     *
     * @param string $configFilePath The path to the primary .ini file.
     * @param unknown_type $configFileScanDir The value, if any, of the
     * configuration option "config-file-scan-dir."
     * @throws CodeRage\Error
     */
    function __construct($configFilePath, $configFileScanDir = null)
    {
        $this->files[] = new IniFile($configFilePath);
        if ($configFileScanDir && file_exists($configFileScanDir)) {
            $handler = new ErrorHandler;
            $dir = $handler->_opendir($configFileScanDir);
            if ($handler->errno())
                throw new
                    Error(['message' =>
                        $handler->formatError(
                            "Failed reading directory '$configFileScanDir'"
                        )
                    ]);
            while (($file = @readdir($dir)) !== false)
                if (preg_match('/\.ini$/', $file))
                    $this->files[] =
                        new IniFile(
                            "$configFileScanDir/$file"
                        );
            @closedir($dir);
        }
    }

    /**
     * Returns true if this collection of .ini files contains an enabled
     * occurrence of the named directive, either pre-existing or added by the
     * build system.
     *
     * @param string $name
     * @return boolean
     */
    function hasDirective($name)
    {
        foreach ($this->files as $f)
            if ($f->hasDirective($f))
                return true;
        return false;
    }

    /**
     * Returns the final non-disabled occurrence of the named directive in this
     * collection of .ini files, or null if there is no such occurrence.
     *
     * @param string $name
     * @return CodeRage\Build\Packages\Php\IniDirective
     */
    function lookupDirective($name)
    {
        for ($z = sizeof($this->files) - 1; $z != -1; --$z)
            if ($d = $this->files[$z]->lookupDirective($name))
                return $d;
        return null;
    }

    /**
     * Sets the value of the given directive in this collection of .ini files,
     * by enabling a disabled directive or inserting a new directive as
     * appropriate.
     *
     * @param string $name
     * @param mixed $value
     * @throws CodeRage\Error if the given value is invalid
     */
    function insertDirective($name, $value)
    {
        for ($z = sizeof($this->files) - 1; $z != -1; --$z) {
            if ($this->files[$z]->insertDirective($name, $value, $z == 0)) {
                for (--$z; $z != -1; --$z)
                    $this->files[$z]->removeDirective($name);
                break;
            }
        }
    }

    /**
     * Disables or removes all occurrences of the named directive in this
     * collection of .ini files.
     *
     * @param string $name
     * @return boolean true if an .ini file was modified.
     */
    function removeDirective($name)
    {
        foreach ($this->files as $f)
            $f->removeDirective($name);
    }

    /**
     * Returns true if this collection of .ini files contains a directive
     * enabling the named extension, either pre-existing or added by the build
     * system.
     *
     * @param string $name
     * @return boolean
     */
    function hasExtension($name)
    {
        foreach ($this->files as $f)
            if ($f->hasExtension($f))
                return true;
        return false;
    }

    /**
     * Enables the given extension, either by enabling a disabled directive or
     * inserting a new directive.
     *
     * @param string $name
     */
    function enableExtension($name)
    {
        for ($z = sizeof($this->files) - 1; $z != -1; --$z)
            if ($this->files[$z]->enableExtension($name, $z == 0))
                return;
    }

    /**
     * Disables or removes all occurrences of the named extension in this
     * collection of .ini files.
     *
     * @param string $name
     */
    function disableExtension($name)
    {
        foreach ($this->files as $f)
            $f->disableExtension($name);
    }

    /**
     * Saves changes to the underlying collection of .ini files.
     *
     * @throws CodeRage\Error
     */
    function save()
    {
        foreach ($this->files as $f)
            $f->save();
    }
}
