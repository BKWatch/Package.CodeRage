<?php

/**
 * Defines the class CodeRage\Build\Command\Build
 *
 * File:        CodeRage/Build/Command/Build.php
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
 * Implements "crush build"
 */
final class Build extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Build
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'build',
            'description' => 'Builds a project'
        ]);
        $this->addConfigOptions();
    }

    protected function doExecute()
    {
        return $this->createEngine()->build([
            'setProperties' => $this->setProperties(),
            'unsetProperties' => $this->unsetProperties()
        ]);
    }
}
