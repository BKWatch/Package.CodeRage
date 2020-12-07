<?php

/**
 * Defines the class CodeRage\Build\Config\Reader\CommandLine.
 *
 * File:        CodeRage/Build/Config/Reader/CommandLine.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Reader;

/**
 * Reads collections of properties from the command-line.
 */
class CommandLine implements \CodeRage\Build\Config\Reader {

    /**
     * @var CodeRage\Util\CommandLine
     */
    private $commandLine;

    /**
     * Constructs a CodeRage\Build\Config\Reader\CommandLine
     *
     * @param CodeRage\Util\CommandLine $commandLine
     */
    function __construct(\CodeRage\Build\CommandLine $commandLine)
    {
        $this->commandLine = $commandLine;
    }

    /**
     * Returns an instance of CodeRage\Build\ProjectConfig.
     *
     * @return CodeRage\Build\ProjectConfig
     */
    function read()
    {
        $properties = new \CodeRage\Build\Config\Basic;
        $pattern = '/^([_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*)=(.*)$/i';
        if ($values = $this->commandLine->getValue('s', true)) {
            foreach ($values as $v) {
                if (preg_match($pattern, $v, $match)) {
                    $properties->addProperty(
                        new \CodeRage\Build\Config\Property(
                                $match[1],
                                \CodeRage\Build\ISSET_,
                                $match[2],
                                0,
                                \CodeRage\Build\COMMAND_LINE
                            )
                    );
                } else {
                    throw new \CodeRage\Error("Invalid option: -s $v");
                }
            }
        }
        return $properties;
    }
}
