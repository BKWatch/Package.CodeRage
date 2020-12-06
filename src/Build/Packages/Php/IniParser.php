<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\IniParser
 * 
 * File:        CodeRage/Build/Packages/Php/IniParser.php
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

class IniParser {

    /**
     * The pathname of the underlying .ini file.
     *
     * @var string
     */
    private $path;

    /**
     * The contents of the underlyin .ini file.
     *
     * @var string
     */
    private $data;

    /**
     * The current state of the parser.
     *
     * @var int
     */
    private $state = Ini::STATE_INITIAL;

    /**
     * The current line in the underlying .ini file.
     *
     * @var int
     */
    private $line = 1;

    /**
     * The current character offset in the underlying .ini file.
     *
     * @var int
     */
    private $pos = 0;

    /**
     * The number of characters in $data.
     *
     * @var int
     */
    private $end;

    /**
     * The line ending sequence.
     *
     * @var string
     */
    private $lineEnding;

    /**
     * Constructs a CodeRage\Build\Packages\Php\IniParser.
     *
     * @param string $path
     */
    function __construct($path)
    {
         \CodeRage\File\checkReadable($path);
         $contents = @file_get_contents($path);
         if ($contents === false)
             throw new Error(['message' => "failed reading file: $path"]);
         $this->path = $path;
         $this->lineEnding =
             strpos($contents, "\r\n") !== false ?
                 "\r\n" :
                 "\n";
         $this->data = preg_replace('/\r\n/', "\n", $contents);
         $this->end = strlen($this->data);
    }

    /**
     * Returns the next instance of CodeRage\Build\Packages\Php\IniToken, or false
     * if there are no remaining tokens.
     *
     * @return mixed
     */
    function nextToken()
    {
        if ($this->pos == $this->end)
            return false;
        $token = $this->doNextToken();
        //echo "TOKEN=$token; LINE=$this->line\n";
        return $token;
    }

    /**
     * Returns the next instance of CodeRage\Build\Packages\Php\IniToken, or false
     * if there are no remaining tokens.
     *
     * @return mixed
     */
    function doNextToken()
    {
        switch ($this->state) {
        case Ini::STATE_INITIAL:
            return $this->parseLine();
        case Ini::STATE_VALUE:
        case Ini::STATE_VALUE_DISABLED:
            return $this->parseValue();
        case Ini::STATE_NAME_DISABLED:
            return $this->parseDisabledName();
        default:
            throw new Error(['message' => "Unknown state; $this->state"]);
        }
    }

    /**
     * Creates a token.
     *
     * @param int $id
     * @param string $value
     * @param string $text
     */
    private function newToken($id, $value, $text)
    {
        return new
            IniToken(
                $id, $value, str_replace("\n", $this->lineEnding, $text)
            );
    }

    /**
     * Returns the line ending sequence.
     *
     * @return string
     */
    function lineEnding()
    {
        return $this->lineEnding;
    }

    /**
     * Parses the beginning of a line of an .ini file.
     *
     * @return CodeRage\Build\Packages\Php\IniToken
     */
    private function parseLine()
    {
        static $noticeLen;
        if ($noticeLen === null)
            $noticeLen = strlen(Ini::DIRECTIVE_DISABLED_NOTICE);

        // Skip initial space
        $begin = $this->pos;
        if (!$this->skipSpace())
            return
                $this->newToken(
                    Ini::TOKEN_SPACE,
                    null,
                    substr($this->data, $begin, $this->pos - $begin)
                );

        // Examine first character
        $id = $value = null;
        $c = $this->data[$this->pos];
        switch ($c) {
        case '[':
            $id = Ini::TOKEN_BLOCK;
            $value = $this->parseIgnoredLine();
            $this->state = Ini::STATE_INITIAL;
            break;
        case ';':
            $notice =
                $begin + $noticeLen < $this->end &&
                substr($this->data, $begin, $noticeLen) ==
                    Ini::DIRECTIVE_DISABLED_NOTICE;
            $id = $notice ?
                Ini::TOKEN_DISABLED_NOTICE :
                Ini::TOKEN_COMMENT;
            $value = $this->parseIgnoredLine();
            $this->state = $notice ?
                Ini::STATE_NAME_DISABLED :
                Ini::STATE_INITIAL;
            break;
        default:
            $id = Ini::TOKEN_NAME;
            $value = $this->parseDirectiveName();
            $this->state = Ini::STATE_VALUE;
            break;
        }
        return
            $this->newToken(
                $id, $value, substr($this->data, $begin, $this->pos - $begin)
            );
    }

    /**
     * Parses a directive value.
     *
     * @return CodeRage\Build\Packages\Php\IniToken
     */
    private function parseValue()
    {
        $begin = $this->pos;
        $value = '';
        $done = false;
        while ($this->pos < $this->end) {
            if (!$this->skipSpace())
                break;
            $c = $this->data[$this->pos];
            switch ($c) {
            case ';':
                $this->skipComment();
                $done = true;
                break;
            case '"':
                $value .= $this->parseQuote();
                break;
            case '$':
                if ( $this->pos < $this->end - 1 &&
                     $this->data[$this->pos + 1] == '{' )
                {
                    $value .= $this->parseReference();
                    break;
                }
                // Fall through
            default:
                $value .= $this->parsePlainValue();
                break;
            }
        }
        $id = $this->state == Ini::STATE_VALUE ?
            Ini::TOKEN_VALUE :
            Ini::TOKEN_DISABLED_VALUE;
        $this->state = Ini::STATE_INITIAL;
        return
            $this->newToken(
                $id, $value, substr($this->data, $begin, $this->pos - $begin)
            );
    }

