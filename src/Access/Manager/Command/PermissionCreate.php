<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionCreate
 *
 * File:        CodeRage/Access/Manager/Command/PermissionCreate.php
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
 * Implements the command 'permission-create'
 */
final class PermissionCreate extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-create',
            'description' => 'Creates a permission',
            'params' =>
                [
                    [
                        'name' => 'name',
                        'description' => 'The permission name',
                        'type' => 'string',
                        'required' => true
                    ],
                    [
                        'name' => 'title',
                        'description' => 'The permission title',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'description',
                        'description' => 'The permission description',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'domain',
                        'description' => 'The permission domain',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'owner',
                        'description' => 'The permission owner',
                        'type' => 'descriptor[user]'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['owner'] = $params['owner']->id();
        return Permission::create($params);
    }
}
