<?php

/**
 * Defines the class CodeRage\Access\ResourceType
 *
 * File:        CodeRage/Access/ResourceType.php
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
 * Represents a general category of resource
 */
final class ResourceType {

    /**
     * @var array
     */
    const OPTIONS =
        [ 'id' => 1, 'name' => 1, 'title' => 1, 'description' => 1,
          'tableName' => 1, 'columnName' => 1 ];

    /**
     * @var string
     */
    const MATCH_NAME = '/^[a-z][_a-z0-9]*$/';

    /**
     * Constructs an instance of CodeRage\Access\ResourceType
     *
     * @param array $options The options array; supports the following options:
     *     id - The database ID
     *     name - The machine-readable name of the resource type
     *     title - A descriptive label of the resource type
     *     description - A description of the resource type
     *     tableName - The name of the table, if any, that stores
     *       resources with type equasl to the resource type under construction
     *     columnName - The name of a character-typed column that can be used as
     *       an alternate primary key, e.g., "username" for the resource
     *       type "user" or "name" for the resource type "group"
     */
    private function __construct(array $options)
    {
        foreach (array_keys(self::OPTIONS) as $o)
            $this->$o = $options[$o];
    }

    /**
     * Returns the ID of the record in the table AccessResourceType, if any,
     * corresponding to this CodeRage\Access\ResourceType
     *
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Returns the machine-readable name of this CodeRage\Access\ResourceType
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns a descriptive label of this CodeRage\Access\ResourceType
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * Sets the descriptive label of this CodeRage\Access\ResourceType
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns a description of this CodeRage\Access\ResourceType
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Sets the description of this CodeRage\Access\ResourceType
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the name of the table, if any, that stores resources of the
     * type represented by this CodeRage\Access\ResourceType
     *
     * @return string
     */
    public function tableName()
    {
        return $this->tableName;
    }

    /**
     * Returns the name of a character-typed column that can be used as an
     * alternate primary key, e.g., "username" for the resource type "user" or
     * "name" for the resource type "group-definition"
     *
     * @return string
     */
    public function columnName()
    {
        return $this->columnName;
    }

