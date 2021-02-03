<?php

/**
 * Defines the class CodeRage\Build\Command\Clean
 *
 * File:        CodeRage/Build/Command/Clean.php
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
 * Implements "crush clean"
 */
final class Clean extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Clean
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'clean',
            'description' => 'Removes generated file'
        ]);
    }

    protected function doExecute()
    {
        return $this->createEngine()->clean();
    }
}
