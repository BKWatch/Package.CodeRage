<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\UserList
 *
 * File:        CodeRage/Access/Manager/Command/UserList.php
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
use CodeRage\Util\Array_;

/**
 * Implements the command 'user-list'
 */
final class UserList extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'user-list',
            'description' => 'Lists all users',
            'params' =>
                [
                    [
                        'name' => 'disabled',
                        'description' =>
                            'List only users with this disabled status',
                        'type' => 'boolean'
                    ],
                    [
                        'name' => 'retired',
                        'description' =>
                            'List only users with this retired status',
                        'type' => 'boolean'
                    ],
                    [
                        'name' => 'sort',
                        'description' =>
                            "The sort criteria: supported field names are " .
                            "id, username, created, disabled, and retired",
                        'type' => 'string'
                    ],
                    [
                        'name' => 'from',
                        'description' =>
                            'The 0-based index of the first user to return',
                        'type' => 'int'
                    ],
                    [
                        'name' => 'to',
                        'description' =>
                            'The 0-based index of the last user to return',
                        'type' => 'int'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $query =
            'SELECT u.RecordID AS id, u.username,
                    u.CreationDate AS created,
                    r.disabled, r.retired
             FROM AccessUser u
             JOIN AccessResource r
               ON r.RecordID = u.resource
             ORDER BY u.CreationDate';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'fields' =>
                        [
                            'id' => 'int',
                            'username' => 'string',
                            'created' => 'datetime',
                            'disabled' => 'datetime',
                            'retired' => 'datetime'
                        ],
                    'maxRows' => self::MAX_ROWS
                ]);
        [$from, $to, $disabled, $retired, $sort] =
            Array_::values($params, [
                'from', 'to', 'disabled', 'retired', 'sort'
            ]);
        $filters = [];
        if ($disabled !== null)
            $filters[] = $disabled ? 'disabled,exists' : 'disabled,notexists';
        if ($retired !== null)
            $filters[] = $retired ? 'retired,exists' : 'retired,notexists';
        return $search->execute([
                   'from' => $from,
                   'to' => $to,
                   'filters' => $filters,
                   'sort' => $sort
               ]);
    }
}
