<?php

/**
 * Defines the class CodeRage\Build\Command\Install
 *
 * File:        CodeRage/Build/Command/Install.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Command;

/**
 * Implements "crush install"
 */
final class Install extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Install
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'install',
            'description' => 'Installs or updates project components'
        ]);
    }

    protected function doExecute()
    {
        return $this->createEngine()->install();
    }
}
