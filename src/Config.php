<?php

/**
 * Defines the class CodeRage\Config
 *
 * File:        CodeRage/Config.php
 * Date:        Mon Dec  7 01:47:24 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use Exception;
use CodeRage\Build\ProjectConfig;
use CodeRage\Util\Args;

/**
 * Provides access to a system-wide configuration
 */
final class Config {

    /**
     * @var string
     */
    public const PROJECT_CONFIG = 'project.xml';

        /*
         * Methods for accessing the current configuration
         */

    /**
     * Returns the current configuration
     *
     * @return CodeRage\Config
     */
    public static function current(): ProjectConfig
    {
        if (self::$current === null)
            self::$current = new \CodeRage\Build\Config\Builtin;
        return self::$current;
    }

    /**
     * Replaces the current configuration
     *
     * @param CodeRage\Build\ProjectConfig $current The new configuration
     * @return CodeRage\Build\ProjectConfig The previous configuration
     */
    public static function setCurrent(ProjectConfig $current): ?ProjectConfig
    {
        $prev = self::$current;
        self::$current = $current;
        return $prev;
    }

    /**
     * Returns the project root directory
     *
     * @return string
     */
    public static function projectRoot(bool $throwOnError = true): ?string
    {
        if (self::$projectRoot === null) {
            for ($dir = getcwd() ; ; $dir = $parent) {
                if (file_exists(File::join($dir, self::PROJECT_CONFIG))) {
                    self::$projectRoot = $dir;
                    break;
                }
                if (($parent = dirname($dir)) == $dir) {
                    break;
                }
            }
            if (self::$projectRoot === null && $throwOnError) {
                throw new \Exception(
                    "Can't determine project root: no project configuration " .
                        "found in current directory or its ancestors"
                );
            }
        }
        return self::$projectRoot;
    }

    /**
     * The project root directory
     *
     * @var string
     */
    private static $projectRoot;

    /**
     * The currently installed configuration
     *
     * @var CodeRage\Config
     */
    private static $current;
}
