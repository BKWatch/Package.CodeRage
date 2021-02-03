<?php

/**
 * Defines the class CodeRage\Build\Property.
 *
 * File:        CodeRage/Build/Property.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Error;
use CodeRage\File;
use CodeRage\Text\Regex;
use CodeRage\Util\Args;

/**
 * Represents a property value plus metadata.
 */
final class Property {

    /**
     * Indicates that a property's 'value' attribute contains its value
     *
     * @var integer
     */
    public const LITERAL = 1;

    /**
     * Indicates that a property's 'value' attribute is the name of an
     * environment variable storing the property value
     *
     * @var integer
     */
    public const ENVIRONMENT = 2;

    /**
     * Indicates that a property's 'value' attribute is the path of a file
     * storing the property value
     *
     * @var integer
     */
    public const FILE = 3;

    /**
     * @var string
     */
    private const MATCH_SET_AT = '/^(\[[\.a-z0-9\]|[^[].+)$/';

    /**
     * @var string
     */
    private const MATCH_ENCODING = '/^(env|file)\[(.+)\]$/';

    /**
     * Constructs a CodeRage\Build\Config\Property.
     *
     * @param array $options The options array; supports the following options:
     *    storage - The value of one of the constants LITERAL, ENVIRONMENT, or
     *      FILE
     *    value - The raw property value
     *    setAt - The location of the definition of the property value; must be
     *      a file pathname or one of the special values "[cli]" or "[XXX]"
     *      where XXX is a module names
     */
    public function __construct(array $options)
    {
        $storage =
            Args::checkKey($options, 'storage', 'int', [
                'default' => self::LITERAL
            ]);
        if ($storage < self::LITERAL || $storage > SELF::FILE) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid storage: $storage"
                ]);
        }
        $value =
            Args::checkKey($options, 'value', 'string', [
                'required' => true
            ]);
        $setAt =
            Args::checkKey($options, 'setAt', 'string', [
                'required' => true
            ]);
        if ($setAt[0] !== '[') {
            File::checkFile($setAt, 0b0000);
        }
        $this->storage = $storage;
        $this->value = $value;
        $this->setAt = $setAt;
    }

    /**
     * Returns the value of one of the constants LITERAL, ENVIRONMENT, or
     * FILE
     *
     * @return int
     */
    public function storage(): int
    {
        return $this->storage;
    }

    /**
     * Returns the raw property value
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Evaluates this property and returns the result, consulting the
     * environment or the file system if appropriate
     *
     * @return string
     */
    public function evaluate(): string
    {
        if ($this->storage == self::LITERAL) {
            return $this->value;
        } elseif ($this->storage == self::ENVIRONMENT) {
            return ($v = getenv($this->value)) !== false ? $v : '';
        } else {
            File::checkFile($this->value, 0b0100);
            return file_get_contents($this->value);
        }
    }

    /**
     * path of the file in which this property was set, if any
     *
     * @return string
     */
    public function setAt(): ?string
    {
        return $this->setAt;
    }

    /**
     * Constructs a property from the given encoded value, where the syntax
     * env[VALUE] or file[VALUE] can be used to define properties with storage
     * ENVIRONMENT or FILE. Opening square brackets may be be escaped with a
     * backslash
     *
     * @param string $encoding The encoded property value
     * @param string setAt The location of the definition of the property value;
     *   must be a file pathname or one of the special values "[cli]" or "[XXX]"
     *   where XXX is a module names
     * @return self
     */
    public static function decode(string $encoding, string $setAt): self
    {
        [$storage, $value] =
            Regex::getMatch(self::MATCH_ENCODING, $encoding, [1, 2]);
        if ($storage !== null) {
            $storage = $storage == 'env' ? self::ENVIRONMENT : self::FILE;
        } else {
            $storage = self::LITERAL;
            $value = self::unescape($encoding);
        }
        return new
            self([
                'storage' => $storage,
                'value' => $value,
                'setAt' => $setAt
            ]);
    }

    /**
     * Returns the result of encoding this properties value together with its
     * storage, where the syntax env[VALUE] or file[VALUE] is used to encode
     * properties with storage ENVIRONMENT and FILE, respectively
     *
     * @return string
     */
    public function encode(): string
    {
        switch ($this->storage) {
            case self::LITERAL:      return $this->value;
            case self::ENVIRONMENT:  return 'env[' . $this->value . ']';
            case self::FILE:         return 'file[' . $this->value . ']';
        }
    }

    /**
     * Returns the result of unescaping the characters '[' and '\' in the given
     * value
     *
     * @param unknown $value
     * @return string
     */
    private function unescape($value): string
    {
        $esc = false;
        $result = "";
        for ($i = 0, $n = strlen($value); $i < $n; ++$i) {
            $c = $value[$i];
            if (!$esc) {
                if ($c != "\\") {
                    $result .= $c;
                } else {
                    $esc = true;
                }
            } else {
                if ($c != "[" && $c != "\\") {
                    throw new
                        Error([
                            "status" => "INVALID_PARAMETER",
                            "message" => "Unrecognized escape sequence: \\$c"
                        ]);
                }
                $result .= $c;
                $esc = false;
            }
        }
        return $result;
    }

    /**
     * @var int
     */
    private $storage;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $setAt;
}
