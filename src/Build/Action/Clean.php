<?php

/**
 * Defines the class CodeRage\Build\Action\Clean.
 *
 * File:        CodeRage/Build/Action/Clean.php
 * Date:        Tue Jan 06 16:51:57 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Action;

use CodeRage\Build\Run;
use CodeRage\Error;
use CodeRage\Log;

/**
 * @ignore
 */
require_once('CodeRage/File/getContents.php');
require_once('CodeRage/Text/split.php');

/**
 * Represents the 'clean' build action.
 */
class Clean implements \CodeRage\Build\Action {

    /**
     * Returns 'clean'.
     *
     * @return string
     */
    function name() { return 'clean'; }

    /**
     * Checks the command line of the given run for consistency with the 'clean'
     * build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {
        $commandLine = $run->commandLine();
        if (sizeof($commandLine->arguments()))
            throw new
                Error([
                    'message' =>
                        "Targets are not currently supported by the option " .
                         "--clean"
                ]);
        $common = \CodeRage\Text\split(\CodeRage\Build\CommandLine::COMMON_OPTIONS);
        foreach ($commandLine->options() as $opt) {
            if ($opt->hasExplicitValue()) {
                $long = $opt->longForm();
                if ($long != 'clean' && !in_array($long, $common))
                    throw new
                        Error(['message' =>
                            "The option --$long cannot be combined with " .
                            "the option --clean"
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
     * Executes the 'clean' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        if (!$run->buildConfig()->projectConfigFile())
            throw new Error(['message' => "Missing project definition file"]);
        $path = $run->projectRoot() . '/' . Run::GENERATED_FILE_LOG;
        if (!file_exists($path)) {
            if ($str = $run->getStream(Log::WARNING))
                $str->write(
                    "List of generated files is missing; nothing to do"
                );
            return;
        }
        $files = explode("\n", rtrim(\CodeRage\File\getContents($path)));
        for ($z = sizeof($files) - 1; $z != -1; --$z) {
            $f = $files[$z];
            if (file_exists($f) && @unlink($f) === false) {
                $run->log()->logError("Failed removing file: $f");
            } else {
                array_splice($files, $z, 1);
            }
        }
        if (sizeof($files)) {
            $content = join("\n", $this->files);
            $handler = new \CodeRage\Util\ErrorHandler;
            if (!$this->handler->_file_put_contents($path, $content))
                if ($str = $this->log->getStream(Log::ERROR))
                    $str->write(
                        $this->handler->formatError(
                            "Failed saving list of generated files to '$path'"
                        )
                    );
        }
    }
}
