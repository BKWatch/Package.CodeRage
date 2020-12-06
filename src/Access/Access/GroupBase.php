<?php

/**
 * Defines the trait CodeRage\Access\GroupBase
 *
 * File:        CodeRage/Access/GroupBase.php
 * Date:        Tue Dec 25 19:43:52 UTC 2018
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
use CodeRage\Util\Random;
use CodeRage\Util\Time;


/**
 * Base class for CodeRage\Access\Group and CodeRage\Access\Permission
 */
class GroupBase implements Managed {

    /**
     * The name of the universal group and permission
     *
     * @var string
     */
    const UNIVERSAL = 'any';

    /**
     * The name of the universal group and permission
     *
     * @var string
     */
    const UNIVERSAL_ID = 1;

    /**
     * @var array
     */
    const OPTIONS =
        [
            'name' => 1,
            'title' => 1,
            'description' => 1,
            'domain' => 1,
            'owner' => 1
        ];

    /**
     * @var int
     */
    const NAME_MAX_LENGTH = 255;

    /**
     * @var int
     */
    const RANDOM_NAME_LENGTH = 30;

    /**
     * @var bool
     */
    const DISABLE_TRANSACTIONS = true;

    /**
     * Constructs an instance of CodeRage\Access\GroupBase
     *
     * @param array $options The options array; supports the following options:
     *   id - The database ID
     *   name - The machine-readable name of the group or permission under
     *     construction
     *   title - A descriptive label of the group or permission under
     *     construction
     *   description - The description of the group or permission under
     *     construction
     *   domain - The resource type of members of the group under construction,
     *     as an instance of CodeRage\Access\ResourceType
     *   resource - The resource associated with the group or permission under
     *     construction
     */
    protected function __construct(array $options)
    {
        $this->id = $options['id'];
        $this->name = $options['name'];
        $this->title = $options['title'];
        $this->description = $options['description'];
        $this->domain = $options['domain'];
        $this->resource = $options['resource'];
    }

    /**
     * Returns the database ID
     *
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Returns the machine-readable name of this group or permission
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns a descriptive label of this group or permission
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * Sets the title of this group or permission
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        Args::check($title, 'string', 'title');
        (new Db)->update(
            static::PRIMARY_TABLE,
            ['title' => $title],
            $this->id
        );
        $this->title = $title;
    }

    /**
     * Returns the description of this group or permission
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Sets the description of this group or permission
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        Args::check($description, 'string', 'title');
        (new Db)->update(
            static::PRIMARY_TABLE,
            ['description' => $description],
            $this->id
        );
        $this->description = $description;
    }

    /**
     * Returns the resource type of members of this group or permission
     *
     * @return CodeRage\Access\ResourceType
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * Returns the resource associated with this group or permission
     *
     * @return CodeRage\Access\Resource_
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * Returns this instance's list of children
     *
     * @return array
     */
    public function children()
    {
        $children = [];
        try {
            $table = static::PARENT_TABLE;
            $sql =
                "SELECT child {i}
                 FROM [$table]
                 WHERE parent = %i";
            $result = (new Db)->query($sql, $this->id());
            while ($row = $result->fetchRow())
                $children[] = self::load(['id' => $row[0]]);
        } catch (Throwable $e) {
            throw new
                Error([
                    'message' => "Failed loading children of $this",
                    'inner' => $e
                ]);
        }
        return $children;
    }

    /**
     * Returns this instance's list of parents
     *
     * @return array
     */
    public function parents()
    {
        $parents = [];
        try {
            $table = static::PARENT_TABLE;
            $sql =
                "SELECT parent {i}
                 FROM [$table]
                 WHERE child = %i";
            $result = (new Db)->query($sql, $this->id());
            while ($row = $result->fetchRow())
                $parents[] = self::load(['id' => $row[0]]);
        } catch (Throwable $e) {
            throw new
                Error([
                    'message' => "Failed loading parents of $this",
                    'inner' => $e
                ]);
        }
        return $parents;
    }

    /**
     * Returns true if this instance is a parent of the specified instance
     *
     * @param CodeRage\Access\GroupBase $other
     * @return boolean
     * @todo move into CodeRage/Access/Test/Suite.php
     */
    public function parentOf(GroupBase $other)
    {
        try {
            $table = static::PARENT_TABLE;
            $sql =
                "SELECT COUNT(*)
                 FROM [$table]
                 WHERE parent = %i AND child = %i";
            return (new Db)->fetchValue($sql, $this->id(), $other->id()) > 0;
        } catch (Throwable $e) {
            throw new
                Error([
                    'message' => "Failed testing paternity of [$this, $other]",
                    'inner' => $e
                ]);
        }
    }

