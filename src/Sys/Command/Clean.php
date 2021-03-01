<?php

/**
 * Defines the class CodeRage\Sys\Command\Clean
 *
 * File:        CodeRage/Sys/Command/Clean.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Command;

/**
 * Implements "crush clean"
 */
final class Clean extends Base {

    /**
     * Constructs an instance of CodeRage\Sys\Command\Clean
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
