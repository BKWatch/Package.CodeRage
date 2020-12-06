<?php

/**
 * Defines the class CodeRage\Access\Test\SuiteAnimal
 * 
 * File:        CodeRage/Access/Test/SuiteAnimal.php
 * Date:        Fri Jun 22 15:19:20 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Test;

use Exception;
use Throwable;
use CodeRage\Access\Group;
use CodeRage\Access\Managed;
use CodeRage\Access\Permission;
use CodeRage\Access\ResourceId;
use CodeRage\Access\ResourceType;
use CodeRage\Access\Resource_;
use CodeRage\Access\Session;
use CodeRage\Access\User;
use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Error;
use CodeRage\Test\Assert;

/**
 * @ignore
 */

abstract class SuiteAnimal implements Managed {
    protected function __construct($name, $color, $resource, $id)
    {
        $this->id = $id;
        $this->name = $name;
        $this->color = $color;
        $this->resource = $resource;
    }
    public function id() { return $this->id; }
    public function name() { return $this->name; }
    public function color() { return $this->color; }
    public function setColor($color) { return $this->color = $color; }
    public function resource() { return $this->resource; }
    protected static function createImpl($name, $color, $owner, $type)
    {
        $db = new Db;
        $db->beginTransaction();
        $result = null;
        try {
            if (is_string($type))
                $type = ResourceType::load(['name' => $type]);
            $resource = Resource_::create($type, $owner);
            $table = $type->name() == 'cat' ?
                'Cat' :
                'Dog';
            $class = $type->name() == 'cat' ?
                'CodeRage\Access\Test\SuiteCat' :
                'CodeRage\Access\Test\SuiteDog';
            $id =
                $db->insert(
                    $table,
                    [
                        'name' => $name,
                        'color' => $color,
                        'resource' => $resource->id()
                    ]
                );
            $result = new $class($name, $color, $resource, $id);
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
        $db->commit();
        return $result;
    }
    protected static function loadImpl($nameOrId, $type)
    {
        $db = new Db;
        $result = null;
        try {
            if (is_string($type))
                $type = ResourceType::load(['name' => $type]);
            $table = $type->name() == 'cat' ?
                'Cat' :
                'Dog';
            $class = $type->name() == 'cat' ?
                'CodeRage\Access\Test\SuiteCat' :
                'CodeRage\Access\Test\SuiteDog';
            $sql = is_int($nameOrId) ?
                "SELECT * FROM $table WHERE RecordID = %i" :
                "SELECT * FROM $table WHERE name = %s";
            $row = $db->fetchFirstRow($sql);
            if (!$row)
                throw new
                    Exception("No such " . strtolower($table) . ": $nameOrId");
            $resource =
                Resource_::load(['id' =>  $row['resource']]);
            $result =
                $class(
                    $row['name'],
                    $row['color'],
                    $resource,
                    $row['RecordID']
                );
        } catch (Throwable $e) {
            throw $e;
        }
        $db->commit();
        return $result;
    }
    public function save()
    {
        $db = new Db;
        try {
            $table = $type->name() == 'cat' ?
                'Cat' :
                'Dog';
            $sql = "UPDATE $table SET color = %s WHERE RecordID = %i";
            $db->query($sql, $id);
        } catch (Throwable $e) {
            throw $e;
        }
        $db->commit();
    }
}