    /**
     * Returns true if this instance is an ancestor of the specified instance
     *
     * @param CodeRage\Access\GroupBase $other
     * @return boolean
     * @todo move into CodeRage/Access/Test/Suite.php
     */
    public function ancestorOf(GroupBase $other)
    {
        try {
            $table = static::ANCESTOR_TABLE;
            $sql =
                "SELECT COUNT(*)
                 FROM [$table]
                 WHERE ancestor = %i AND descendant = %i";
            return (new Db)->fetchValue($sql, $this->id(), $other->id()) > 0;
        } catch (Throwable $e) {
            throw new
                Error([
                    'message' => "Failed testing ancestry of [$this, $other]",
                    'inner' => $e
                ]);
        }
    }

    /**
     * Adds a child to this instance
     *
     * @param CodeRage\Access\Managed $child The child
     */
    public function addChild(Managed $child)
    {
        $parentTable = static::PARENT_TABLE;
        $ancestorTable = static::ANCESTOR_TABLE;
        $db = new Db;
        if (!self::DISABLE_TRANSACTIONS)
            $db->beginTransaction();
        try {

            // Check whether $child is already a child
            $sql =
                "SELECT COUNT(*)
                 FROM [$parentTable]
                 WHERE parent = %i AND
                       child = %i";
            if ($db->fetchValue($sql, $this->id(), $child->id()) > 0)
                throw new
                    Error([
                        'status' => 'STATE_ERROR',
                        'message' => "$child is already a child of $this"
                    ]);

            // Check whether $child is an ancestor
            $sql =
                "SELECT COUNT(*)
                 FROM [$ancestorTable]
                 WHERE ancestor = %i AND
                       descendant = %i";
            if ($db->fetchValue($sql, $child->id(), $this->id()) > 0)
                throw new
                    Error([
                        'status' => 'STATE_ERROR',
                        'message' => "$child is an ancestor of $this"
                    ]);

            // Add a parent record
            $db->insert(
                $parentTable,
                [
                    'parent' => $this->id(),
                    'child' => $child->id()
                ]
            );

            // Add ancestor records
            $sql =
                "INSERT INTO [$ancestorTable]
                 (CreationDate, ancestor, descendant)
                 SELECT %i, parent.ancestor, child.descendant
                 FROM [$ancestorTable] parent
                 JOIN [$ancestorTable] child
                 WHERE parent.descendant = %i AND
                       child.ancestor = %i AND
                       NOT EXISTS (
                         SELECT *
                         FROM [$ancestorTable] a
                         WHERE a.ancestor = parent.ancestor AND
                               a.descendant = child.descendant
                       )";
            $now = Time::get();
            $db->query($sql, $now, $this->id(), $child->id());

        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();

            // Check whether $child was added concurrently after the initial
            // check, causing a constraint violation
            $sql =
                "SELECT COUNT(*)
                 FROM [$parentTable]
                 WHERE parent = %i AND
                       child = %i";
            if ($db->fetchValue($sql, $this->id(), $child->id()) > 0)
                return;
            throw new
                Error([
                    'message' => "Failed adding $child as child of $this",
                    'inner' => $e
                ]);
        }
        if (!self::DISABLE_TRANSACTIONS)
            $db->commit();
    }

