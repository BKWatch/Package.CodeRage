<?php

/**
 * Defines the class CodeRage\Access\Group
 *
 * File:        CodeRage/Access/Group.php
 * Date:        Sun Jun 10 12:45:50 EDT 2012
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use Throwable;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Represents a group of resources created by binding the parameters of a group
 * definition to resources of appropriate types.
 */
final class Group extends GroupBase {

    /**
     * Derived table definition defining pairs [G, R] where resource R is a
     * member of a descendant of group G, and where R is neither disabled nor
     * retired, exposing the following columns names:
     *
     *   id - The ID of the ancestor group
     *   name - The name of the ancestor group
     *   resource - The ID of a resource contained in a descendant group
     *
     * @var string
     */
    const JOIN =
        '(SELECT g.RecordID AS id,
                 g.name AS name,
                 m.member AS resource
          FROM AccessGroup g
          JOIN AccessGroupAncestor a
            ON a.ancestor = g.RecordID
          JOIN AccessGroupMember m
            ON m.groupid = a.descendant
          JOIN AccessResource r
            ON r.RecordID = m.member AND
               r.disabled IS NULL AND
               r.retired IS NULL)';

    /**
     * Derived table definition defining pairs [G, R] where resource R is a
     * member of a descendant of group G, and where R is neither disabled nor
     * retired, exposing the following columns names:
     *
     *   id - The ID of the ancestor group
     *   name - The name of the ancestor group
     *   resource - The ID of a resource contained in a descendant group
     *   disabled - The time the resource was disabled, as a UNIX timestamp
     *   retried - The time the resource was retired (soft-deleted), as a UNIX
     *     timestamp
     *
     * @var string
     */
    const JOIN_ALL =
        '(SELECT g.RecordID AS id,
                 g.name AS name,
                 m.member AS resource
          FROM AccessGroup g
          JOIN AccessGroupAncestor a
            ON a.ancestor = g.RecordID
          JOIN AccessGroupMember m
            ON m.groupid = a.descendant
          JOIN AccessResource r
            ON r.RecordID = m.member)';

    /**
     * The name of the universal group
     *
     * @var string
     */
    const UNIVERSAL = 'any';

    /**
     * @var string
     */
    const RESOURCE_TYPE = 'group';

    /**
     * @var string
     */
    const PRIMARY_TABLE = 'AccessGroup';

    /**
     * @var string
     */
    const PARENT_TABLE = 'AccessGroupParent';

    /**
     * @var string
     */
    const ANCESTOR_TABLE = 'AccessGroupAncestor';

    /**
     * @var string
     */
    const MATCH_NAME =
        '/^([-._a-zA-Z0-9]+:[-._a-zA-Z0-9]+|
            [-._a-zA-Z0-9]+(\(([-._a-zA-Z0-9]+:)?[-._a-zA-Z0-9]+\))?)$/x';

    /**
     * @var int
     */
    const NAME_MAX_LENGTH = 255;

    /**
     * Constructs an instance of CodeRage\Access\Group
     *
     * @param array $options The options array; supports the following options:
     *   id - The database ID
     *   name - The machine-readable name of the group under construction
     *   title - A descriptive label of the group under
     *     construction
     *   description - The description of the group under construction
     *   domain - The resource type of members of the group under construction,
     *     as an instance of CodeRage\Access\ResourceType
     *   resource - The resource associated with the group under construction
     */
    protected function __construct($options)
    {
        parent::__construct($options);
    }

    /**
     * Adds a resource to this group
     *
     * @param mixed $member An instance of CodeRage\Access\Resource_ or
     *   CodeRage\Access\Managed
     */
    public function add($member)
    {
        Args::check($member, 'CodeRage\Access\Resource_|CodeRage\Access\Managed', 'member');
        if (!($member instanceof Resource_))
            $member = $member->resource();
        $id = $this->id();
        $domain = $this->domain()->name();
        $type = $member->type()->name();
        if ($domain != 'any' && $type != $domain)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Failed adding $member to $this: expected $domain; " .
                        "found $type"
                ]);
        $db = new Db;
        $sql =
            'SELECT COUNT(*)
             FROM AccessGroupMember
             WHERE groupid = %i AND
                   member = %i';
        try {
            if ($db->fetchValue($sql, $id, $member->id()) == 0) {
                $db->insert(
                    'AccessGroupMember',
                    [
                        'groupid' => $id,
                        'member' => $member->id()
                    ]
                );
            }
        } catch (Throwable $e) {
            if ($db->fetchValue($sql, $id, $member->id()) == 0) {
                throw new
                    Error([
                        'message' => "Failed adding $member to $this",
                        'inner' => $e
                    ]);
            }
        }
    }

    /**
     * Removes a resource from this group
     *
     * @param mixed $member An instance of CodeRage\Access\Resource_ or
     *   CodeRage\Access\Managed
     */
    public function remove($member)
    {
        Args::check($member, 'CodeRage\Access\Resource_|CodeRage\Access\Managed', 'member');
        if (!($member instanceof Resource_))
            $member = $member->resource();
        try {
            (new Db)->delete(
                'AccessGroupMember',
                ['groupid' => $this->id(), 'member' => $member->id()]
            );
        } catch (Throwable $e) {
            throw new
                Error([
                    'message' => "Failed removing $member from $this",
                    'inner' => $e
                ]);
        }
    }

    /**
     * Returns true if the given resource is contained in this group
     *
     * @param mixed $member An instance of CodeRage\Access\Resource_ or
     *   CodeRage\Access\Managed
     * @return bool
     */
    public function contains($member)
    {
        Args::check($member, 'CodeRage\Access\Resource_|CodeRage\Access\Managed', 'member');
        if (!($member instanceof Resource_))
            $member = $member->resource();
        return
            (new Db)->fetchValue(
                'SELECT COUNT(*)
                 FROM AccessGroupAncestor a
                 JOIN AccessGroupMember m
                   ON m.groupid = a.descendant AND
                      m.member = %i
                 WHERE a.ancestor = %i',
                $member->id(),
                $this->id()
            ) > 0;
    }

    /**
     * Deletes this group
     */
    public function delete()
    {
        $db = new Db;
        if (!self::DISABLE_TRANSACTIONS)
            $db->beginTransaction();
        $id = $this->id();
        try {

            // Delete access grants
            $sql =
                'DELETE FROM AccessGrant
                 WHERE grantee = %i OR target = %i';
            $db->query($sql, $id, $id);

            // Remove from hierarchy
            foreach ($this->parents() as $parent)
                if ($parent->id() != self::UNIVERSAL_ID)
                    $parent->removeChild($this);
            foreach ($this->children() as $child)
                $this->removeChild($child);
            $db->delete(
                'AccessGroupParent',
                [
                    'parent' => self::UNIVERSAL_ID,
                    'child' => $id
                ]
            );
            $db->delete(
                'AccessGroupAncestor',
                [
                    'ancestor' => self::UNIVERSAL_ID,
                    'descendant' => $id
                ]
            );
            $db->delete(
                'AccessGroupAncestor',
                [
                    'ancestor' => $id,
                    'descendant' => $id
                ]
            );
            $db->delete('AccessGroupMember', ['groupid' => $id]);

            // Delete group and resource
            $db->delete('AccessGroup', $id);
            $this->resource()->delete();
        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();
            $sql =
                'SELECT username
                 FROM AccessUser
                 WHERE publicGroup = %i OR
                       privateGroup = %i OR
                       singletonGroup = %i';
            $username = $db->fetchValue($sql, $id, $id, $id);
            if ($username !== null) {
                throw new
                    Error([
                        'status' => 'OBJECT_EXISTS',
                        'message' => "$this is in use by user[$username]"
                    ]);
            } else {
                throw new
                    Error([
                        'details' => "Failed deleting $this",
                        'inner' => $e
                    ]);
            }
        }
        if (!self::DISABLE_TRANSACTIONS)
            $db->commit();
    }
}
