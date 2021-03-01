<?php

/**
 * Defines the interface CodeRage\Sys\Config\Reader.
 *
 * File:        CodeRage/Sys/Config/Reader.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

/**
 * Interface implemented by components that read configurations from
 * the command-line, the environment, XML documents, or other sources.
 */
interface Reader {

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Sys\ProjectConfig
     */
    public function read(): \CodeRage\Sys\ProjectConfig;
}
