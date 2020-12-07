<?php

/**
 * Defines the class CodeRage\Build\Target\Default_.
 *
 * File:        CodeRage/Build/Target/Default_.php
 * Date:        Sun Jan 11 17:20:20 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Target;

use Exception;
use CodeRage\Build\Run;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Log;
use CodeRage\Util\ErrorHandler;

/**
 * Copies files to the web server root.
 */
class Default_ extends Basic {

    /**
     * Default name of the web server root directory.
     *
     * @var in
     */
    const WEBSERVER_ROOT = 'www';

    /**
     * Default name of the directories containing files to be moved
     * automatically into the web server root.
     *
     * @var in
     */
    const PUBLIC_DIRECTORY_NAME = '__www__';

    /**
     * Constructs a CodeRage\Build\Target\Default_.
     *
     * @param CodeRage\Build\ProjectConfig $config
     */
    function __construct(\CodeRage\Build\ProjectConfig $config)
    {
        parent::__construct('__default');
    }

    /**
     * Returns an instance of CodeRage\Build\Info describing this target.
     *
     * @return CodeRage\Build\Info.
     */
    function info()
    {
        return new
            \CodeRage\Build\Info([
                'label' => 'Default Target',
                'description' =>
                    'Target added automatically to projects whose ' .
                    'primary configuration file is not XML'
            ]);
    }

    /**
     * Copies contents of __WWW__ directories to the web server root.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        if ($str = $run->getStream(Log::INFO))
            $str->write('Copying public files to web server root');
        $toolsRoot = $run->buildConfig()->toolsPath();
        $projectRoot = $run->projectRoot();
        $pub =
            ($prop = $run->projectConfig()->lookupProperty('public_directory'))
                ? $prop->value()
                : self::WEBSERVER_ROOT;
        $pairs = []; // List of ($src, $dest) pairs
        $debug = $run->getStream(Log::DEBUG);
        $handler = new ErrorHandler;
        $stack = [["$toolsRoot/CodeRage", "$projectRoot/$pub/CodeRage"]];
        while (sizeof($stack)) {
            list($src, $dest) = array_pop($stack);
            if ($debug)
                $debug->write("Processing pair ('$src','$dest')");
            $dir = $handler->_opendir($src);
            if ($handler->errno())
                throw new
                    Error(['message' =>
                        $handler->formatError(
                             'Failed copying public files to web server root'
                        )
                    ]);
            if ($debug)
                $debug->write("Reading directory '$src'");
            while (($file = $handler->_readdir($dir)) !== false) {
                if ($debug)
                    $debug->write("Processing entry '$file'");
                if ($handler->errno())
                    throw new
                        Error(['message' =>
                            $handler->formatError(
                                 'Failed copying public files to web server ' .
                                 'root'
                            )
                        ]);
                if ($file == '.' || $file == '..')
                    continue;
                $src2 = "$src/$file";
                $dest2 = "$dest/$file";
                if (is_file($src2))
                    continue;
                if ($file == self::PUBLIC_DIRECTORY_NAME) {
                    $this->copyDirectory($run, $handler, $src2, $dest);
                } else {
                    if ($debug)
                        $debug->write(
                            "Adding pair ('$src2','$dest2') to stack"
                        );
                    $stack[] = [$src2, $dest2];
                }
            }
            $handler->_closedir($dir);
        }
    }

    /**
     * Recursively copies the contents of $src to $dest, registering each
     * copied file with $run.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param CodeRage\Util\ErrorHandler $handler
     * @param string $src
     * @param string $dest
     */
    private function copyDirectory(Run $run,
        ErrorHandler $handler, $src, $dest)
    {
        $debug = $run->getStream(Log::DEBUG);
        if ($debug)
            $debug->write("Copying directory '$src' to '$dest'");
        $handler = new ErrorHandler;
        $stack = [[$src, $dest]];
        while (sizeof($stack)) {
            list ($s, $d) = array_pop($stack);
            if ($debug)
                $debug->write("Processing pair ('$s','$d')");
            $dir = $handler->_opendir($s);
            if ($handler->errno())
                throw new
                    Exception(
                        $handler->formatError(
                            'Failed copying public files to web server root'
                        )
                    );
            if ($debug)
                $debug->write("Reading directory '$s'");
            while (($f = $handler->_readdir($dir)) !== false) {
                if ($debug)
                    $debug->write("Processing entry '$f'");
                if ($handler->errno())
                    throw new
                        Exception(
                            $handler->formatError(
                                'Failed copying public files to web server root'
                            )
                        );
                if ($f == '.' || $f == '..')
                    continue;
                $s2 = "$s/$f";
                $d2 = "$d/$f";
                if (is_file($s2)) {
                    if (is_dir($d2))
                        throw new
                            Exception(
                                "Failed copying public files to web server " .
                                "root: '$d2' is a directory"
                            );
                    File::mkdir(dirname($d2), 0755);
                    File::copy($s2, $d2);
                    $run->recordGeneratedFile($d2);
                } else {
                    if ($debug)
                        $debug->write("Creating directory '$d2'");
                    File::mkdir(dirname($d2), 0755);
                    if (is_file($d2) || is_link($d2))
                        throw new
                            Exception(
                                "Failed copying public files to web server " .
                                "root: '$d2' is not a directory"
                            );
                    $stack[] = [$s2, $d2];
                }
            }
            $handler->_closedir($s);
        }
    }
}
