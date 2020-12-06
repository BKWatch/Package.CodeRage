<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionDelete
 *
 * File:        CodeRage/Access/Manager/Command/PermissionDelete.php
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
 * Implements the command 'permission-delete'
 */
final class PermissionDelete extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-delete',
            'description' => 'Deletes a permission',
            'params' =>
                [
                    [
                        'name' => 'permission',
                        'description' => 'The permission to delete',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['permission']->delete();
    }
}
