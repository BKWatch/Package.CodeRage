<?php

/**
 * Defines the class CodeRage\Sys\Module\Container
 *
 * File:        CodeRage/Sys/Module/Container.php
 * Date:        Mon Mar  1 01:28:20 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Module;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use CodeRage\Sys\Engine;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;

/**
 * PHP-DI integration
 */
final class Container extends \CodeRage\Sys\BasicModule {

    /**
     * @var string
     */
    private const CACHE_DIRECTORY = '.coderage/container';

    /**
     * @var string
     */
    private const PROXIES_FILE = '.coderage/container/proxies';

    /**
     * @var string
     */
    private const SERVICES_FILE = 'services';

    /**
     * Constructs an instance of CodeRage\Sys\Module\Container
     */
    public function __construct()
    {
        parent::__construct([
            'title' => 'Container',
            'description' => 'PHP-DI integration'
        ]);
    }

    public function build(Engine $engine): void
    {
        $this->loadContainerImpl($engine, true);
    }

    /**
     * Loads the compiled container from the cache
     *
     * @param CodeRage\Sys\Engine $engine
     * @return Psr\Container\ContainerInterface
     */
    public function loadContainer(Engine $engine): ContainerInterface
    {
        return $this->loadContainerImpl($engine, false);
    }

    /**
     * Loads the compiled container from the cache
     *
     * @param CodeRage\Sys\Engine $engine
     * @return Psr\Container\ContainerInterface
     */
    private function loadContainerImpl(Engine $engine, bool $build): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $projectRoot = Config::projectRoot();
        $cacheDirectory = $projectRoot . '/' . self::CACHE_DIRECTORY;
        $builder->enableCompilation($cacheDirectory);
        $builder->writeProxiesToFile(true, $projectRoot . '/' . self::PROXIES_FILE);

        if ($build) {
            File::rm(self::CACHE_DIRECTORY);

            // Add config service
            $builder->addDefinitions([
                'config' => function() { return Config::current(); }
            ]);

            // Load definitions from modules
            foreach ($engine->moduleStore()->modules() as $module) {
                if (method_exists($module, 'services')) {
                    $builder->addDefinitions($module->services($engine));
                }
            }

            // Load project service definitions
            $path = $projectRoot . '/' . self::SERVICES_FILE . '.php';
            if (file_exists($path)) {
                File::checkFile($path, 0b0100);
                $builder->addDefinitions($path);
            }

            // Load environment-specific service definitions
            $config = $engine->projectConfig();
            if ($env = $config->lookupProperty('environment')) {
                $path =
                    $projectRoot . '/' . self::SERVICES_FILE . '.' .
                    $env->value() . '.php';
                if (file_exists($path)) {
                    File::checkFile($path, 0b0100);
                    $builder->addDefinitions($path);
                }
            }

            if (file_exists($cacheDirectory)) {
                $engine->recordGeneratedFile($projectRoot . '/' . self::CACHE_DIRECTORY);
            }
        }

        $container = $builder->build();
        $container->set('engine', $engine);
        return $container;
    }
}
