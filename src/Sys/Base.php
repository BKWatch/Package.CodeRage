<?php

/**
 * Defines the abstract class CodeRage\Sys\Base
 *
 * File:        CodeRage/Sys/Base.php
 * Date:        Fri Nov 13 20:04:52 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use stdClass;
use CodeRage\Util\Array_;

/**
 * Base class for several interface implementations
 */
abstract class Base implements \JsonSerializable
{
    /**
     * Constructs an instance of CodeRage\Sys\Base
     *
     * @param array $options The options array; supports the following options:
     *     validate - true to process and validate options; defaults to true
     */
    public function __construct(array $options = [])
    {
        $validate = Args::checkKey($options, 'validate', 'boolean', [
            'default' => true,
            'unset' => true
        ]);
        foreach ($options as $n => $v) {
            if ($v instanceof stdClass) {
                $options[$n] = (array) $v;
            } elseif (is_array($v)) {
                foreach ($v as $i => $x) {
                    if ($x instanceof stdClass) {
                        $options[$n][$i] = (array) $x;
                    }
                }
            }
        }
        if ($validate) {
            foreach (static::OPTIONS as $n => $spec) {
                $type = Array_::unset($spec['type']);
                Args_::checkKey($options, $n, $type, $spec);
            }
        }
        self::processOptions($options, $validate);
        foreach (static::OPTIONS as $n) {
            $options[$n] = $options[$n] ?? null;
        }
        $this->options = $options;
    }

    protected static function processOptions(array &$options, bool $validate): void
    {

    }

    public function jsonSerialize(): array
    {
        return $this->options;
    }

    /**
     * Associative array storing property values
     *
     * @var array
     */
    protected $options;
}
