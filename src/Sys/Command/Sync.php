<?php

/**
 * Defines the class CodeRage\Sys\Command\Sync
 *
 * File:        CodeRage/Sys/Command/Sync.php
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
 * Implements "crush sync"
 */
final class Sync extends Base {

    /**
     * Constructs an instance of CodeRage\Sys\Command\Sync
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'sync',
            'description' =>
                'Synchronizes data in the database with the code base'
        ]);
    }

    protected function doExecute()
    {
        return $this->createEngine()->sync();
    }
}
