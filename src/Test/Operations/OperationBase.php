<?php

/**
 * Defines the class CodeRage\Test\Operations\OperationBase
 *
 * File:        CodeRage/Test/Operations/OperationBase.php
 * Date:        Tue Apr 25 22:48:17 EDT 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use stdClass;
use CodeRage\Sys\Config\Array_ as ArrayConfig;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\Array_;
use CodeRage\Util\Time;


/**
 * Base class for all implementations of
 * CodeRage\Test\Operations\AbstractOperation
 */
abstract class OperationBase implements AbstractOperation, XmlSupportConstants {
    use XmlSupport;

    public function expandExpressions($value)
    {
        $eval = $this->expressionEvaluator();
        $result = null;
        if (is_scalar($value)) {
            if (is_string($value))
                $value = Text::expandExpressions($value, $eval);
            $result = $value;
        } elseif (is_array($value) && Array_::isIndexed($value)) {
            $result = [];
            foreach ($value as $i)
                $result[] = self::expandExpressions($i);
        } elseif (is_array($value)) {
            $result = [];
            foreach ($value as $n => $v)
                $result[$n] = self::expandExpressions($v);
        } elseif ($value instanceof stdClass) {
            $result = new stdClass;
            foreach ($value as $n => $v)
                $result->$n =
                    self::expandExpressions($v);
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Expected native data structure; found ' .
                        getType($value)
                ]);
        }
        return $result;
    }

    public function config()
    {
        if ($this->config === null) {
            if (($config = $this->configProperties()) !== null) {
                $config = $this->expandExpressions($config);
                $this->config = new ArrayConfig($config, Config::current());
            }
        }
        return $this->config;
    }

    /**
     * Returns an expression evaluator taking a string argument and returning a
     * string
     *
     * @return callable
     */
    protected abstract function expressionEvaluator();

    /**
     * Installs the return value of config() as the current configuration, if
     * it is non-null, and sets the current time based on the value of the
     * configuration variable 'coderage.util.time.offset', using
     * CodeRage\Util\Time::set()
     *
     * @return CodeRage\Config The previous configuration, if a new
     *   configuration was installed
     */
    protected function installConfig()
    {
        $prev = null;
        if (($config = $this->config()) !== null) {
            $prev = Config::setCurrent($config);
            if ($config->hasProperty('coderage.util.time.offset')) {
                $offset = $config->getProperty('coderage.util.time.offset');
                Time::set(Time::real() + $offset);
            }
        }
        return $prev;
    }

    /**
     * Clears the cached instance of CodeRage\Config created by config()
     */
    protected function clearConfig()
    {
        $this->config = null;
    }

    /**
     * @var CodeRage\Config
     */
    protected $config;

}
