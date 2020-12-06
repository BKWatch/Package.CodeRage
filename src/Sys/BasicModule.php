<?php

/**
 * Defines the class CodeRage\Sys\BasicModule
 *
 * File:        CodeRage/Sys/BasicModule.php
 * Date:        Tue Nov 10 18:05:03 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use CodeRage\Util\Args;

/**
 * Base class for implementations of CodeRage\Sys\ModuleInterface
 */
class BasicModule extends Base implements ModuleInterface
{
    /**
     * @var array
     */
    protected const OPTIONS = [
        'title' => ['type' => 'string', 'required' => true],
        'description' => ['type' => 'string', 'required' => true],
        'version' => ['type' => 'string', 'required' => true],
        'dependencies' => ['type' => 'array', 'defaut' => []],
        'replaces' => ['type' => 'array', 'defaut' => []],
        'eventHandlers' => ['type' => 'array', 'defaut' => []],
        'dataSources' => ['type' => 'array', 'defaut' => []]
    ];

    /**
     * Constructs an instance of CodeRage\Sys\BasicModule
     *
     * @param array $options The options array; supports the following options:
     *     title - The title
     *     description - The description
     *     version - The version
     *     dependencies - The list of dependencies; defaults to an empty list
     *     replaces - The replacements list; defaults to an empty array
     *     eventHandlers - The collection of event handlers; defaults to an
     *       empty array
     *     dataSources - The list of data source specifications; defaults to an
     *       empty array
     *     validate - true to process and validate options; defaults to true
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function getTitle(): string
    {
        return $this->options['title'];
    }

    public function getDescription(): string
    {
        return $this->options['description'];
    }

    public function getVersion(): string
    {
        return $this->options['version'];
    }

    public function getDependencies(): array
    {
        return $this->options['dependencies'];
    }

    public function getReplaces(): array
    {
        return $this->options['replaces'];
    }

    public function getEventHandlers(): array
    {
        return $this->options['eventHandlers'];
    }

    public function getDataSources(): array
    {
        return $this->options['dataSources'];
    }

    final protected static function processOptions(array &$options, bool $validate): void
    {
        if ($validate) {
            Util::validateVersion($options['version']);
            Util::validateDependencies($options['dependencies']);
            Util::validateReplaces($options['replaces']);
            Util::validateEventHandlers($options['eventHandlers']);
            Util::validateDataSources($options['dataSources']);
        }
    }
}
