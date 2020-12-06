<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupDelete
 *
 * File:        CodeRage/Access/Manager/Command/GroupDelete.php
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
 * Implements the command 'group-delete'
 */
final class GroupDelete extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'group-delete',
            'description' => 'Deletes a group',
            'params' =>
                [
                    [
                        'name' => 'group',
                        'description' => 'The group to delete',
                        'type' => 'descriptor[group]',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['group']->delete();
    }
}
