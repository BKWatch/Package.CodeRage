<?php

/**
 * Defines the class CodeRage\Build\Command\Info
 *
 * File:        CodeRage/Build/Command/Info.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Command;

/**
 * Implements "crush info"
 */
final class Info extends Base {

    /**
     * @var integer
     */
    private const MAX_VARIABLE_LENGTH = 50;

    /**
     * Constructs an instance of CodeRage\Build\Command\Sync
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'info',
            'description' =>
                'Displays configuration information about a project'
        ]);
        $this->addOption([
            'shortForm' => 'r',
            'longForm' => 'raw',
            'description' =>
                'Display the raw value of the variables instead of the ' .
                'calculated value',
            'type' => 'switch'
        ]);
        $this->addOption([
            'shortForm' => 'c',
            'longForm' => 'command',
            'description' =>
                'Display a command that can be used to reconfigure the ' .
                'project after it is reset',
            'type' => 'switch'
        ]);
    }

    protected function doExecute()
    {
        if ($this->hasExplicitValue('raw') && $this->hasExplicitValue('command')) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'The options --raw and --command are incompatible'
                ]);
        }
        $engine = $this->createEngine(['defaultLogLevel' => \CodeRage\Log::WARNING]);
        return $engine->execute(function($engine) {
            $result = '';
            $modules = $engine->moduleStore()->modules();
            if (!empty($modules)) {
                $result .= "\nMODULES:\n\n";
                foreach ($engine->moduleStore()->modules() as $m) {
                    $result .= '  ' . $m->name() . PHP_EOL;
                }
            }
            $config = $engine->projectConfig();
            $raw = $this->getValue('raw') || $this->getValue('command');
            $setAt = [];
            foreach ($config->propertyNames() as $name) {
                $prop = $config->lookupProperty();
                $setAt[$prop->setAt()][$name] = $raw ?
                    $prop->encode() :
                    $prop->evaluate();
            }
            foreach ($setAt as $location => $properties) {
                ksort($setAt[$location]);
            }
            uksort($setAt);
            $result = null;
            if ($this->getValue('command')) {
                if (!isset($setAt['cli'])) {
                    echo "No CLI configuration variables are set" . PHP_EOL;
                    return true;
                }
                $result = 'crush config';
                foreach ($setAt['cli'] as $name => $value) {
                    $result .= " --set name=" . escapeshellarg($value);
                }
            } else {
                $result = '';
                $modules = $engine->moduleStore()->modules();
                if (!empty($modules)) {
                    $result .= "\nMODULES:" . PHP_EOL . PHP_EOL;
                    foreach ($engine->moduleStore()->modules() as $m) {
                        $result .= '  ' . $m->name() . PHP_EOL;
                    }
                    $result .= PHP_EOL;
                }
                foreach ($setAt as $location => $properties) {
                    $result .= $setAt . ':' . PHP_EOL . PHP_EOL;
                    foreach ($properties as $name => $value) {
                        $result .=
                            str_pad(
                                $name,
                                self::MAX_VARIABLE_LENGTH,
                                $this->formatString($value)
                            ) . PHP_EOL;
                    }
                    $result .= PHP_EOL;
                }
            }
            echo $result;
            return true;
        });
    }

    /**
     * Returns a PHP expression evaluating to the given string
     *
     * @param string $value
     * @return string
     */
    private function formatString(string $value)
    {
        return strlen($value) == 0 || ctype_print($value) ?
            "'" . addcslashes($value, "\\'") . "'" :
            "base64_decode('" . base64_encode($value) . "')";
    }
}
