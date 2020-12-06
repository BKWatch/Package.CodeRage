<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\PermissionListAll
 *
 * File:        CodeRage/Access/Manager/Command/PermissionListAll.php
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
 * Implements the command 'permission-list-all'
 */
final class PermissionListAll extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'permission-list-all',
            'description' => 'Lists all permissions',
            'params' =>
                [
                    [
                        'name' => 'from',
                        'description' =>
                            'The 0-based index of the first permission to return',
                        'type' => 'int'
                    ],
                    [
                        'name' => 'to',
                        'description' =>
                            'The 0-based index of the last permission to return',
                        'type' => 'int'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $query =
            'SELECT RecordID
             FROM AccessPermission
             ORDER BY RecordID';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'fields' => ['RecordID' => 'int'],
                    'maxRows' => self::MAX_ROWS,
                    'transform' =>
                        function($row)
                        {
                            return Permission::load(['id' => $row['RecordID']]);
                        }
                ]);
        [$from, $to] = Array_::values($params, ['from', 'to']);
        return $search->execute([
                   'from' => $from,
                   'to' => $to
               ]);
    }
}
