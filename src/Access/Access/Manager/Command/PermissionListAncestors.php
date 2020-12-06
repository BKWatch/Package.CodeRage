<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionListAncestors
 *
 * File:        CodeRage/Access/Manager/Command/PermissionListAncestors.php
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
use CodeRage\Util\Array_;

/**
 * Implements the command 'permission-list-ancestors'
 */
final class PermissionListAncestors extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-list-ancestors',
            'description' => 'Lists ancestors of a permission',
            'params' =>
                [
                    [
                        'name' => 'permission',
                        'description' => 'The permission',
                        'type' => 'descriptor[perm]',
                        'required' => true
                    ],
                    [
                        'name' => 'from',
                        'description' =>
                            'The 0-based index of the first ancestor to ' .
                            'return',
                        'type' => 'int'
                    ],
                    [
                        'name' => 'to',
                        'description' =>
                            'The 0-based index of the last ancestor to ' .
                            'return',
                        'type' => 'int'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $query =
            'SELECT ancestor
             FROM AccessPermissionAncestor
             WHERE descendant = %i
             ORDER BY ancestor';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'queryParams' => [$params['permission']->id()],
                    'fields' => ['ancestor' => 'int'],
                    'distinct' => true,
                    'maxRows' => self::MAX_ROWS,
                    'transform' =>
                        function($row)
                        {
                            return Permission::load(['id' => $row['ancestor']]);
                        }
                ]);
        [$from, $to] = Array_::values($params, ['from', 'to']);
        return $search->execute([
                   'from' => $from,
                   'to' => $to
               ]);
    }
}
