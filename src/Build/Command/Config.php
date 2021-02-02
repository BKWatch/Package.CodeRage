<?php

/**
 * Defines the class CodeRage\Build\Command\Config
 *
 * File:        CodeRage/Build/Command/Config.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Command;

use CodeRage\Error;

/**
 * Implements "crush config"
 */
final class Config extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Config
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'config',
            'description' =>
                'Sets, unsets, or displays the value of configuration variables'
        ]);
        $this->addConfigOptions();
        $this->addOption([
            'shortForm' => 'g',
            'longForm' => 'get',
            'description' =>
                'Displays the value of the configuration value <<NAME>>',
        ]);
        $this->addOption([
            'shortForm' => 'r',
            'longForm' => 'raw',
            'description' =>
                'Displays the raw value of the variable specified with ' .
                '--get, instead of the calculated value',
            'type' => 'switch'
        ]);
    }

    protected function doExecute()
    {
        $engine =
            $this->createEngine(['defaultLogLevel' => \CodeRage\Log::WARNING]);
        if ($this->hasValue('get')) {
            if ($this->hasValue('set') || $this->hasValue('unset')) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            'The option --get may not be combined with --set ' .
                            'or --unset'
                    ]);
            }
            return $engine->execute(function($engine) {
                $name = $this->getValue('get');
                $prop = $engine->projectConfig()->lookupProperty($name);
                if ($prop === null) {
                    echo "The configuration variable '$name' is not set" . PHP_EOL;
                    return false;
                } else {
                    $value =  $this->getValue('raw') ?
                        $prop->encode() :
                        $prop->evaluate();
                    echo $value . PHP_EOL;
                    return true;
                }
            });
        } else {
            if ($this->hasExplicitValue('raw')) {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            'The option --raw may not be combined with --set ' .
                            'or --unset'
                    ]);
            }
            return $engine->config([
                'setProperties' => $this->setProperties(),
                'unsetProperties' => $this->unsetProperties()
            ]);
        }
    }
}
