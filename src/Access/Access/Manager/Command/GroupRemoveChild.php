<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupRemoveChild
 *
 * File:        CodeRage/Access/Manager/Command/GroupRemoveChild.php
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
 * Implements the command 'group-remove-child'
 */
final class GroupRemoveChild extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'group-remove-child',
            'description' =>
                "Removes a group from another group's list of children",
            'params' =>
                [
                    [
                        'name' => 'parent',
                        'description' => 'The parent group',
                        'type' => 'descriptor[group]',
                        'required' => true
                    ],
                    [
                        'name' => 'child',
                        'description' => 'The child group',
                        'type' => 'descriptor[group]',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['parent']->removeChild($params['child']);
    }
}
