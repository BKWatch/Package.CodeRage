<?php

/**
 * Defines the class CodeRage\Web\Module
 *
 * File:        CodeRage/Web/Module.php
 * Date:        Wed Dec 16 19:52:11 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Web;

use Exception;
use CodeRage\Sys\Engine;
use CodeRage\Config;
use CodeRage\File;
use CodeRage\Log;
use CodeRage\Util\Args;
use CodeRage\Util\ErrorHandler;

/**
 * Copies files into the web server root
 */
final class Module extends \CodeRage\Sys\BasicModule {

    /**
     * Default name of the web server root directory.
     *
     * @var in
     */
    private const DEFAULT_PUBLIC_DIRECTORY = 'www';

    /**
     * Default name of the directories containing files to be moved
     * automatically into the web server root.
     *
     * @var in
     */
    private const MAGIC_SOURCE_DIRECTORY = '__www__';

    /**
     * Constructs an instance of CodeRage\Web\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Web',
            'description' => 'Copies files into the web server root'
        ]);
    }

    public function build(Engine $engine): void
    {
        if ($str = $engine->log()->getStream(Log::INFO))
            $str->write('Copying public files to web server root');
        $config = $engine->projectConfig();
        $pubDir = ($prop = $config->lookupProperty('public_directory')) ?
            $prop->value() :
            self::DEFAULT_PUBLIC_DIRECTORY;
        $webRoot = Config::projectRoot() . '/' . $pubDir;
        $debug = $engine->log()->getStream(Log::DEBUG);
        $handler = new ErrorHandler;
        $stack = [];
        foreach ($engine->moduleStore()->modules() as $mod) {
            foreach ($mod->webRoots() as $src => $dest) {
                $stack[] = [$src, $dest != '' ? "$webRoot/$dest" : $webRoot];
            }
        }
        while (sizeof($stack)) {
            [$src, $dest] = array_pop($stack);
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
                if (is_file($src2))
                    continue;
                if ($file == self::MAGIC_SOURCE_DIRECTORY) {
                    $this->copyDirectory($engine, $handler, $src2, $dest);
                } else {
                    $dest2 = "$dest/$file";
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
     * copied file with $engine.
     *
     * @param CodeRage\Sys\Engine $engine The build engine
     * @param CodeRage\Util\ErrorHandler $handler
     * @param string $src
     * @param string $dest
     */
    private function copyDirectory(Engine $engine,
        ErrorHandler $handler, $src, $dest)
    {
        $debug = $engine->log()->getStream(Log::DEBUG);
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
                    $engine->recordGeneratedFile($d2);
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
