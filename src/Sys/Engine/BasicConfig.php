<?php

/**
 * Defines the class CodeRage\Sys\Engine\BasicConfig
 *
 * File:        CodeRage/Sys/Engine/BasicConfig.php
 * Date:        Fri Nov 13 19:51:17 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use CodeRage\Util\Array_;
use CodeRage\Util\Args;

/**
 * An implementation of CodeRage\Sys\Engine\ConfigInterface
 */
final class BasicConfig extends Base implements ConfigInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'modules' => ['type' => 'list', 'default' => []],
        'dataSources' => ['type' => 'list', 'default' => []],
        'options' => [
            'type' => 'map[scalar]|CodeRage\Sys\ConfigInterface',
            'default' => []
        ]
    ];

    /**
     * Constructs an instance of CodeRage\Sys\BasicProjectConfig
     *
     * @param array $options The options array; supports the following options:
     *     modules - The list of module configurations, as associative arrays
     *       or instances of CodeRage\Sys\Module\ConfigInterface
     *     dataSources - The list of data source configurations, as associative
     *       arrays or instances of CodeRage\Sys\DataSource\ConfigInterface
     *     options - The collection of configuration variables, as an
     *       associative array or as an instance of CodeRage\Sys\ConfigInterface
     *     config - An instance of CodeRage\Sys\ConfigIterface
     *     validate - true to process and validate options; defaults to true
     *   The option "config" is incompatible with the options "modules",
     *   "dataSources", and "options"
     */
    public function __construct(array $options)
    {
        $config = Args::checkKey($options, 'config', 'CodeRage\Sys\ConfigIterface');
        if ($config !== null) {
            foreach (self::OPTIONS as $name) {
                if (isset($options[$name])) {
                    throw new Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The option 'config' is incompatible with the " .
                            "option '$name'"
                    ]);
                }
            }
            unset($options['config']);
            $options['modules'] = $config->getProperty('modules', []);
            $options['dataSources'] = $config->getProperty('dataSources', []);
            $options['options'] = $config->getProperty('modules', []);
        }
        parent::__construct($options);
    }

    public function getModules(): array
    {
        return $this->options['modules'];
    }

    public function getDataSources(): array
    {
        return $this->options['dataSources'];
    }

    public function getOptions(): Config
    {
        return $this->options['options'];
    }

    protected static function processOptions(array &$options, bool $validate): void
    {
        if ($validate) {
            foreach ($modules as $i => $m) {
                Args::check(
                    $m,
                    'map|CodeRage\Sys\Module\ConfigInterface',
                    "module at position $i"
                );
            }
            foreach ($dataSources as $i => $d) {
                Args::check(
                    $d,
                    'map|CodeRage\Sys\DataSource\ConfigInterface',
                    "data source at position $i"
                );
            }
        }
        foreach ($options['modules'] as $i => $m) {
            if (is_array($m)) {
                $options['modules'][$i] =
                    new \CodeRage\Sys\Module\BasicConfig(
                        $m + ['validate' => $validate]
                    );
            }
        }
        foreach ($options['dataSources'] as $i => $d) {
            if (is_array($d)) {
                $options['dataSources'][$i] =
                    new \CodeRage\Sys\DataSource\BasicConfig(
                        $d + ['validate' => $validate]
                    );
            }
        }
        if (is_array($options['options'])) {
            $options['options'] =
                new \CodeRage\Sys\Config\Basic(
                    $options['options'] + ['validate' => $validate]
                );
        }
    }
}
