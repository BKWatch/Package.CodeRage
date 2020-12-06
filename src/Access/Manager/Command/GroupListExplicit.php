<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\GroupListExplicit
 *
 * File:        CodeRage/Access/Manager/Command/GroupListExplicit.php
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
use CodeRage\Access\ResourceType;
use CodeRage\Access\Resource_;

/**
 * Implements the command 'group-list-explicit'
 */
final class GroupListExplicit extends \CodeRage\Access\Manager\Command {

    /**
     * @var int
     */
    const MAX_ROWS = 200;

    public function __construct()
    {
        parent::__construct([
            'name' => 'group-list-explicit',
            'description' =>
                'Lists members that have been explicitly added to a group',
            'params' =>
                [
                    [
                        'name' => 'group',
                        'description' => 'The group',
                        'type' => 'descriptor[group]',
                        'required' => true
                    ],
                    [
                        'name' => 'types',
                        'description' =>
                            'The comma-separated list of resource types to ' .
                            'return',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'from',
                        'description' =>
                            'The 0-based index of the first group member to ' .
                            'return',
                        'type' => 'int'
                    ],
                    [
                        'name' => 'to',
                        'description' =>
                            'The 0-based index of the first group member to ' .
                            'return',
                        'type' => 'int'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $query =
            'SELECT m.member
             FROM AccessGroupMember m
             JOIN AccessResource r
               ON r.RecordID = m.member
             WHERE m.groupid = %i';
        if (isset($params['types'])) {
            $ids = [];
            foreach (preg_split('/\s*,\s*/', trim($params['types'])) as $t)
                $ids[] = ResourceType::load(['name' => $t])->id();
            $query .= ' AND r.type in (' . join(',', $ids) . ')';
        }
        $query .= ' ORDER BY m.RecordID';
        $search =
            new \CodeRage\WebService\Search([
                    'query' => $query,
                    'queryParams' => [$params['group']->id()],
                    'fields' => ['member' => 'int'],
                    'maxRows' => self::MAX_ROWS,
                    'transform' =>
                        function($row)
                        {
                            return Resource_::load(['id' => $row['member']]);
                        }
                ]);
        [$from, $to] = Array_::values($params, ['from', 'to']);
        return $search->execute([
                   'from' => $from,
                   'to' => $to
               ]);
    }
}
