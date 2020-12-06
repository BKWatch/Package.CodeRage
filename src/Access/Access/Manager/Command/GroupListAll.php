<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupListAll
 *
 * File:        CodeRage/Access/Manager/Command/GroupListAll.php
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
use CodeRage\Util\Array_;

/**
 * Implements the command 'group-list-all'
 */
final class GroupListAll extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'group-list-all',
            'description' => 'Lists all groups',
            'params' =>
                [
                    [
                        'name' => 'from',
                        'description' =>
                            'The 0-based index of the first group to return',
                        'type' => 'int'
                    ],
                    [
                        'name' => 'to',
                        'description' =>
                            'The 0-based index of the last group to return',
                        'type' => 'int'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $query =
            'SELECT RecordID
             FROM AccessGroup
             ORDER BY RecordID';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'fields' => ['RecordID' => 'int'],
                    'maxRows' => self::MAX_ROWS,
                    'transform' =>
                        function($row)
                        {
                            return Group::load(['id' => $row['RecordID']]);
                        }
                ]);
        [$from, $to] = Array_::values($params, ['from', 'to']);
        return $search->execute([
                   'from' => $from,
                   'to' => $to
               ]);
    }
}
