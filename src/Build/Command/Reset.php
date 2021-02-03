<?php

/**
 * Defines the class CodeRage\Build\Command\Reset
 *
 * File:        CodeRage/Build/Command/Reset.php
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
 * Implements "crush reset"
 */
final class Reset extends Base {

    /**
     * Constructs an instance of CodeRage\Build\Command\Reset
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'reset',
            'description' =>
                'Deletes generated files and clears configuration variables ' .
                'set on the command line'
        ]);
    }

    protected function doExecute()
    {
        return $this->createEngine()->reset();
    }
}
