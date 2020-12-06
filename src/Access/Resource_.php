<?php

/**
 * Defines the class CodeRage\Access\Resource_
 *
 * File:        CodeRage/Access/Resource_.php
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
use CodeRage\Access;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Time;


/**
 * Represents an entity to which access can be granted.
 */
final class Resource_ implements Managed {

    /**
     * @var string
     */
    const MATCH_DESCRIPTOR =
        '/^([a-z][_a-z0-9]*)(-([0-9a-f]{8})|\[[^]]+\]|\((0|[1-9][0-9]*)\))$/';

    /**
     * Constructs an instance of CodeRage\Access\Resource_.
     *
     * @param mixed $type The resource type of the CodeRage\Access\Resource_
     *   under construction, specified by name or as an instance of
     *   CodeRage\Access\ResourceType
     * @param int $owner The owner of the CodeRage\Access\Resource_ under
     *   construction, as the ID of a record in the table AccessUser
     * @param int $disabled - The time at which the CodeRage\Access\Resource_
     *   under construction was disabled, if any, as a UNIX timestamp
     * @param int $retired - The time at which the CodeRage\Access\Resource_
     *   under construction was soft-deleted, if any, as a UNIX timestamp
     * @param int $id The ID of the record in the table AccessResource
     *   corresponding to the CodeRage\Access\Resource_ under construction
     * @param int $associatedId The ID of the record in the in the associated
     *   table whose "resource" column points to the CodeRage\Access\Resource_
     *   under construction, if any
     */
    private function __construct($type, $owner, $disabled, $retired, $id,
        $associatedId = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->owner = $owner;
        $this->disabled = $disabled;
        $this->retired = $retired;
        $this->associatedId = $associatedId;
    }

    /**
     * Returns the ID of a record in the table AccessResource corresponding
     * to this CodeRage\Access\Resource_
     *
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Returns the resource type of this CodeRage\Access\Resource_
     *
     * @return CodeRage\Access\ResourceType
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns the owner of this CodeRage\Access\Resource_, as the ID of a
     * record in the table AccessUser
     *
     * @return int
     */
    public function owner()
    {
        return $this->owner;
    }

    /**
     * Sets the owner of this CodeRage\Access\Resource_, as the ID of a record
     * in the table AccessUser
     *
     * @param int $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * Returns the user ID of the most remote ancestor of this resource under
     * the owner-of relation, excluding the root user unless this resource is
     * owned by the root user
     *
     * @return int
     */
    public function primaryOwner()
    {
        if ($this->owner === User::ROOT)
            return User::ROOT;
        $id = $this->owner;
        while (true) {
            $user = User::load(['id' => $id]);
            $owner = $user->resource()->owner();
            if ($owner == User::ROOT)
                break;
            $id = $owner;
        }
        return $id;
    }

    /*
     * Returns the time at which this CodeRage\Access\Resource_ was disabled,
     * if any, as a UNIX timestamp
     *
     * Returns: int
     */
    public function disabled() { return $this->disabled; }

