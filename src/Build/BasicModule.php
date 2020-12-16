<?php

/**
 * Defines the class CodeRage\Build\BasicModule
 *
 * File:        CodeRage/Build/BasicModule.php
 * Date:        Wed Dec 16 16:08:34 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\File;
use CodeRage\Util\Args;

/**
 * An implementation on CodeRage\Build\Module
 */
class BasicModule {

    /**
     * Constructs an instance of CodeRage\Build\BasicModule
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'title', 'string', ['required' => true]);
        Args::checkKey($options, 'description', 'string', ['required' => true]);
        Args::checkKey($options, 'configFile', 'string');
        Args::checkKey($options, 'dependencies', 'list[string]', [
            'default' => []
        ]);
        Args::checkKey($options, 'tables', 'string');
        Args::checkKey($options, 'statusCodes', 'string');
        $webRoots = Args::checkKey($options, 'webRoots', 'map[string]');
        foreach ($webRoots as $src => $dest) {
            File::checkDirectory($src, 0b0101);
        }
    }

    public function title(): string
    {
        return $this->options['title'];
    }

    public function description(): string
    {
        return $this->options['description'];
    }

    public function configFile(): ?string
    {
        return $this->options['configFile'];
    }

    public function dependencies(): array
    {
        return $this->options['dependencies'];
    }

    public function tables(): ?string
    {
        return $this->options['tables'];
    }

    public function statusCodes(): ?string
    {
        return $this->options['statusCodes'];
    }

    public function webRoots(): array
    {
        return $this->options['webRoots'];
    }

    public function build(Engine $engine): void
    {

    }

    public function install(Engine $engine): void
    {

    }

    public function sync(Engine $engine): void
    {

    }

    /**
     * @var array
     */
    private array $options;
}
