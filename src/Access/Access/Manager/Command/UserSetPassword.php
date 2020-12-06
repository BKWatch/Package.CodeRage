<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\UserSetPassword
 *
 * File:        CodeRage/Access/Manager/Command/UserSetPassword.php
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
 * Implements the command 'user-create'
 */
final class UserSetPassword extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'user-set-password',
            'description' => "Sets a user's password",
            'params' =>
                [
                    [
                        'name' => 'user',
                        'description' => 'The user',
                        'type' => 'descriptor[user]',
                        'required' => true
                    ],
                    [
                        'name' => 'password',
                        'description' => 'The password',
                        'type' => 'string',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $params['user']->setPassword($params['password']);
    }
}