    /**
     * Removes a child from this instance
     *
     * @param CodeRage\Access\Managed $child The child
     */
    public function removeChild(Managed $child)
    {
        if ($this->id() == self::UNIVERSAL_ID) {
            $type = static::RESOURCE_TYPE;
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'message' => "Can't remove children from universal $type"
                ]);
        }

        // Verify that $child is a child
        $table = static::PARENT_TABLE;
        $db = new Db;
        $sql =
            "SELECT RecordID {i}
             FROM [$table]
             WHERE parent = %i AND
                   child = %i";
        $parentId = $db->fetchValue($sql, $this->id(), $child->id());
        if ($parentId == null)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'message' =>
                        "Failed removing child: $child is not a child of $this"
                ]);

        if (!self::DISABLE_TRANSACTIONS)
            $db->beginTransaction();

        // Remove parent record
        try {
            $db->delete($table, $parentId);
        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();

            // Check whether $child was removed concurrently after the initial
            // check
            $sql =
                "SELECT COUNT(*)
                 FROM [$table]
                 WHERE parent = %i AND
                       child = %i";
            if ($db->fetchValue($sql, $this->id(), $child->id()) == 0)
                return;
            throw new
                Error([
                    'message' => "Failed removing $child from $this",
                    'inner' => $e
                ]);
        }

        // Update ANCESTOR_TABLE
        try {
            $this->updateAncestry();
            $child->updateAncestry();
        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();
            throw new
                Error([
                    'message' => "Failed removing child from group $this",
                    'inner' => $e
                ]);
        }

        if (!self::DISABLE_TRANSACTIONS)
            $db->commit();
    }

    /**
     * Returns a newly constructed group or permission
     *
     * @param array $options The options array; supports the following options:
     *   name - The machine-readable name of the group
     *   title - A descriptive label of the group (optional)
     *   description - A description of the group (optional)
     *   domain - The resource type of members of the group, as a string or an
     *     instance of CodeRage\Access\ResourceType; defaults to "any"
     *   owner - The user that will own the resource, specified by ID, by
     *     username, or as an instance of CodeRage\Access\User; defaults to
     *     the root user
     * @return CodeRage\Access\GroupBase
     */
    public static function create(array $options)
    {
        $type = static::RESOURCE_TYPE;
        $primaryTable = static::PRIMARY_TABLE;
        $ancestorTable = static::ANCESTOR_TABLE;
        foreach (array_keys($options) as $o)
            if (!array_key_exists($o, self::OPTIONS))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Unsupported option: $o"
                    ]);
        $name =
            Args::checkKey($options, 'name', 'string', [
                'required' => true
            ]);
        if (!preg_match(static::MATCH_NAME, $name))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid $type name: $name"
                ]);
        if (strlen($name) > self::NAME_MAX_LENGTH)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid $type name '$name': $type names may not " .
                        "exceed " . self::NAME_MAX_LENGTH . " characters"
                ]);
        Args::checkKey($options, 'title', 'string', [
            'default' => null
        ]);
        Args::checkKey($options, 'description', 'string', [
            'default' => null
        ]);
        Args::checkKey($options, 'domain', 'string|CodeRage\\Access\\ResourceType', [
            'default' => 'any'
        ]);
        if (is_string($options['domain']))
            $options['domain'] =
                ResourceType::load(['name' => $options['domain']]);
        Args::checkKey($options, 'owner', 'int', [
            'default' => User::ROOT
        ]);
        $db = new Db;
        if (!self::DISABLE_TRANSACTIONS)
            $db->beginTransaction();
        try {
            $options['resource'] = Resource_::create($type, $options['owner']);
            $id = $options['id'] =
                $db->insert(
                    $primaryTable,
                    [
                        'name' => $name,
                        'title' => $options['title'],
                        'description' => $options['description'],
                        'domain' => $options['domain']->id(),
                        'resource' => $options['resource']->id(),
                    ]
                );
            $db->insert(
                $ancestorTable,
                [
                    'ancestor' => $id,
                    'descendant' => $id
                ]
            );
        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();
            $title = self::lowercaseTitle();
            $sql =
                "SELECT COUNT(*)
                 FROM [$primaryTable]
                 WHERE name = %s";
            if ($db->fetchValue($sql, $name)) {
                throw new
                    Error([
                        'status' => 'OBJECT_EXISTS',
                        'message' => "A $title with name '$name' already exists",
                        'inner' => $e
                    ]);
            } else {
                throw new
                    Error([
                        'details' => "Failed creating $title '$name'",
                        'inner' => $e
                    ]);
            }
        }
        $class = static::class;
        $result = new $class($options);
        if ($name != self::UNIVERSAL) {
            $any = self::load(['name' => self::UNIVERSAL]);
            $any->addChild($result);
        }
        if (!self::DISABLE_TRANSACTIONS)
            $db->commit();
        return $result;
    }

    /**
     * Returns the group or permission with the given ID
     *
     * @param array $options The options array; supports the following options:
     *     id - The database ID (optional)
     *     name - The name (optional)
     *   Exactly one of "id" or "name" must be supplied
     * @return CodeRage\Access\GroupBase
     * @throws CodeRage\Error if no such group or permission exists
     */
    public static function load(array $options)
    {
        $type = ResourceType::load(['name' => static::RESOURCE_TYPE]);
        $table = static::PRIMARY_TABLE;
        Args::checkKey($options, 'id', 'int', [
            'label' => 'ID'
        ]);
        Args::checkKey($options, 'name', 'string');
        if ( isset($options['name']) &&
             !preg_match(static::MATCH_NAME, $options['name']) )
        {
            $title = self::lowercaseTitle();
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid $title name: {$options['name']}"
                ]);
        }
        $opt = Args::uniqueKey($options, ['id', 'name']);
        $where = isset($options['id']) ?
            'RecordID = %i' :
            'name = %s';
        $row =
            (new Db)->fetchFirstArray(
                "SELECT RecordID as id {i}, name {s}, title {s},
                        description {s}, domain {i}, resource {i}
                 FROM [$table]
                 WHERE $where",
                $options[$opt]
            );
        if ($row === null) {
            $title = self::lowercaseTitle();
            $value = $options[$opt];
            $desc = $opt == 'name' ?
                static::RESOURCE_TYPE . "[$value]":
                ResourceId::encode($value, static::RESOURCE_TYPE);
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'message' => "No such $title: $desc"
                ]);
        }
        $row['domain'] = ResourceType::load(['id' => $row['domain']]);
        $row['resource'] = Resource_::load(['id' => $row['resource']]);
        $class = static::class;
        return new $class($row);
    }

    /**
     * Returns the universal object
     *
     * @return CodeRage\Acces\GroupBase
     */
    public static function universal()
    {
        static $base;
        if ($base === null)
            $base = self::load(['name' => self::UNIVERSAL]);
        return $base;
    }

    public function nativeDataEncoder(\CodeRage\Util\NativeDataEncoder $encoder)
    {
        return (object) ['id' => $this->id];
    }

    public function __toString()
    {
        $type = static::RESOURCE_TYPE;
        return "{$type}[$this->name]";
    }

    /**
     * Returns the title of the assocaited resource type, in lower case
     *
     * @return string
     */
    private static function lowercaseTitle()
    {
        $type = ResourceType::load(['name' => static::RESOURCE_TYPE]);
        return strtolower($type->title());
    }

    /**
     * Ensures that the table ANCESTOR_TABLE accurately reflects the
     * paternity relations defined by the table PARENT_TABLE for this instance
     * and its relatives
     */
    private function updateAncestry()
    {
        $primaryTable = static::PRIMARY_TABLE;
        $parentTable = static::PARENT_TABLE;
        $ancestorTable = static::ANCESTOR_TABLE;
        $db = new Db;
        if (!self::DISABLE_TRANSACTIONS)
            $db->beginTransaction();
        try {
            $relatives = $this->relatives();

            // Delete ancestor relationships
            $sql =
                "DELETE FROM [$ancestorTable]
                 WHERE [$ancestorTable].ancestor != %i AND
                       [$ancestorTable].ancestor !=
                           [$ancestorTable].descendant AND
                       EXISTS (
                         SELECT *
                         FROM [$primaryTable] p
                         JOIN AccessGroupMember m
                           ON m.groupid = %i AND
                              m.member = p.resource
                         WHERE [$ancestorTable].ancestor = p.RecordID OR
                               [$ancestorTable].descendant = p.RecordID
                       )";
            $db->query($sql, self::UNIVERSAL_ID, $relatives->id());

            // Fetch parent-child relationships
            $all = [];       // Maps IDs to members of $relatives
            $children = [];  // Maps IDs to associative arrays of children
            $sql =
                "SELECT p.parent {i}, p.child {i}
                 FROM [$parentTable] p
                 JOIN [$primaryTable] parent
                   ON parent.RecordID = p.parent AND
                      parent.RecordID != %i
                 JOIN [$primaryTable] child
                   ON child.RecordID = p.child
                 JOIN AccessGroupMember pm
                   ON pm.groupid = %i AND
                      pm.member = parent.resource
                 JOIN AccessGroupMember cm
                   ON cm.groupid = %i AND
                      cm.member = child.resource";
            $result =
                $db->query(
                    $sql,
                    self::UNIVERSAL_ID,
                    $relatives->id(),
                    $relatives->id()
                );
            while ($row = $result->fetchRow()) {
                list($parentId, $childId) = $row;
                $parent = $child = null;
                if (isset($all[$parentId])) {
                    $parent = $all[$parentId];
                } else {
                    $parent = $all[$parentId] = self::load(['id' => $parentId]);
                }
                if (isset($all[$childId])) {
                    $child = $all[$childId];
                } else {
                    $child = $all[$childId] = self::load(['id' => $childId]);
                }
                $children[$parent->id()][$child->id()] = $child;
            }

            // Delete $relatives
            $db->delete('AccessGroupMember', ['groupid' => $relatives->id()]);
            $db->delete('AccessGroupAncestor', ['descendant' => $relatives->id()]);
            $db->delete('AccessGroupParent', ['child' => $relatives->id()]);
            $db->delete('AccessGroup', $relatives->id());
            $relatives->resource()->delete();

            // Compute ancestor relationships
            $descendants = [];           // Maps element IDs to associative
            foreach ($all as $id => $g)  // arrays containing their descendants
                $descendants[$id][$id] = $g;
            while (true) {
                $done = true;
                foreach ($children as $parent => $kids) {
                    foreach ($kids as $kidId => $kid) {
                        foreach ($descendants[$kidId] as $descId => $desc) {
                            if (!isset($descendants[$parent][$descId])) {
                                $descendants[$parent][$descId] = $desc;
                                $done = false;
                            }
                        }
                    }
                }
                if ($done)
                    break;
            }

            // Populate ANCESTOR_TABLE
            foreach ($descendants as $ancestorId => $map) {
                foreach ($map as $descId => $desc) {
                    if ($descId != $ancestorId) {
                        $db->insert(
                            static::ANCESTOR_TABLE,
                            [
                                'ancestor' => $ancestorId,
                                'descendant' => $descId
                            ]
                        );
                    }
                }
            }
        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();
            throw new
                Error([
                    'details' => "Failed updating ancestry of $this",
                    'inner' => $e
                ]);

        }
        if (!self::DISABLE_TRANSACTIONS)
            $db->commit();
    }

    /**
     * Returns a temporary group containing all instances connected to this
     * instances by a chain of zero or more parent or child relationsihps; the
     * caller is responsible for deleting the group
     *
     * @return array
     */
    private function relatives()
    {
        $primaryTable = static::PRIMARY_TABLE;
        $parentTable = static::PARENT_TABLE;
        $ancestorTable = static::ANCESTOR_TABLE;
        $descendantsQuery =
            "SELECT child.RecordID {i}
             FROM [$primaryTable] child
             JOIN [$parentTable] p
               ON p.child = child.RecordID
             JOIN [$primaryTable] parent
               ON parent.RecordID = p.parent
             JOIN AccessResource r
               ON r.RecordID = parent.resource
             JOIN AccessGroupMember m
               ON m.groupid = %i AND
                  m.member = r.RecordID
             LEFT JOIN AccessGroupMember m2
               ON m2.groupid = %i AND
                  m2.member = child.resource
             WHERE m2.RecordID IS NULL";
        $ancestorsQuery =
            "SELECT parent.RecordID {i}
             FROM [$primaryTable] parent
             JOIN AccessGroupParent p
               ON p.parent = parent.RecordID AND
                  parent.RecordID != %i
             JOIN [$primaryTable] child
               ON child.RecordID = p.child
             JOIN AccessResource r
               ON r.RecordID = child.resource
             JOIN AccessGroupMember m
               ON m.groupid = %i AND
                  m.member = r.RecordID
             LEFT JOIN AccessGroupMember m2
               ON m2.groupid = %i AND
                  m2.member = parent.resource
             WHERE m2.RecordID IS NULL";
        $db = new Db;
        if (!self::DISABLE_TRANSACTIONS)
            $db->beginTransaction();
        $relatives = null;
        try {
            $length = self::RANDOM_NAME_LENGTH;
            $relatives =
                Group::create([
                    'name' => Random::string($length),
                    'domain' => 'any',
                    'owner' => User::ROOT
                ]);
            $id = $relatives->id();
            $relatives->add($this);
            while (true) {
                $new = [];
                $result = $db->query($descendantsQuery, $id, $id);
                while ($row = $result->fetchRow()) {
                    $new[$row[0]] = self::load(['id' => $row[0]]);
                }
                $result =
                    $db->query(
                        $ancestorsQuery,
                        self::UNIVERSAL_ID,
                        $id,
                        $id
                    );
                while ($row = $result->fetchRow()) {
                    $new[$row[0]] = self::load(['id' => $row[0]]);
                }
                if (empty($new))
                    break;
                foreach ($new as $g)
                    $relatives->add($g->resource);
            }
        } catch (Throwable $e) {
            if (!self::DISABLE_TRANSACTIONS)
                $db->rollback();
            throw new
                Error([
                    'details' =>
                        'Failed populating temporary group of relatives of ' .
                        $this,
                    'inner' => $e
                ]);
        }
        if (!self::DISABLE_TRANSACTIONS)
            $db->commit();
        return $relatives;
    }

    /**
     * The database ID
     *
     * @var int
     */
    private $id;

    /**
     * The machine-readable name of this group or permission
     *
     * @var string
     */
    private $name;

    /**
     * A descriptive label of this group or permission
     *
     * @var string
     */
    private $title;

    /**
     * The description of this group or permission
     *
     * @var string
     */
    private $description;

    /**
     * The resource type of members of this CodeRage\Access\Group
     *
     * @var CodeRage\Access\ResourceType
     */
    private $domain;

    /**
     * The resource associated with this group or permission
     *
     * @var CodeRage\Access\Resource_
     */
    private $resource;
}
