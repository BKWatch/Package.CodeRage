<?php

/**
 * Defines the class CodeRage\Sys\Module\BasicConfig
 *
 * File:        CodeRage/Sys/Module/BasicConfig.php
 * Date:        Sun Nov 15 17:29:41 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Module;

use CodeRage\Sys\DataSource\Bindings;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\Module\ConfigInterface
 */
class BasicConfig extends Base implements ConfigInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'name' => ['type' => 'string', 'required' => true],
        'dataSources' => [
            'type' => 'map[string]|CodeRage\Sys\DataSource\Bindings',
            'required' => true
        ]
    ];

    /**
     * Constructs an instance of CodeRage\Sys\Module\BasicConfig
     *
     * @param array $options The options array; supports the following options:
     *     name - The module name
     *     dataSources - The data source bindings, as an associative array
     *       or instance of CodeRage\Sys\DataSource\Bindings
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

    public function getDataSources(): array
    {
        return $this->options['dataSources'];
    }

    final protected static function processOptions(array &$options, bool $validate): void
    {
        if (is_array($options['dataSources'])) {
            $options['dataSources'] =
                new Bindings(
                    $options['dataSources'] + ['validate' => $validate]
                );
        }
    }
}
