<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupRemove
 *
 * File:        CodeRage/Access/Manager/Command/GroupRemove.php
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
 * Implements the command 'group-remove'
 */
final class GroupRemove extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'group-remove',
            'description' => 'Removes resources from a group',
            'params' =>
                [
                    [
                        'name' => 'group',
                        'description' => 'The group',
                        'type' => 'descriptor[group]',
                        'required' => true
                    ],
                    [
                        'name' => 'members',
                        'description' =>
                            'The comma-separated list of resources to remove',
                        'type' => 'string',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $members = [];
        $descriptors = preg_split('/\s*,\s*/', trim($params['members']));
        foreach ($descriptors as $desc)
            $members[] = $manager->loadDescriptor($desc);
        foreach ($members as $m)
            $params['group']->remove($m);
    }
}
