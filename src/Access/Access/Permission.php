<?php

/**
 * Defines the class CodeRage\Access\Permission
 *
 * File:        CodeRage/Access/Permission.php
 * Date:        Sun Jun 10 12:45:50 EDT 2012
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use Exception;
use Throwable;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;


/**
 * Represents a permission to that can be granted to a group of users to access
 * a group of resources
 */
final class Permission extends GroupBase {

    /**
     * Derived table definition defining triples [P, U, R] where user U has
     * permission P to access to resource R, and where R is neither disabled nor
     * retired, exposing the following column  names:
     *
     *   permission - The permission name
     *   userid - The user ID of the user granted access
     *   username - The username of the user granted access
     *   resource - The ID of the resource to which access os granted
     *
     * @var string
     */
    const JOIN =
        '(SELECT p.name AS permission,
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
               tr.retired IS NULL)';

    /**
     * Derived table definition defining triples [P, U, R] where user U has
     * permission P to access to resource R, exposing the following column
     * names:
     *
     *   permission - The permission name
     *   userid - The user ID of the user granted access
     *   username - The username of the user granted access
     *   resource - The ID of the resource to which access is granted
     *   disabled - The time the resource was disabled, as a UNIX timestamp
     *   retried - The time the resource was retired (soft-deleted), as a UNIX
     *     timestamp
     *
     * @var string
     */
    const JOIN_ALL =
        '(SELECT p.name AS permission,
                 u.RecordID AS userid,
                 u.username,
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
            ON tr.RecordID = tm.member)';

    /**
     * @var string
     */
    const RESOURCE_TYPE = 'perm';

    /**
     * @var string
     */
    const PRIMARY_TABLE = 'AccessPermission';

    /**
     * @var string
     */
    const PARENT_TABLE = 'AccessPermissionParent';

    /**
     * @var string
     */
    const ANCESTOR_TABLE = 'AccessPermissionAncestor';

    /**
     * @var string
     */
    const MATCH_NAME = '/^[-._a-zA-Z0-9]+$/';

    /**
     * Constructs an instance of CodeRage\Access\Permission
     *
     * @param array $options The options array; supports the following options:
     *   id - The database ID
     *   name - The machine-readable name of the permission under construction
     *   title - A descriptive label of the group under
     *     construction
     *   description - The description of the permission under construction
     *   domain - The resource type of members of the permission under
     *     construction, as an instance of CodeRage\Access\ResourceType
     *   resource - The resource associated with the permission under construction
     */
    protected function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * Deletes this permission
     */
    public function delete()
    {
        $db = new Db;
        $db->beginTransaction();
        try {

            // Delete access grants
            $sql =
                'DELETE FROM AccessGrant
                 WHERE permission = %i';
            $db->query($sql, $this->id, $this->id);

            // Remove from hierarchy
            foreach ($this->parents() as $parent)
                $parent->removeChild($this);
            foreach ($this->children() as $child)
                $this->removeChild($child);
            $db->delete('AccessPermissionAncestor', ['ancestor' => $this->id]);

            // Delete permission and resource
            $db->delete('AccessPermission', $this->id);
            $this->resource->delete();
        } catch (Throwable $e) {
            throw new
                Error([
                    'details' => "Failed deleting $this",
                    'inner' => $e
                ]);
        }
        $db->commit();
    }

    /**
     * Grants a permission to a group of users
     *
     * @param array $options The options array; supports the following options:
     *     permission - The permission, sepcified by ID, by name, or as an
     *       instance of CodeRage\Access\Permission
     *     grantee - The group of users to whom permission is to be granted,
     *       specified by ID, by name, or as an
     *       instance of CodeRage\Access\Group
     *     target - The group of resources, if any, to which access is to be
     *       granted, specified by ID, by name, or as an instance of
     *       CodeRage\Access\Group; may be null if and only if the permission's
     *       domain is "none", meaning that the permission does not relate to a
     *       group of resources
     */
    public static function grant(array $options)
    {
        self::processGrantOptions($options);
        [$permission, $grantee, $target] =
            Array_::values($options, ['permission', 'grantee', 'target']);
        $db = new Db;
        $sql =
            'SELECT COUNT(*) {i}
             FROM AccessGrant
             WHERE permission = %i AND
                   grantee = %i AND
                   target = %i';
        try {
            if ( $db->fetchValue(
                     $sql,
                     $permission->id(),
                     $grantee->id(),
                     $target->id() ) == 0 )
            {
                $db->insert(
                    'AccessGrant',
                    [
                        'permission' => $permission->id(),
                        'grantee' => $grantee->id(),
                        'target' => $target->id()
                    ]
                );
            }
        } catch (Throwable $e) {
            if ( $db->fetchValue(
                     $sql,
                     $permission->id(),
                     $grantee->id(),
                     $target->id() ) == 0 )
            {
                throw new
                    Error([
                        'details' =>
                            "Failed granting $permission to $grantee with " .
                            "target $target",
                        'inner' => $e
                    ]);
            }
        }
    }

    /**
     * Revokes the specified permission from the given group of users
     *
     * @param array $options The options array; supports the following options:
     *     permission - The permission, sepcified by ID, by name, or as an
     *       instance of CodeRage\Access\Permission
     *     grantee - The group of from to whom permission is to be revoked,
     *       specified by ID, by name, or as an
     *       instance of CodeRage\Access\Group
     *     target - The group of resources, if any, to which access has been
     *       granted, specified by ID, by name, or as an instance of
     *       CodeRage\Access\Group; may be null if and only if the permission's
     *       domain is "none", meaning that the permission does not relate to a
     *       group of resources
     */
    public static function revoke(array $options)
    {
        self::processGrantOptions($options);
        [$permission, $grantee, $target] =
            Array_::values($options, ['permission', 'grantee', 'target']);
        $db = new Db;
        $sql =
            'SELECT COUNT(*) {i}
             FROM AccessGrant
             WHERE permission = %i AND
                   grantee = %i AND
                   target = %i';
        try {
            if ( $db->fetchValue(
                     $sql,
                     $permission->id(),
                     $grantee->id(),
                     $target->id() ) != 0 )
            {
                $db->delete(
                    'AccessGrant',
                    [
                        'permission' => $permission->id(),
                        'grantee' => $grantee->id(),
                        'target' => $target->id()
                    ]
                );
            }
        } catch (Throwable $e) {
            if ( $db->fetchValue(
                     $sql,
                     $permission->id(),
                     $grantee->id(),
                     $target->id() ) != 0 )
            {
                throw new
                    Error([
                        'details' =>
                            "Failed revoking $permission from $grantee with " .
                            "target $target",
                        'inner' => $e
                    ]);
            }
        }
    }

    /**
     * Returns true if the specified permission has been granted to the given
     * user
     *
     * @param array $options The options array; supports the following options:
     *     permission - The permission, sepcified by ID, by name, or as an
     *       instance of CodeRage\Access\Permission
     *     user - The user, specified by ID, by name, or as an instance of
     *       CodeRage\Access\User
     *     resource - The target resource, if any, specified by ID or as an
     *       instance of CodeRage\Access\Managed or CodeRage\Access\Resource_
     *     throwOnError - true to throw an exception instead of returning false;
     *       defaults to false
     * @return boolean
     */
    public static function test(array $options)
    {
        // Process options
        Args::checkKey($options, 'permission', 'int|string|CodeRage\Access\Permission', [
            'required' => true
        ]);
        $options['permission'] = self::loadPermission($options['permission']);
        Args::checkKey($options, 'user', 'int|string|CodeRage\Access\User', [
            'required' => true
        ]);
        $options['user'] = self::loadUser($options['user']);
        Args::checkKey($options, 'resource', 'int|CodeRage\Access\Managed|CodeRage\Access\Resource_', [
            'default' => null
        ]);
        if (isset($options['resource'])) {
            if (is_int($options['resource'])) {
                $options['resource'] =
                    Resource_::load(['id' => $options['resource']]);
            } elseif ($options['resource'] instanceof Managed) {
                $options['resource'] = $options['resource']->resource();
            }
        }
        Args::checkKey($options, 'throwOnError', 'boolean', [
            'default' => false
        ]);

        // Handle root user
        if ($options['user']->id() == User::ROOT)
            return true;

        // Check access
        [$permission, $user, $resource] =
            Array_::values($options, ['permission', 'user', 'resource']);
        $domain = $permission->domain()->name();
        $result = null;
        if ($domain !== 'none') {
            if ($resource === null)
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => 'Missing resource'
                    ]);
            $sql =
                'SELECT COUNT(*) {i}
                 FROM AccessGrant g
                 JOIN AccessPermissionAncestor pa
                   ON pa.ancestor = g.permission AND
                      pa.descendant = %i
                 JOIN AccessGroupAncestor ga
                   ON ga.ancestor = g.grantee
                 JOIN AccessGroupMember gm
                   ON gm.member = %i AND
                      gm.groupid = ga.descendant
                 JOIN AccessGroupAncestor sa
                   ON sa.ancestor = g.target
                 JOIN AccessGroupMember sm
                   ON sm.member = %i AND
                      sm.groupid = sa.descendant';
            $result =
                (new Db)->fetchValue(
                    $sql,
                    $permission->id(),
                    $user->resource()->id(),
                    $resource->resource()->id()
                ) > 0;
            if (!$result && $options['throwOnError']) {
                $view =
                    $permission->name() == 'view' ||
                    self::test([
                        'permission' => 'view',
                        'user' => $user,
                        'resource' => $resource
                    ]);
                if ($view) {
                    throw new
                        Error([
                            'status' => 'ACCESS_DENIED',
                            'message' =>
                                "$user does not have $permission access to " .
                                $resource
                        ]);
                } else {
                    $title = $resource->type()->title();
                    throw new
                        Error([
                            'status' => 'OBJECT_DOES_NOT_EXIST',
                            'message' => "No such $title: $resource",
                            'details' =>
                                "$user does not have $permission access to " .
                                $resource
                        ]);
                }
            }
        } else {
            if ($resource !== null)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "$permission does not support target resources"
                    ]);

            $sql =
                'SELECT COUNT(*) {i}
                 FROM AccessGrant g
                 JOIN AccessPermissionAncestor pa
                   ON pa.ancestor = g.permission AND
                      pa.descendant = %i
                 JOIN AccessGroupAncestor ga
                   ON ga.ancestor = g.grantee
                 JOIN AccessGroupMember gm
                   ON gm.member = %i AND
                      gm.groupid = ga.descendant
                 WHERE g.target = %i';
            $result =
                (new Db)->fetchValue(
                    $sql,
                    $permission->id(),
                    $user->resource()->id(),
                    self::UNIVERSAL_ID
                ) > 0;
            if (!$result && $options['throwOnError'])
                throw new
                    Error([
                        'status' => 'ACCESS_DENIED',
                        'message' => "$user does not have $permission"
                    ]);

        }
        return $result;
    }

    /**
     * Returns true if the specified permission has been granted to the given
     * user
     *
     * @param array $options The options array; supports the following options:
     *     permission - The permission, sepcified by ID, by name, or as an
     *       instance of CodeRage\Access\Permission
     *     user - A SQL expression that refers to the ID of a user whose access
     *       to a resource is to be tested
     *     resource - A SQL expression that refers to the ID of a resource to
     *       which access is to be tested
     *     throwOnError - true to throw an exception instead of returning false;
     *       defaults to false
     * @return boolean
     */
    public static function join(array $options)
    {
        [$permission, $user, $resource] =
            Array_::values($options, ['permission', 'user', 'resource']);
        $permission =
            Args::checkKey($options, 'permission', 'int|string|CodeRage\Access\Permission', [
                'required' => true
            ]);
        if (is_int($permission)) {
            $permission = self::load(['id' => $permission]);
        } elseif (is_string($permission)) {
            $permission = self::load(['name' => $permission]);
        }
        $user =
            Args::checkKey($options, 'user', 'int|string', [
                'label' => 'user column alias',
                'required' => true
            ]);
        $resource =
            Args::checkKey($options, 'resource', 'int|string', [
                'label' => 'resource column alias',
                'required' => true
            ]);
        return
            "JOIN AccessUser u_
               ON u_.RecordID = $user
             JOIN AccessGroupMember ug_
               ON ug_.member = u_.resource
             JOIN AccessGroupAncestor ua_
               ON ua_.descendant = ug_.groupid
             JOIN AccessGrant g_
               ON g_.grantee = ua_.ancestor
             JOIN AccessPermissionAncestor pa_
               ON pa_.ancestor = g_.permission
             JOIN AccessPermission p_
               ON p_.RecordID = pa_.descendant AND
                  p_.name = '{$permission->name()}'
             JOIN AccessGroupAncestor ta_
               ON ta_.ancestor = g_.target
             JOIN AccessGroupMember tg_
               ON tg_.groupid = ta_.descendant AND
                  tg_.member = $resource
             JOIN AccessResource tr_
               ON tr_.RecordID = tg_.member AND
                  tr_.disabled IS NULL AND
                  tr_.retired IS NULL";
    }

    /**
     * Process and validates options for grant() and revoke()
     *
     * @param array $options The options array; supports the following options:
     *     permission - The permission, sepcified by ID, by name, or as an
     *       instance of CodeRage\Access\Permission
     *     grantee - The group of users to whom permission is to be granted,
     *       specified by ID, by name, or as an
     *       instance of CodeRage\Access\Group
     *     target - The group of resources, if any, to which access is to be
     *       granted, specified by ID, by name, or as an instance of
     *       CodeRage\Access\Group; may be null if and only if the permission's
     *       domain is "none", meaning that the permission does not relate to a
     *       group of resources
     */
    private static function processGrantOptions(array &$options)
    {
        Args::checkKey($options, 'permission', 'int|string|CodeRage\Access\Permission', [
            'required' => true
        ]);
        $options['permission'] = self::loadPermission($options['permission']);
        Args::checkKey($options, 'grantee', 'int|string|CodeRage\Access\Group', [
            'required' => true
        ]);
        $options['grantee'] = self::loadGroup($options['grantee']);
        Args::checkKey($options, 'target', 'int|string|CodeRage\Access\Group');
        if (isset($options['target']))
            $options['target'] = self::loadGroup($options['target']);
        $permission = $options['permission'];
        $domain = $permission->domain()->name();
        if ($domain !== 'none') {
            if (!isset($options['target']))
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => "Missing target group for $permission"
                    ]);
            $target = $options['target'];
            if ($domain != 'any' && $target->domain()->name() != $domain)
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' =>
                            "Invalid target group for $permission: expected " .
                            "group of resources of type " .
                            $permission->domain()->title() . "; found group " .
                            "of resources of type " .
                            $target->domain()->title()
                    ]);
        } elseif (isset($options['target'])) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "$permission does not support target groups"
                ]);
        } else {
            $options['target'] = Group::universal();
        }
    }

    /**
     * Helper for processGrantOptions()
     *
     * @param mixed $group
     * @return CodeRage\Access\Group
     */
    private static function loadGroup($group)
    {
        return is_int($group) ?
            Group::load(['id' => $group]) :
            ( is_string($group) ?
                  Group::load(['name' => $group]) :
                  $group );
    }

    /**
     * Helper for processGrantOptions()
     *
     * @param mixed $permission
     * @return CodeRage\Access\Permission
     */
    private static function loadPermission($permission)
    {
        return is_int($permission) ?
            Permission::load(['id' => $permission]) :
            ( is_string($permission) ?
                  Permission::load(['name' => $permission]) :
                  $permission );
    }

    /**
     * Helper for test()
     *
     * @param mixed $user
     * @return CodeRage\Access\User
     */
    private static function loadUser($user)
    {
        return is_int($user) ?
            User::load(['id' => $user]) :
            ( is_string($user) ?
                  User::load(['username' => $user]) :
                  $user );
    }

    /**
     * The resource type of members of this CodeRage\Access\Group
     *
     * @var CodeRage\Access\ResourceType
     */
    private $domain;
}
