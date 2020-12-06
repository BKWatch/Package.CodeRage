<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionAddChild
 *
 * File:        CodeRage/Access/Manager/Command/PermissionAddChild.php
 * Date:        Fri Jan  4 13:45:21 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Manager\Command;

use CodeRage\Access\Manager;

/**
 * Implements the command 'permission-add-child'
 */
final class PermissionAddChild extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-add-child',
            'description' =>
                "Adds a permission to another permission's list of children",
            'params' =>
                [
                    [
                        'name' => 'parent',
                        'description' => 'The parent permission',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ],
                    [
                        'name' => 'child',
                        'description' => 'The child permission',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['parent']->addChild($params['child']);
    }
}