    /**
     * Parses the name of a disabled directive.
     *
     * @return CodeRage\Build\Packages\Php\IniToken
     */
    private function parseDisabledName()
    {
        $begin = $this->pos;
        if ($this->data[$this->pos++] != ';')
            throw new
                Error(['message' =>
                    "Failed parsing .ini file '$this->path': " .
                    "expected ';' at line $this->line, column 1"
                ]);
        $value = $this->parseDirectiveName();
        $this->state = Ini::STATE_VALUE_DISABLED;
        return
            $this->newToken(
                Ini::TOKEN_DISABLED_NAME, $value,
                substr($this->data, $begin, $this->pos - $begin)
            );
    }

    /**
     * Parses a blank line, a line of form [xxx], or a line containing a
     * comment. Returns the block name, if any.
     *
     * @return string
     */
    private function parseIgnoredLine()
    {
        $result = '';
        $block = $comment = false;
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos++];
            switch ($c) {
            case '[':
                if (!$block && !$comment)
                    $block = true;
                break;
            case ';':
                $comment = true;
                break;
            case ']':
                if (!$comment)
                    $block = false;
                break;
            case "\n":
                ++$this->line;
                return $result;
            default:
                if ($block)
                    $result .= $c;
                break;
            }
        }
        return $result;
    }

    /**
     * Parses a directive name and the following '=' character.
     *
     * @return string
     */
    private function parseDirectiveName()
    {
        $result = '';
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos];
            if (ctype_space($c) || $c == '=') {
                if (!$this->skipSpace() || $this->data[$this->pos] != '=')
                    throw new
                        Error(['message' =>
                            "Failed parsing .ini file '$this->path': " .
                            "expected '=' at line $this->line"
                        ]);
                ++$this->pos; // Consume '='
                break;
            }
            $result .= $c;
            ++$this->pos;
        }
        return $result;
    }

    /**
     * Parses portion of the value of an .ini directive not contained in quotes
     * or part of a ${xxx} construct.
     *
     * @return string
     */
    private function parsePlainValue()
    {
        $result = '';
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos];
            switch ($c) {
            case '"':
            case ';':
                return trim($result);
            case '$':
                if ( $this->pos < $this->end - 1 &&
                     $this->data[$this->pos + 1] == '{' )
                {
                    return trim($result);
                }
                // Fall through
            default:
                if ($c == "\n") {
                    //++$this->line;
                    return trim($result);
                }
                $result .= $c;
            }
            ++$this->pos;
        }
        return trim($result);
    }

    /**
     * Parses a quoted string.
     *
     * @param string $this->data
     * @param int $this->line
     * @param int $this->pos
     * @param int $this->end
     * @return string
     */
    private function parseQuote()
    {
        $result = '';
        ++$this->pos; // Assume $this->data[$this->pos] is "
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos++];
            if ($c == '"')
                return $result;
            if ($c == "\n") {
                ++$this->line;
                if ( $this->state ==
                         Ini::STATE_VALUE_DISABLED)
                {
                    if ($this->data[$this->pos++] != ';')
                        throw new
                            Error(['message' =>
                                "Failed parsing .ini file '$this->path': " .
                                "expected ';' at line $this->line, column 1"
                            ]);
                }
            }
            $result .= $c;
        }
        throw new
            Error(
                "Failed parsing .ini file '$this->path': unterminated quoted " .
                "string on line $this->line"
            );
    }

    /**
     * Parses a construct of the form ${xxx}.
     *
     * @return string
     */
    private function parseReference()
    {
        $result = '';
        ++$this->pos; // Assume $this->data[$this->pos] is $
        ++$this->pos; // Assume $this->data[$this->pos] is {
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos++];
            if ($c == '}')
                return '${' . trim($result) . '}';
            if ($c == "\n")
                break;
            $result .= $c;
        }
        throw new
            Error(
                "Failed parsing .ini file '$this->path': unterminated ini " .
                "variable reference on line $this->line"
            );
    }

    /**
     * Advances $this->pos to the next non-space, non-newline character.
     *
     * @return boolean true if at least one character remains in the current
     * line $this->data
     */
    private function skipSpace()
    {
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos];
            if ($c == "\n") {
                ++$this->pos;
                ++$this->line;
                return false;
            }
            if (!ctype_space($c))
                return true;
            ++$this->pos;
        }
        return false;
    }

    /**
     * Advances $this->pos to the end of the current line, if the current
     * character is ';'
     *
     * @return boolean true if a comment was skipped.
     */
    private function skipComment()
    {
        if ($this->data[$this->pos] != ';')
            return false;
        while ($this->pos < $this->end) {
            $c = $this->data[$this->pos];
            if ($c == "\n")
                break;
            ++$this->pos;
        }
        return true;
    }
}
