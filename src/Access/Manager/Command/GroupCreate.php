<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupCreate
 *
 * File:        CodeRage/Access/Manager/Command/GroupCreate.php
 * Date:        Fri Jan  4 13:45:21 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Manager\Command;

use CodeRage\Access\Group;
use CodeRage\Access\Manager;

/**
 * Implements the command 'group-create'
 */
final class GroupCreate extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'group-create',
            'description' => 'Creates a group',
            'params' =>
                [
                    [
                        'name' => 'name',
                        'description' => 'The group name',
                        'type' => 'string',
                        'required' => true
                    ],
                    [
                        'name' => 'title',
                        'description' => 'The group title',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'description',
                        'description' => 'The group description',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'domain',
                        'description' => 'The group domain',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'owner',
                        'description' => 'The group owner',
                        'type' => 'descriptor[user]'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['owner'] = $params['owner']->id();
        return Group::create($params);
    }
}
