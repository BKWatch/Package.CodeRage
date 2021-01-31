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
     * @var string
     */
    private const MATCH_SET_AT = '/^(\[(cli|code)\]|[^[].+)$/';

    /**
     * Constructs a CodeRage\Build\Config\Property.
     *
     * @param array $options The options array; supports the following options:
     *    type - One of 'literal', 'environment', or 'file'; defaults to
     *      'literal'
     *    value - The raw property value
     *    setAt - The source of the property value; must be a file pathname or
     *      one of the special values "[cli]" or "[code]"
     */
    public function __construct(array $options)
    {
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
        $setAt =
            Args::checkKey($options, 'setAt', 'string', [
                'required' => true
            ]);
        if ($setAt[0] !== '[') {
            File::checkFile($setAt, 0b0000);
        }
        $this->type = $type;
        $this->value = $value;
        $this->setAt = $setAt;
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
