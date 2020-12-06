<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionGrant
 *
 * File:        CodeRage/Access/Manager/Command/PermissionGrant.php
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
final class PermissionGrant extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-grant',
            'description' => 'Grants a permission to a group of users',
            'params' =>
                [
                    [
                        'name' => 'permission',
                        'description' => 'The permission',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ],
                    [
                        'name' => 'grantee',
                        'description' => 'The group of users',
                        'type' => 'descriptor[group]',
                        'required' => true
                    ],
                    [
                        'name' => 'target',
                        'description' =>
                            'The group of resources to which access is to be ' .
                            'granted',
                        'type' => 'descriptor[group]',
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        Permission::grant($params);
    }
}
