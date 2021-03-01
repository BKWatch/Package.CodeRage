<?php

/**
 * Defines the class CodeRage\Sys\ModuleStore
 *
 * File:        CodeRage/Sys/Module.php
 * Date:        Wed Dec 16 16:08:34 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use Composer\Semver\Semver;
use MJS\TopSort\Implementations\FixedArraySort;
use CodeRage\Sys\Config\Reader\File as FileReader;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Array_;
use CodeRage\Util\Factory;
use CodeRage\Text;

/**
 * Stores a project's collection of modules
 */
final class ModuleStore {

    /**
     * List of names of built-in modules
     *
     * @var string
     */
    private const BUILTIN_MODULES = ['CodeRage.Sys.Module.Container'];

    /**
     * Constructs an instance of CodeRage\Sys\ModuleStore
     *
     * @param Engine $engine The build engine
     * @param array $modules The list of the class names of modules, if any,
     *   topologically sorted by dependency
     */
    private function __construct(Engine $engine, array $modules = [])
    {
        $this->engine = $engine;
        foreach ($modules as $m)
            $this->loadModule($m);
    }

    /**
     * Returns the module with the given name, if any
     *
     * @return CodeRage\Sys\Module
     */
    public function lookupModule(string $name): ?Module
    {
        return $this->byName[$name] ?? null;
    }

    /**
     * Returns the list of modules, topologically sorted by dependency
     *
     * @return string
     */
    public function modules(): array
    {
        return $this->modules;
    }

    /**
     * Rebuilds the collection of modules from the project configuration
     */
    public function update(): void
    {
        $this->modules = $this->byName = [];

        // Fetch modules
        $stack = $this->moduleNames();
        while (!empty($stack)) {
            $name = array_pop($stack);
            $module = $this->loadModule($name);
            foreach ($module->dependencies() as $dep)
                if (!isset($this->byName[$dep]))
                    $stack[] = $dep;
        }

        // Sort by dependencies
        $sorter = new FixedArraySort();
        $sorter->setCircularInterceptor(function(array $names) {
            throw new
                Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'message' =>
                        'Found circular dependency among modules: ' .
                        join(' => ', $names)
                ]);
        });
        foreach ($this->byName as $name => $module)
            $sorter->add($name, $module->dependencies());
        $sorted = $sorter->sort();
        $index = array_flip($sorted);
        usort(
            $this->modules,
            function($a, $b) use($index)
            {
                return $index[$a->name()] <=> $index[$b->name()];
            }
        );
    }

    /**
     * Returns a newly constructed module store loaded from the build cache,
     * or an empty store if no cached store is available
     *
     * @param Engine $engine The build engine
     */
    public static function load(Engine $engine): self
    {
        $path = Config::projectRoot() . '/.coderage/modules.php';
        $modules = [];
        if (file_exists($path)) {
            File::checkFile($path, 0b0100);
            $modules = include($path);
        }
        return new self($engine, $modules);
    }

    /**
     * Saves this store to the build cache
     */
    public function save(): void
    {
        $path = Config::projectRoot() . '/.coderage/modules.php';
        File::checkDirectory(dirname($path), 0b0011);
        $content =
            "return\n" . $this->formatArray(array_keys($this->byName), '    ') .
            ";\n";
        File::generate($path, $content, 'php');
    }

    /**
     * Helper for load()
     *
     * @param string $name
     */
    private function moduleNames(): array
    {
        $modules = [];
        foreach (self::BUILTIN_MODULES as $m) {
            $modules[$m] = 1;
        }
        $config = $this->engine->projectConfig();
        if ($mods = $config->lookupProperty('modules')) {
            foreach (Text::split($mods->value(), Text::COMMA) as $m) {
                $modules[$m] = 1;
            }
        }
        if ($env = $config->lookupProperty('environment')) {
            if ($mods = $config->lookupProperty('modules.' . $env->value())) {
                foreach (Text::split($mods->value(), Text::COMMA) as $m) {
                    $modules[$m] = 1;
                }
            }
        }
        return array_keys($modules);
    }

    /**
     * Helper for load()
     *
     * @param string $name
     */
    private function loadModule(string $name): Module
    {
        $module = $this->byName[$name] ?? null;
        if ($module === null) {
            $module = Factory::create(['class' => $name]);
            $this->modules[] = $module;
            $this->byName[$name] = $module;
            if (!empty($module->tables()))
                $this->loadModule('CodeRage.Db.Module');
            if (!empty($module->webRoots()))
                $this->loadModule('CodeRage.Web.Module');
        }
        return $module;
    }

    /**
     * Returns the given array of strings formatted as a PHP expression
     *
     * @param array $values
     * @param string $indent
     */
    private function formatArray(array $values, string $indent)
    {
        $indexed = Array_::isIndexed($values);
        $items = [];
        foreach ($values as $n => $v) {
            $items[] = $indexed ?
                $this->formatString($v) :
                $this->formatString($n) . ' => ' . $this->formatString($v);
        }
        return "{$indent}[\n$indent    " . join(",\n$indent    ", $items) .
               "\n$indent]";
    }

    /**
     * Returns a PHP expression evaluating to the given string
     *
     * @param string $value
     * @return string
     */
    private function formatString(string $value)
    {
        return "'" . addcslashes($value, "\\'") . "'";
    }

    /**
     * @var CodeRage\Sys\Engine
     */
    private $engine;

    /**
     * @var array
     */
    private $modules = [];

    /**
     * @var array
     */
    private $byName = [];
}
