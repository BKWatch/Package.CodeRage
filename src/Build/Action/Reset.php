<?php

/**
 * Defines the class CodeRage\Build\Action\Reset.
 *
 * File:        CodeRage/Build/Action/Reset.php
 * Date:        Tue Jan 06 16:51:57 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Action;

use CodeRage\Build\Config\Basic;
use CodeRage\Build\Run;
use CodeRage\Error;
use CodeRage\File;

/**
 * Represents the 'reset' build action.
 */
class Reset implements \CodeRage\Build\Action {

    /**
     * Returns 'reset'.
     *
     * @return string
     */
    function name() { return 'reset'; }

    /**
     * Checks the command line of the given run for consistency with the 'reset'
     * build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {
        $commandLine = $run->commandLine();
        if (sizeof($commandLine->arguments()))
            throw new
                Error(['message' =>
                    "Targets cannot be specified with the option --reset"
                ]);
        $common = \CodeRage\Text\split(\CodeRage\Build\CommandLine::COMMON_OPTIONS);
        foreach ($commandLine->options() as $opt) {
            if ($opt->hasExplicitValue()) {
                $long = $opt->longForm();
                if ($long != 'reset' && !in_array($long, $common))
                    throw new
                        Error(['message' =>
                            "The option --$long cannot be combined with " .
                            "the option --reset"
                        ]);
            }
        }
    }

    /**
     * Returns true.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return boolean
     */
    function requiresProjectConfig(Run $run)
    {
        return false;
    }

    /**
     * Executes the 'reset' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        $clean = new Clean;
        $clean->execute($run);
        $stack = [$run->projectRoot()];
        $handler = new \CodeRage\Util\ErrorHandler;
        while (sizeof($stack)) {
            $dir = array_pop($stack);
            $path = "$dir/.coderage";
            if (file_exists($path))
                if (!File::rm($path))
                    $run->log->logError("Failed removing directory: $path");
            $hnd = $handler->_opendir($dir);
            if ($hnd === false || $handler->errno())
                $run->log->logError(
                    $handler->formatError("Failed listing directory: $file")
                );
            while (($file = readdir($hnd)) !== false) {
                if ($file == '.' || $file == '..')
                    continue;
                $path = "$dir/$file";
                if (is_dir($path))
                    $stack[] = $path;
            }
            @closedir($hnd);
        }
        $config = $run->buildConfig();
        $config->additionalConfigFiles([]);
        $config->setRepositoryType(null);
        $config->setRepositoryUrl(null);
        $config->setRepositoryPath(null);
        $config->setRepositoryUsername(null);
        $config->setCommandLineProperties(new Basic);
        $config->setEnvironmentProperties(new Basic);
    }
}
