<?php

/**
 * Defines the interface CodeRage\Sys\Engine\BasicModuleDescriptor
 *
 * File:        CodeRage/Sys/Engine/BasicModuleDescriptor.php
 * Date:        Fri Nov 13 00:55:19 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use CodeRage\Sys\Base;
use CodeRage\Sys\BasicModule;
use CodeRage\Sys\DataSource\Bindings;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\Engine\ModuleDescriptorInterface
 */
final class BasicModuleDescriptor extends Base implements ModuleDescriptorInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'name' => ['type' => 'string', 'required' => true],
        'metadata' => [
            'type' => 'map|CodeRage\Sys\ModuleInterface',
            'required' => true
        ],
        'bindings' => ['type' => 'list', 'required' => true],
        'rank' => ['type' => 'int', 'required' => true]
    ];

    /**
     * Constructs an instance of CodeRage\Sys\Engine\BasicModuleDescriptor
     *
     * @param array $options The options array; supports the following options:
     *     name - The module name
     *     metadata - The metadata, as an associative array or an instance of
     *       CodeRage\Sys\ModuleInterface
     *     bindings - The list of data source bindings, as associative arrays or
     *       instances of CodeRage\Sys\DataSource\Bindings
     *     rank - The rank
     *     validate - true to process and validate options; defaults to true
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function getName(): string
    {
        return $this->options['name'];
    }

    public function getMetadata(): \CodeRage\Sys\Module
    {
        return $this->options['metadata'];
    }

    public function getBindings(): array
    {
        return $this->options['bindings'];
    }

    public function getRank(): int
    {
        return $this->options['rank'];
    }

    final protected static function processOptions(array &$options, $validate)
    {
        if ($validate) {
            foreach ($options['bindings'] as $i => $b) {
                Args::check(
                    $b,
                    'map[string]|CodeRage\Sys\DataSource\Bindings',
                    "binding at position $i"
                );
            }
        }
        if (is_array($options['metadata'])) {
            $options['metadata'] =
                new BasicModule($options['metadata'] + ['validate' => $validate]);
        }
        foreach ($options['bindings'] as $i => $b) {
            if (is_array($b)) {
                $options['bindings'][$i] = new Bindings($b);
            }
        }
    }
}
