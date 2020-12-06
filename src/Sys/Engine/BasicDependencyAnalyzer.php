<?php

/**
 * Defines the class CodeRage\Sys\Engine\BasicDependencyAnalyzer
 *
 * File:        CodeRage/Sys/Engine/BasicDependencyAnalyzer.php
 * Date:        Fri Nov 13 17:57:21 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use InvalidArgumentException;
use Composer\Semver\Semver;
use MJS\TopSort\Implementations\FixedArraySort;
use CodeRage\Error;
use CodeRage\Sys\Module\Initial;
use CodeRage\Sys\Module\Terminal;
use CodeRage\Sys\Util;
use CodeRage\Sys\BasicModule;

/**
 * An implementation of CodeRage\Sys\Engine\DependencyAnalyzerInterface
 */
final class BasicDependencyAnalyzer implements DependencyAnalyzerInterface
{
    /**
     * Generates a module store from the given engine configuration
     *
     * @param CodeRage\Sys\Engine\ConfigInterface $config
     * @return CodeRage\Sys\Engine\ModuleStore
     */
    public function analyze(ConfigInterface $config): ModuleStoreInterface
    {
        $modules = $bindings = $dependencies = $errors = [];

        // Load modules
        foreach ($config->getModules() as $m) {
            $name = $m->getName();
            try {
                Util::validateModuleName($name);
                $module = new $name();
                Util::validateModule($module);
                $modules[$name] = $module;
                if (!isset($bindings[$name])) {
                    $bindings[$name] = [];
                }
                $bindings[$name][] = $module->getDataSources();
            } catch (InvalidArgumentException $e) {
                $errors[] = $e;
            }
        }

        // Resolve dependencies
        foreach ($modules as $name => $module) {
            $deps = [];
            foreach ($module->getDependencies() as [$d, $c]) {
                $dep = $modules[$d] ?? null;
                if ($dep === null) {
                    $errors[] = "Missing dependency '$d' for module '$name'";
                } else {
                    $ver = $dep->getVersion();
                    if (!Semver::satisfies($ver, $c)) {
                        $errors[] =
                            "Dependency '$d' for module '$name', with " .
                            "version '$ver', doesn't satisfy constraint " .
                            "'$c'";
                    }
                }
                $deps[] = $d;
            }
            $dependencies[$name] = $deps;
        }
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Failed validating configuration: ' . join('; ', $errors)
            );
        }

        // Sort by dependencies
        $sorter = new FixedArraySort();
        $sorter->setCircularInterceptor(function(array $names) {
            throw new InvalidArgumentException(
                'Found circular dependency among modules: ' . join(' => ', $names)
            );
        });
        foreach ($modules as $name => $module) {
            $sorter->add($name, $dependencies[$name]);
        }
        $sorted = $sorter->sort();

        // Add initial and terminal modules
        $modules[Initial::class] = new Initial();
        $modules[Terminal::class] = new Terminal();
        array_unshift($sorted, Initial::class);
        $sorted[] = Terminal::class;

        // Construct module store
        $store = [];
        foreach ($sorted as $i => $name) {
            $module = $modules[$name];
            $store[$name] = new BasicModuleDescriptor([
                'name' => $name,
                'metadata' =>
                    new BasicModuleDescriptor([
                        'name' => $name,
                        'title' => $module->getTitle(),
                        'description' => $module->getDescription(),
                        'replaces' => $module->getReplaces(),
                        'eventHandlers' => $module->getEventHandlers(),
                        'dataSources' => $module->getDataSource()
                    ]),
                'bindings' => $bindings[$name],
                'rank' => $i
            ]);
        }

        return new BasicModuleStore($store, false);
    }
}
