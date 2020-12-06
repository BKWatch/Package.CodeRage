<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupListAncestors
 *
 * File:        CodeRage/Access/Manager/Command/GroupListAncestors.php
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
 * Implements the command 'group-list-ancestors'
 */
final class GroupListAncestors extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'group-list-ancestors',
            'description' => 'Lists ancestors of a group',
            'params' =>
                [
                    [
                        'name' => 'group',
                        'description' => 'The group',
                        'type' => 'descriptor[group]',
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
             FROM AccessGroupAncestor
             WHERE descendant = %i
             ORDER BY ancestor';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'queryParams' => [$params['group']->id()],
                    'fields' => ['ancestor' => 'int'],
                    'distinct' => true,
                    'maxRows' => self::MAX_ROWS,
                    'transform' =>
                        function($row)
                        {
                            return Group::load(['id' => $row['ancestor']]);
                        }
                ]);
        [$from, $to] = Array_::values($params, ['from', 'to']);
        return $search->execute([
                   'from' => $from,
                   'to' => $to
               ]);
    }
}
