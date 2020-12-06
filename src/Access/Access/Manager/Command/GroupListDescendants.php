<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupListDescendants
 *
 * File:        CodeRage/Access/Manager/Command/GroupListDescendants.php
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
 * Implements the command 'group-list-descendants'
 */
final class GroupListDescendants extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'group-list-descendants',
            'description' => 'Lists descendants of a group',
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
                            'The 0-based index of the first descendant to ' .
                            'return',
                        'type' => 'int'
                    ],
                    [
                        'name' => 'to',
                        'description' =>
                            'The 0-based index of the last descendant to ' .
                            'return',
                        'type' => 'int'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $query =
            'SELECT descendant
             FROM AccessGroupAncestor
             WHERE ancestor = %i
             ORDER BY descendant';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'queryParams' => [$params['group']->id()],
                    'fields' => ['descendant' => 'int'],
                    'distinct' => true,
                    'maxRows' => self::MAX_ROWS,
                    'transform' =>
                        function($row)
                        {
                            return Group::load(['id' => $row['descendant']]);
                        }
                ]);
        [$from, $to] = Array_::values($params, ['from', 'to']);
        return $search->execute([
                   'from' => $from,
                   'to' => $to
               ]);
    }
}
