<?php

/**
 * Defines the class CodeRage\Build\Config\Property.
 *
 * File:        CodeRage/Build/Config/Property.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Args;

/**
 * Represents a property value plus metadata.
 */
final class Property {

    /**
     * @var string
     */
    private const MATCH_TYPE = '/^(literal|environment|file)$/';

    /**
     * Constructs a CodeRage\Build\Config\Property.
     *
     * @param array $options The options array; supports the following options:
     *    name - The property name, as a string
     *    type - One of 'literal', 'environment', or 'file'; defaults to
     *      'literal'
     *    value - The raw property value
     *    setAt - The path of the file in which the property was set, if any
     */
    public function __construct(array $options)
    {
        $name =
            Args::checkKey($options, 'name', 'string', [
                'required' => true
            ]);
        $type =
            Args::checkKey($options, 'type', 'string', [
                'default' => 'literal'
            ]);
        if (!preg_match(self::MATCH_TYPE, $type)) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid type: $type"
                ]);
        }
        $value =
            Args::checkKey($options, 'value', 'string', [
                'required' => true
            ]);
        $setAt = Args::checkKey($options, 'setAt', 'string');
        if ($setAt !== null) {
            File::checkFile($setAt, 0b0000);
        }
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
        $this->setAt = $setAt;
    }

    /**
     * Returns the property name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns one of 'literal', 'environment', or 'file'
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
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
        if ($this->type == 'literal') {
            return $this->value;
        } elseif ($this->type == 'environment') {
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
     * Translates the given location into a human readable string.
     *
     * @param mixed $location A file pathname or one of the constants
     * CodeRage\Build\XXX.
     * @return string
     */
    public static function translateLocation(?string $location)
    {
        return $location ?? '[command-line]';
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $setAt;
}
