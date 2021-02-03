<?php

/**
 * Defines the class CodeRage\Access
 *
 * File:        CodeRage/Access.php
 * Date:        Sun Jun 10 12:45:50 EDT 2012
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use Throwable;
use CodeRage\Access\Group;
use CodeRage\Access\Permission;
use CodeRage\Access\ResourceType;
use CodeRage\Access\User;
use CodeRage\Db;
use CodeRage\Error;

final class Access {

    /**
     * Initializes the access control system for the default data source
     */
    public static function initialize()
    {
        $db = new Db;
        $db->beginTransaction();
        try {

            // Create resource types
            ResourceType::create([
                'name' => 'string',
                'title' => 'String',
                'description' => 'A sequence of characters'
            ]);
            ResourceType::create([
                'name' => 'any',
                'title' => 'Any',
                'description' => 'Any resource'
            ]);
            ResourceType::create([
                'name' => 'none',
                'title' => 'None',
                'description' => 'No resource'
            ]);
            ResourceType::create([
                'name' => 'user',
                'title' => 'User',
                'description' => 'A user',
                'tableName' => 'AccessUser',
                'columnName' => 'username'
            ]);
            ResourceType::create([
                'name' => 'group',
                'title' => 'Group',
                'description' => 'A group of resources',
                'tableName' => 'AccessGroup',
                'columnName' => 'name'
            ]);
            ResourceType::create([
                'name' => 'perm',
                'title' => 'Permission',
                'description' => 'A permission',
                'tableName' => 'AccessPermission',
                'columnName' => 'name'
            ]);
            ResourceType::create([
                'name' => 'auth',
                'title' => 'Authorization Token',
                'description' =>
                    'An alphanumeric identifier used to authenticate',
                'tableName' => 'AccessAuthToken',
                'columnName' => 'value'
            ]);

            // Create permissions
            $any =
                Permission::create([
                    'name' => Permission::UNIVERSAL,
                    'title' => 'Any',
                    'description' => 'Unrestricted access to a resource',
                    'domain' => 'any'
                ]);
            if ($any->id() != Permission::UNIVERSAL_ID)
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            'Universal permission has incorrect ID: expected ' .
                            Permission::UNIVERSAL_ID . '; found ' . $any->id()
                    ]);
            $view =
                Permission::create([
                    'name' => 'view',
                    'title' => 'View',
                    'description' => 'Permission to view a resource',
                    'domain' => 'any'
                ]);
            $modify =
                Permission::create([
                    'name' => 'modify',
                    'title' => 'Modify',
                    'description' => 'Permission to modify a resource',
                    'domain' => 'any'
                ]);
            $use =
                Permission::create([
                    'name' => 'delete',
                    'title' => 'Delete',
                    'description' => 'Permission to delete a resource',
                    'domain' => 'any'
                ]);
            $use =
                Permission::create([
                    'name' => 'use',
                    'title' => 'Use',
                    'description' =>
                        'Permission to use a resource for its intended purpose',
                    'domain' => 'any'
                ]);
            $list =
                Permission::create([
                    'name' => 'list',
                    'title' => 'List',
                    'description' =>
                        'Permission to list the members of a group',
                    'domain' => 'group'
                ]);
            $add =
                Permission::create([
                    'name' => 'add-member',
                    'title' => 'Add Member',
                    'description' => 'Permission to add members to a group',
                    'domain' => 'group'
                ]);
            $remove =
                Permission::create([
                    'name' => 'remove-member',
                    'title' => 'Remove Member',
                    'description' =>
                        'Permission to remove members from a group',
                    'domain' => 'group'
                ]);
            $modify->addChild($view);
            $use->addChild($view);

            // Create groups
            $any =
                Group::create([
                    'name' => Group::UNIVERSAL,
                    'title' => 'Universal Group',
                    'description' => 'Group that is an ancestor of all groups',
                    'domain' => 'any'
                ]);
            if ($any->id() != Group::UNIVERSAL_ID)
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            'Universal group has incorrect ID: expected ' .
                            Group::UNIVERSAL_ID . '; found ' . $any->id()
                    ]);

            // Create users
            $root = User::create('root');
            if ($root->id() != User::ROOT)
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            'Root user has incorrect ID: expected ' .
                            User::ROOT . '; found ' . $root->id()
                    ]);
            $anonymous = User::create('anonymous');
            $sql = 'UPDATE AccessUser set RecordID = %i WHERE RecordID = %i';
            $db->query($sql, User::ANONYMOUS, $anonymous->id());

            // Create views
            $sql =
                'CREATE VIEW AccessCheck AS
                 SELECT p.name AS permission,
                        u.RecordID AS userid,
                        u.username AS username,
                        tr.RecordID AS resource
                 FROM AccessGrant g
                 JOIN AccessPermissionAncestor pa
                   ON pa.ancestor = g.permission
                 JOIN AccessPermission p
                   ON p.RecordID = pa.descendant
                 JOIN AccessGroupAncestor ga
                   ON ga.ancestor = g.grantee
                 JOIN AccessGroupMember gm
                   ON gm.groupid = ga.descendant
                 JOIN AccessResource gr
                   ON gr.RecordID = gm.member AND
                      gr.disabled IS NULL AND
                      gr.retired IS NULL
                 JOIN AccessUser u
                   ON u.resource = gm.member
                 JOIN AccessGroupAncestor ta
                   ON ta.ancestor = g.target
                 JOIN AccessGroupMember tm
                   ON tm.groupid = ta.descendant
                 JOIN AccessResource tr
                   ON tr.RecordID = tm.member AND
                      tr.disabled IS NULL AND
                      tr.retired IS NULL';
            $db->query($sql);
            $sql =
                'CREATE VIEW AccessCheckAll AS
                 SELECT p.name AS permission,
                        u.RecordID AS userid,
                        u.username AS username,
                        tr.RecordID AS resource,
                        tr.disabled,
                        tr.retired
                 FROM AccessGrant g
                 JOIN AccessPermissionAncestor pa
                   ON pa.ancestor = g.permission
                 JOIN AccessPermission p
                   ON p.RecordID = pa.descendant
                 JOIN AccessGroupAncestor ga
                   ON ga.ancestor = g.grantee
                 JOIN AccessGroupMember gm
                   ON gm.groupid = ga.descendant
                 JOIN AccessResource gr
                   ON gr.RecordID = gm.member AND
                      gr.disabled IS NULL AND
                      gr.retired IS NULL
                 JOIN AccessUser u
                   ON u.resource = gm.member
                 JOIN AccessGroupAncestor ta
                   ON ta.ancestor = g.target
                 JOIN AccessGroupMember tm
                   ON tm.groupid = ta.descendant
                 JOIN AccessResource tr
                   ON tr.RecordID = tm.member';
            $db->query($sql);
        } catch (Throwable $e) {
            $db->rollback();
            throw new
                Error([
                    'message' => 'Failed initializing access control system',
                    'inner' => $e
                ]);
        }
        $db->commit();
    }

    /**
     * Returns true if the access control system has been initialized for the
     * default data source
     *
     * @return boolean
     */
    public static function initialized()
    {
        static $initialized = [];
        $id = (new Db)->params()->id();
        if (!isset($initialized[$id]) || !$initialized[$id]) {
            $initialized[$id] = (boolean)
                (new Db)->fetchValue(
                    'SELECT COUNT(*) {i}
                     FROM AccessUser
                     WHERE RecordID = %i',
                    User::ANONYMOUS
                );
        }
        return $initialized[$id];
    }

    /**
     * Deletes all data from the tables populated by initialize(). Useful only
     * for testing; may fail because of foreign key constraints.
     */
    public static function cleanup()
    {

        $db = new Db;
        $db->beginTransaction();
        try {
            $tables =
                [
                    'AccessUser',
                    'AccessGrant',
                    'AccessGroupMember',
                    'AccessGroupParent',
                    'AccessGroupAncestor',
                    'AccessGroup',
                    'AccessPermissionParent',
                    'AccessPermissionAncestor',
                    'AccessPermission',
                    'AccessResource',
                    'AccessResourceType'
                ];
            foreach ($tables as $t) {
                $db->query("DELETE FROM $t");
            }
        } catch (Throwable $e) {
            $db->rollback();
            throw new
                Error([
                    'message' => 'Failed cleaning up access control system',
                    'inner' => $e
                ]);
        }
        $db->commit();
    }
}
