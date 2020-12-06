<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\UserCreate
 *
 * File:        CodeRage/Access/Manager/Command/UserCreate.php
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
use CodeRage\Access\User;
use CodeRage\Error;
use CodeRage\Util\Array_;

/**
 * Implements the command 'user-create'
 */
final class UserCreate extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'user-create',
            'description' => 'Creates a user',
            'params' =>
                [
                    [
                        'name' => 'username',
                        'description' => 'The username',
                        'type' => 'string',
                        'required' => true
                    ],
                    [
                        'name' => 'password',
                        'description' => 'The password',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'owner',
                        'description' => 'The parent user',
                        'type' => 'descriptor[user]'
                    ],
                    [
                        'name' => 'groups',
                        'description' =>
                            'A list of groups to which the user should be added',
                        'type' => 'string'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $groups = [];
        if (isset($params['groups'])) {
            $descriptors = preg_split('/\s*,\s*/', trim($params['groups']));
            foreach ($descriptors as $i => $desc) {
                $g = $manager->loadDescriptor($desc);
                if (!$g instanceof Group)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => "Invalid group at position $i: $g"
                        ]);
                $groups[] = $g;
            }
        }
        [$username, $password, $owner] =
            Array_::values($params, ['username', 'password', 'owner']);
        $user = User::create($username, $password, $owner);
        foreach ($groups as $g)
            $g->add($user);
        return $user;
    }
}