    /*
     * Disables or enables this CodeRage\Access\Resource_
     *
     * @param boolean $disabled true to disable this CodeRage\Access\Resource_
     */
    public function setDisabled($disabled)
    {
        if ($disabled == ($this->disabled !== null))
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' =>
                        'Resource is already ' .
                        ($disabled ? 'disabled' : 'enabled')
                ]);
        $db = new Db;
        $value = $disabled ? Time::get() : null;
        $db->update(
            'AccessResource',
            ['disabled' => $value],
            $this->id
        );
        $this->disabled = $value;
    }

    /*
     * Returns the time at which this CodeRage\Access\Resource_ was
     * soft-deleted, if any, as a UNIX timestamp
     *
     * Returns: int
     */
    public function retired() { return $this->retired; }

    /*
     * Soft-deletes or resurrects this CodeRage\Access\Resource_
     *
     * @param boolean $retired true to soft-delete this CodeRage\Access\Resource_
     */
    public function setRetired($retired)
    {
        if ($retired == ($this->retired !== null))
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' =>
                        'Resource is ' .
                        ($retired ? 'already retired' : 'not retired')
                ]);
        $db = new Db;
        $value = $retired ? Time::get() : null;
        $db->update(
            'AccessResource',
            ['retired' => $value],
            $this->id
        );
        $this->retired = $value;
    }

    /**
     * Returns this instance
     */
    public function resource()
    {
        return $this;
    }

    /**
     * Creates an instance of CodeRage\Access\Resource_ and stores it in the database
     *
     * @param mixed $type The resource type of the CodeRage\Access\Resource_ to
     *   be cteated, specified by name or as an instance of
     *   CodeRage\Access\ResourceType
     * @param int $owner The owner of the CodeRage\Access\Resource_ to be
     *   created, as the ID of a record in the table AccessUser
     * @return CodeRage\Access\Resource_
     */
    public static function create($type, $owner = null)
    {
        if (is_string($type))
            $type = ResourceType::load(['name' => $type]);
        if ($owner === null)
            $owner = User::ROOT;
        $resource = null;
        $db = new Db;
        $db->beginTransaction();
        try {
            $id =
                $db->insert(
                    'AccessResource',
                    [
                        'type' => $type->id(),
                        'owner' => $owner
                    ]
                );
            $resource = new Resource_($type, $owner, null, null, $id);
            if (Access::initialized()) {
                $group = $owner == User::ROOT ?
                    self::rootPrivateGroup() :
                    Group::load(['id' => User::load(['id' => $owner])->privateGroupId()]);
                $group->add($resource);
            }
        } catch (Throwable $e) {
            $db->rollback();
            throw new
                Error([
                    'details' => 'Failed creating resource',
                    'inner' => $e
                ]);
        }
        $db->commit();
        return $resource;
    }

    /**
     * Returns the resource with the given database ID or resource ID
     *
     * @param array $options The options array; must contain exactly one of the
     *   keys 'id' and 'resourceId'
     * @return CodeRage\Access\Resource_
     * @throws CodeRage\Error if no such resource exists
     */
    public static function load($options)
    {
        if (!isset($options['id']) && !isset($options['resourceId']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing database ID or resourceId'
                ]);
        if (isset($options['id']) && isset($options['resourceId']))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'id' and 'resourceId' are incompatible"
                ]);
        $key = isset($options['id']) ?
            $options['id'] :
            $options['resourceId'];
        $db = new Db;
        $row = null;
        try {
            if (isset($options['id'])) {
                $sql = 'SELECT * FROM AccessResource WHERE RecordID = %i';
                $row = $db->fetchFirstArray($sql, $key);
            } else {
                list($name, $value) = ResourceId::parse($key);
                $type = ResourceType::load(['name' => $name]);
                $table = $type->tableName();
                $sql =
                    "SELECT r.*
                     FROM AccessResource r
                     JOIN [$table] t
                       ON t.resource = r.RecordID
                     WHERE t.RecordID = %i";
                $row = $db->fetchFirstArray($sql, $value);
            }
        } catch (Throwable $e) {
            $ident = isset($options['id']) ?
                "with ID '$key'" :
                $key;
            throw new
                Error([
                    'details' => "Failed loading resource $ident",
                    'inner' => $e
                ]);
        }
        if (!$row) {
            $ident = isset($options['id']) ?
                "with ID '$key'" :
                $key;
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' =>
                        "Failed loading resource $ident: no such resource"
                ]);
        }
        return new
            Resource_(
                ResourceType::load(['id' => (int) $row['type']]),
                (int) $row['owner'],
                $row['disabled'] !== null ?
                    (int) $row['disabled'] :
                    null,
                $row['retired'] !== null ?
                    (int) $row['retired'] :
                    null,
                (int) $row['RecordID']
            );
    }

    /**
     * Deletes this resource from the database; invalidates this instance
     */
    public function delete()
    {
        $db = new Db;
        $db->beginTransaction();
        try {
            $db->delete('AccessGroupMember', ['member' => $this->id]);
            $db->delete('AccessResource', $this->id);
            $this->id = null;
        } catch (Throwable $e) {
            $db->rollback();
            throw new
                Error([
                    'details' => "Failed deleting $this",
                    'inner' => $e
                ]);
        }
        $db->commit();
    }

    /**
     * Returns the user, group, permission, or resource with the given
     * descriptor
     *
     * @param string $descriptor A string with one other the following forms,
     *   where TYPE is a resource type name:
     *     TYPE-xxxxxxxx - The resource of type TYPE, with ID equal to the
     *       result of decoding the hexidecial value xxxxxxxx
     *     TYPE(I) - The resource of type TYPE having ID N
     *     TYPE(NAME) - The resource of type TYPE whose alternate primary key
     *       value is NAME
     *   If TYPE is "user", "group", or "perm", the returned value will have
     * @param string $expectedType The expected resource type; if it does not
     *   match the type of the loaded resource, an exception will be thrown
     *   (optional)
     * @return mixed An instance of CodeRage\Access\User, CodeRage\Access\Group,
     *   or CodeRage\Access\Permission, if TYPE is "user", "group", or "perm",
     *   and an instance of CodeRage\Access\Resource, otherwise
     */
    public static function loadDescriptor($descriptor, $expectedType = null)
    {
        Args::check($descriptor, 'string', 'resource descriptor');
        $match = null;
        if (!preg_match(self::MATCH_DESCRIPTOR, $descriptor, $match))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid resource descriptor: $descriptor"
                ]);
        list($all, $type, $value) = $match;
        if ($expectedType !== null && $type != $expectedType)
            throw new
                Error([
                    'status' => 'TYPE_ERROR',
                    'message' =>
                        "Failed loading resource descriptor '$descriptor': " .
                        "expected resource of type '$expectedType'; found " .
                        "resource of type '$type'"
                ]);
        if ($type == 'string' && $type == 'any' && $type == 'none')
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Unsupported resource type: $type"
                ]);
        switch ($value[0]) {
        case '-':
            $value = ResourceId::decode(substr($value, 1));
            break;
        case '(':
            $value = (int) substr($value, 1, -1);
            break;
        default:
            $value = substr($value, 1, -1);
            break;
        }
        $type = ResourceType::load(['name' => $type]);
        switch ($type->name()) {
        case 'group':
            $key = is_string($value) ? 'name' : 'id';
            return Group::load([$key => $value]);
        case 'perm':
            $key = is_string($value) ? 'name' : 'id';
            return Permission::load([$key => $value]);
        case 'user':
            $key = is_string($value) ? 'username' : 'id';
            return User::load([$key => $value]);
        default:
            $table = $type->tableName();
            $where = is_string($value) ?
                '[' . $type->columnName() . '] = %s' :
                'RecordID = %i';
            $ph =
            $sql =
                "SELECT resource {i}
                 FROM [$table]
                 WHERE $where";
            $id = (new Db)->fetchValue($sql, $value);
            if ($id === null)
                throw new
                    Error([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'message' =>
                            'No such ' . strtolower($type->title()) . ': ' .
                            $descriptor
                    ]);
            return Resource_::load(['id' => $id]);
        }
    }

    /**
     * @todo ensure $associatedId is always set, to avoid a database query
     */
    public function __toString()
    {
        try {
            $type = $this->type;
            $table = $type->tableName();
            if (($column = $type->columnName()) !== null) {
                $name =
                    (new Db)->fetchValue(
                        "SELECT [$column]
                         FROM [$table] t
                         JOIN AccessResource r
                           ON r.RecordID = t.resource
                         WHERE r.RecordID = %i",
                        $this->id
                    );
                return $type->name() . "[$name]";
            } else {
                $id =
                    (new Db)->fetchValue(
                        "SELECT t.RecordID {i}
                         FROM [$table] t
                         JOIN AccessResource r
                           ON r.RecordID = t.resource
                         WHERE r.RecordID = %i",
                        $this->id
                    );
                return ResourceId::encode($id, $type->name());
            }
        } catch (Throwable $e) {
            return ResourceId::encode($this->id, 'resource');
        }
    }

    /**
     * Returns the private group of the root user
     *
     * @return CodeRage\Access\Group
     */
    private static function rootPrivateGroup()
    {
        static $group;
        if ($group === null) {
            try {
                $root = User::load(['id' => User::ROOT]);
                $group = $root->privateGroup();
            } catch (Error $e) {
                if ($e->status() != 'OBJECT_DOES_NOT_EXIST')
                    throw $e;
            }
        }
        return $group;
    }

    /**
     * The ID of a record in the table AccessResource, if any, corresponding to
     * this CodeRage\Access\Resource_
     *
     * @var int
     */
    private $id;

    /**
     * The resource type of this CodeRage\Access\Resource_
     *
     * @var CodeRage\Access\ResourceType
     */
    private $type;

    /**
     * The owner of this CodeRage\Access\Resource_, as the ID of a record in the
     * table AccessUser
     *
     * @var int
     */
    private $owner;

    /**
     * The ID of the record in the in the associated table whose "resource"
     * column points to this resource, if any
     *
     * @var int
     */
    private $associatedId;
}
