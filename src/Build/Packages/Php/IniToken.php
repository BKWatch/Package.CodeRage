<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\IniToken
 * 
 * File:        CodeRage/Build/Packages/Php/IniToken.php
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
 * Represents a parsed component of an .ini file.
 *
 */
class IniToken {

    /**
     * One of the constants CodeRage\Build\Packages\Php\Ini::TOKEN_XXX
     *
     * @var int
     */
    private $id;

    /**
     * The string value of this CodeRage\Build\Packages\Php\IniToken
     *
     * @var string
     */
    private $value;

    /**
     * The portion of the underlying .ini file associated with this
     * CodeRage\Build\Packages\Php\IniToken
     *
     * @var string
     */
    private $text;

    /**
     * Constructs a CodeRage\Build\Packages\Php\IniToken.
     *
     * @param int $id One of the constants
     * CodeRage\Build\Packages\Php\Ini::TOKEN_XXX
     * @param string $value The string value of the
     * CodeRage\Build\Packages\Php\IniToken under construction
     * @param string $text The portion of the underlying .ini file
     * associated with the CodeRage\Build\Packages\Php\IniToken under
     * construction
     */
    function __construct($id, $value, $text)
    {
        $this->id = $id;
        $this->value = $value;
        $this->text = $text;
    }

    /**
     * Returns one of the constants CodeRage\Build\Packages\Php\Ini::TOKEN_XXX
     *
     * @return int
     */
    function id()
    {
        return $this->id;
    }

    /**
     * Returns the string value of this CodeRage\Build\Packages\Php\IniToken
     *
     * @return string
     */
    function value()
    {
        return $this->value;
    }

    /**
     * Returns the portion of the underlying .ini file associated with this
     * CodeRage\Build\Packages\Php\IniToken
     *
     * @return string
     */
    function text()
    {
        return $this->text;
    }

    /**
     * Returns a string representation of this token.
     *
     * @return string
     */
    function __toString()
    {
        $result = '';
        switch ($this->id) {
        case Ini::TOKEN_NAME:
            return "name($this->value)";
        case Ini::TOKEN_VALUE:
            return "value($this->value)";
        case Ini::TOKEN_BLOCK:
            return "block($this->value)";
        case Ini::TOKEN_COMMENT:
            return "comment($this->text)";
        case Ini::TOKEN_SPACE:
            return "space";
        case Ini::TOKEN_DISABLED_NOTICE:
            return "disabled notice";
        case Ini::TOKEN_DISABLED_NAME:
            return "disabled name($this->value)";
        case Ini::TOKEN_DISABLED_VALUE:
            return "disabled value($this->value)";
        default:
            throw new Error(['message' => "Unknown id: $this->id"]);
        }
    }
}
