<?php

/**
 * Defines the interface CodeRage\Build\Config\Reader.
 *
 * File:        CodeRage/Build/Config/Reader.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

/**
 * Interface implemented by components that read configurations from
 * the command-line, the environment, XML documents, or other sources.
 */
interface Reader {

    /**
     * Returns a property bundle.
     *
     * @return CodeRage\Build\ProjectConfig
     */
    function read();
}
