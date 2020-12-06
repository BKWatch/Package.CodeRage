<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionListChildren
 *
 * File:        CodeRage/Access/Manager/Command/PermissionListChildren.php
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
 * Implements the command 'permission-list-children'
 */
final class PermissionListChildren extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-list-children',
            'description' => 'Lists the children a permission',
            'params' =>
                [
                    [
                        'name' => 'permission',
                        'description' => 'The permission',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        return ['children' => $params['permission']->children()];
    }
}
