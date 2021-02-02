<?php

/**
 * Defines the class CodeRage\Build\Command\Sync
 *
 * File:        CodeRage/Build/Command/Sync.php
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
 * Implements "crush sync"
 */
final class Sync extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Sync
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
