<?php

/**
 * Defines the class CodeRage\Build\ModuleStore
 *
 * File:        CodeRage/Build/Module.php
 * Date:        Wed Dec 16 16:08:34 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use Composer\Semver\Semver;
use MJS\TopSort\Implementations\FixedArraySort;
use CodeRage\Build\Config\Reader\File as FileReader;
use CodeRage\Error;
use CodeRage\Util\Factory;
use CodeRage\Text;

/**
 * Stores a project's collection of modules
 */
final class ModuleStore {

    /**
     * Constructs an instance of CodeRage\Build\ModuleStore
     *
     * @param Engine $engine The build engine
     * @param array $modules The list of the class names of modules, if any,
     *   topologically sorted by dependency
     */
    public function __construct(Engine $engine, array $modules = [])
    {
        $this->engine = $engine;
        foreach ($modules as $m)
            $this->loadModule($m);
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
     * Loads and sort modules
     */
    public function load(): void
    {
        $this->modules = $this->byName = [];

        $path = $this->engine->buildConfig()->projectConfigFile();
        $reader = new FileReader($this->engine, $path);
        $config = $reader->read();
        $moduleNames = ($p = $config->lookupProperty('modules')) !== null ?
            Text::split($p->value(), Text::COMMA) :
            [];
        $stack = $moduleNames;
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
     * @var CodeRage\Build\Engine
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
