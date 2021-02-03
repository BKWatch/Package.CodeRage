<?php

/**
 * Defines the class CodeRage\Util\Test\Command
 * 
 * File:        CodeRage/Util/Test/Command.php
 * Date:        Sun Nov  1 02:43:17 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Test\Assert;
use CodeRage\Util\CommandLine;

/**
 * @ignore
 */

class Command extends CommandLine {
    public function __construct($name, $useAction = false)
    {
        parent::__construct([
            'name' => $name,
            'description' => "Example command with name '$name'",
            'synopsis' => '[OPTION ...] [SUBCOMMAND [OPTION...] ...] [ARG ...]'
        ]);
        $this->addOption([
            'shortForm' => 'b',
            'longForm' => 'boolean',
            'label' => 'Example boolean option',
            'description' => 'Example boolean option',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'shortForm' => 'i',
            'longForm' => 'int',
            'label' => 'Example integer',
            'description' => 'Example integer option',
            'type' => 'int'
        ]);
        $this->addOption([
            'shortForm' => 'f',
            'longForm' => 'float',
            'label' => 'Example float',
            'description' => 'Example floating-point option',
            'type' => 'float'
        ]);
        $this->addOption([
            'shortForm' => 's',
            'longForm' => 'string',
            'label' => 'Example string',
            'description' => 'Example string option',
            'type' => 'string'
        ]);
        $this->addOption([
            'shortForm' => 'm',
            'longForm' => 'multiple',
            'label' => 'Example multiple',
            'description' => 'Example string option with multiple values',
            'type' => 'string',
            'multiple' => true
        ]);
        $this->addOption([
            'longForm' => 'multiple-int',
            'label' => 'Example multiple int',
            'description' => 'Example int option with multiple values',
            'type' => 'int',
            'multiple' => true
        ]);
        $this->addOption([
            'shortForm' => 'o',
            'longForm' => 'optional-int',
            'label' => 'optional int',
            'description' => 'Example boolean with optional value',
            'type' => 'int',
            'valueOptional' => true
        ]);
        $this->addOption([
            'shortForm' => 'x',
            'label' => 'String option',
            'description' => 'Example string option 2',
            'type' => 'string'
        ]);
        $this->addOption([
            'shortForm' => 'y',
            'label' => 'Boolean option',
            'description' => 'Example boolean option 2',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'shortForm' => 'z',
            'label' => 'Boolean option',
            'description' => 'Example boolean option with action',
            'type' => 'boolean',
            'action' =>
                function($cmd)
                {
                    CommandLineExecution::log($cmd, "switch action");
                }
        ]);
        if ($useAction)
            $this->setAction(
                function($cmd)
                {
                    CommandLineExecution::log($cmd, "action");
                }
            );
    }
    protected function doExecute()
    {
        CommandLineExecution::log($this);
    }
}
