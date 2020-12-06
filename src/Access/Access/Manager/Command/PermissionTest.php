<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionTest
 *
 * File:        CodeRage/Access/Manager/Command/PermissionTest.php
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
use CodeRage\Access\Permission;

/**
 * Implements the command 'permission-grant'
 */
final class PermissionTest extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-test',
            'description' => 'Checks whether access has been granted to a user',
            'params' =>
                [
                    [
                        'name' => 'permission',
                        'description' => 'The permission',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ],
                    [
                        'name' => 'user',
                        'description' => 'The user',
                        'type' => 'descriptor[user]',
                        'required' => true
                    ],
                    [
                        'name' => 'resource',
                        'description' => 'The target resource',
                        'type' => 'descriptor',
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        return Permission::test($params);
    }
}