    /**
     * Creates an instance of CodeRage\Access\ResourceType and saves it to the
     * database
     *
     * @param array $options The options array; supports the following options:
     *     id - The database ID
     *     name - The machine-readable name of the resource type
     *     title - A descriptive label of the resource type
     *     description - A description of the resource type
     *     tableName - The name of the table, if any, that stores
     *       resources with type equasl to the resource type under construction
     *     columnName - The name of a character-typed column that can be used as
     *       an alternate primary key, e.g., "username" for the resource
     *       type "user" or "name" for the resource type "group"

     * @return CodeRage\Access\ResourceType
     */
    public static function create(array $options)
    {
        if (isset($options['id']))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'Unsupported option: id'
                ]);
        $options['id'] = null;
        foreach (array_keys($options) as $o)
            if (!array_key_exists($o, self::OPTIONS))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Unsupported option: $o"
                    ]);
        $name =
            Args::checkKey($options, 'name', 'string', [
                'required' => true
            ]);
        if (!preg_match(self::MATCH_NAME, $name))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid name: $name"
                ]);
        Args::checkKey($options, 'title', 'string', [
            'default' => null
        ]);
        Args::checkKey($options, 'description', 'string', [
            'default' => null
        ]);
        Args::checkKey($options, 'tableName', 'string', [
            'default' => null
        ]);
        if ( !isset($options['tableName']) &&
             $name != 'string' &&
             $name != 'any' &&
             $name != 'none' )
        {
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing table name'
                ]);
        }
        Args::checkKey($options, 'columnName', 'string', [
            'default' => null
        ]);
        if (isset($options['columnName']) && !isset($options['tableName']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing table name'
                ]);
        $type = new ResourceType($options);
        $type->save();
        self::$cache[$type->id()] = self::$cache[$name] = $type;
        return $type;
    }

    /**
     * Returns the resource type with the specified name or ID
     *
     * @param array $options The options array; must contain exactly one of the
     *   keys 'name' or 'id'
     * @return CodeRage\Access\ResourceType
     */
    public static function load(array $options)
    {
        Args::checkKey($options, 'name', 'string');
        Args::checkKey($options, 'id', 'int', [
            'label' => 'ID'
        ]);
        $opt = Args::uniqueKey($options, ['name', 'id']);
        $key = $options[$opt];
        if (!isset(self::$cache[$key])) {
            $db = new Db;
            $isId = isset($options['id']);
            $sql = $isId ?
                'SELECT * FROM AccessResourceType WHERE RecordID = %i' :
                'SELECT * FROM AccessResourceType WHERE name = %s';
            $row = null;
            try {
                $row = $db->fetchFirstArray($sql, $key);
            } catch (Throwable $e) {
                $ident = $isId ? "with ID '$key'" : $key;
                throw new
                    Error([
                        'details' => "Failed loading resource type $ident",
                        'inner' => $e
                    ]);
            }
            if (!$row) {
                $ident = $isId ? "with ID '$key'" : $key;
                throw new
                    Error([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'details' => "No resource type $ident exists"
                    ]);
            }
            $row['id'] = (int) $row['RecordID'];
            unset($row['RecordID']);
            $type = new ResourceType($row);
            self::$cache[$row['id']] = self::$cache[$row['name']] = $type;
        }
        return self::$cache[$key];
    }

    /**
     * Saves this resource type to the database
     */
    public function save()
    {
        $db = new Db;
        $id = $this->id;
        if ($id !== null) {
            try {
                $sql =
                    'UPDATE AccessResourceType
                     SET title = %s, description = %s
                     WHERE RecordID = %i';
                $db->query($sql, $this->title, $this->description, $id);
            } catch (Throwable $e) {
                if (!$this->exists()) {
                    throw new
                        Error([
                            'status' => 'OBJECT_DOES_NOT_EXIST',
                            'details' =>
                                "No resource type named '$this->name' " .
                                "exists"
                        ]);
                } else {
                    throw new
                        Error([
                            'details' =>
                                "Failed saving resource type '$this->name'",
                            'inner' => $e
                        ]);
                }
            }
        } else {
            try {
                $this->id =
                    $db->insert(
                        'AccessResourceType',
                        [
                            'name' => $this->name,
                            'title' => $this->title,
                            'description' => $this->description,
                            'tableName' => $this->tableName,
                            'columnName' => $this->columnName
                        ]
                    );
            } catch (Throwable $e) {
                if ($this->exists()) {
                    throw new
                        Error([
                            'status' => 'OBJECT_EXISTS',
                            'details' =>
                                "A resource type named '$this->name' " .
                                "already exists"
                        ]);
                } else {
                    throw new
                        Error([
                            'details' =>
                                "Failed saving resource type '$this->name'",
                            'inner' => $e
                        ]);
                }
            }
        }
        self::$cache = [];
    }

    /**
     * Deletes this resource type from the database; invalidates this instance
     */
    public function delete()
    {
        try {
            $db = new Db;
            $sql = 'DELETE FROM AccessResourceType WHERE RecordID = %i';
            $db->query($sql, $this->id);
            $this->id = null;
        } catch (Throwable $e) {
            throw new
                Error([
                    'details' =>
                        "Failed deleting resource type '$this->name'",
                    'inner' => $e
                ]);
        }
        self::$cache = [];
    }

    /**
     * Returns true if a resource type with the same name as this resource type
     * is stored in the database
     *
     * @return boolean
     */
    private function exists()
    {
        $db = new Db;
        $sql = 'SELECT COUNT(*) FROM AccessResourceType WHERE name = %s';
        return $db->fetchValue($sql, $this->name) == 1;
    }

    /**
     * Cache used by load() to minimze database queries
     *
     * @var array
     */
    private static $cache = [];

    /**
     * The ID of a record in the table AccessResourceType, if any, corresponding
     * to this CodeRage\Access\ResourceType
     *
     * @var int
     */
    private $id;

    /**
     * The machine-readable name of this resource type
     *
     * @var string
     */
    private $name;

    /**
     * A descriptive label of this resource type
     *
     * @var string
     */
    private $title;

    /**
     * A description of this resource type
     *
     * @var string
     */
    private $description;

    /**
     * The name of the table, if any, that stores resources of the type
     * represented by this resource type
     *
     * @var string
     */
    private $tableName;

    /**
     * The name of a character-typed column that can be used as an
     * alternate primary key, e.g., "username" for the resource type "user" or
     * "name" for the resource type "group-definition"
     *
     * @var string
     */
    private $columnName;
}
