<?php

/**
 * Defines the class CodeRage\Build\Command\Info
 *
 * File:        CodeRage/Build/Command/Info.php
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
 * Implements "crush info"
 */
final class Info extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Sync
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'info',
            'description' =>
                'Displays configuration information about a project'
        ]);
    }

    protected function doExecute()
    {
        echo "Hello!\n";
    }
}
