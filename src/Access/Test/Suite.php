<?php

/**
 * Defines the class CodeRage\Access\Test\Suite
 *
 * File:        CodeRage/Access/Test/Suite.php
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
use CodeRage\Access;
use CodeRage\Access\Group;
use CodeRage\Access\Managed;
use CodeRage\Access\Permission;
use CodeRage\Access\ResourceId;
use CodeRage\Access\ResourceType;
use CodeRage\Access\Resource_;
use CodeRage\Access\Session;
use CodeRage\Access\User;
use CodeRage\Sys\Config\Array_ as ArrayConfig;
use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Db\Operations;
use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\Random;

/**
 * @ignore
 */

/**
 * Test suite for the CodeRage access control system
 */
class Suite extends \CodeRage\Test\ReflectionSuite {

    /**
     * The path to the XML file defining the test database
     *
     * @var string
     */
    const SCHEMA = __DIR__ . '/test.dbx';

    /**
     * The length of the random component of the database name
     *
     * @var int
     */
    const RANDOM_STRING_LENGTH = 30;

    /**
     * Constructs an instance of CodeRage\Access\Test\Suite.
     */
    public function __construct()
    {
        parent::__construct(
            "Access Suite",
            "Tests the CodeRage access control system"
        );
    }

    public function testResourceType1()
    {
        $type =
            ResourceType::create([
                'name' => 'lizard',
                'title' => 'Lizard',
                'description' => 'A lizard',
                'tableName' => 'Lizard',
                'columnName' => 'name'
            ]);
        Assert::equal($type->name(), 'lizard');
        Assert::equal($type->title(), 'Lizard');
        Assert::equal($type->description(), 'A lizard');
        Assert::equal($type->tableName(), 'Lizard');
        Assert::equal($type->columnName(), 'name');
        $type = ResourceType::load(['name' => 'lizard']);
        Assert::equal($type->name(), 'lizard');
        Assert::equal($type->title(), 'Lizard');
        Assert::equal($type->description(), 'A lizard');
        Assert::equal($type->tableName(), 'Lizard');
        Assert::equal($type->columnName(), 'name');
        $type->setTitle('Polar Bear');
        $type->setDescription('A polar bear');
        $type->save();
        $type = ResourceType::load(['name' => 'lizard']);
        Assert::equal($type->name(), 'lizard');
        Assert::equal($type->title(), 'Polar Bear');
        Assert::equal($type->description(), 'A polar bear');
        Assert::equal($type->tableName(), 'Lizard');
        Assert::equal($type->columnName(), 'name');
    }

    public function testResourceTypeFail1()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $type = ResourceType::load(['name' => 'lizard']);
    }

    public function testResourceTypeFail2()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $type =
            ResourceType::create([
                'name' => 'lizard',
                'title' => 'Lizard',
                'description' => 'A lizard',
                'tableName' => 'Lizard',
                'columnName' => 'name'
            ]);
        $type->delete();
        $type = ResourceType::load(['name' => 'lizard']);
    }

    public function testResourceId()
    {
        $values =
            [
                1000 => '2f7ce362',
                2000 => '09372d4a',
                3000 => '2f7d1b52',
                4000 => '0936d6ba',
                5000 => '2f7d1082',
                6000 => '0936deea',
                7000 => '2f7d08f2',
                8000 => '0936c6da',
                9000 => '2f7d0022',
                10000 => '0936ce0a'
            ];
        foreach ($values as $n => $v) {
            Assert::equal(
                ResourceId::encode($n),
                $v
            );
            Assert::equal(
                ResourceId::encode($n, 'cat'),
                "cat-$v"
            );
            Assert::equal(
                ResourceId::decode($v),
                $n
            );
            Assert::equal(
                ResourceId::decode("cat-$v", 'cat'),
                $n
            );
            list($type, $value) = ResourceId::parse("cat-$v");
            Assert::equal($type, 'cat');
            Assert::equal($value, $n);
        }
    }

    public function testResource1()
    {
        $rsrc = Resource_::create('cat');
        $rsrc2 = Resource_::load(['id' => $rsrc->id()]);
        Assert::equal($rsrc2->type()->name(), 'cat');
        Assert::equal($rsrc2->id(), $rsrc->id());
    }

    public function testResource2()
    {
        $user1 = User::create('user1');
        Assert::equal(
            $user1->resource()->owner(),
            User::ROOT
        );
        Assert::equal(
            $user1->resource()->primaryOwner(),
            User::ROOT
        );
        $user2 = User::create('user2', null, $user1->id());
        Assert::equal(
            $user2->resource()->owner(),
            $user1->id()
        );
        Assert::equal(
            $user2->resource()->primaryOwner(),
            $user1->id()
        );
        $user3 = User::create('user3', null, $user2->id());
        Assert::equal(
            $user3->resource()->owner(),
            $user2->id()
        );
        Assert::equal(
            $user3->resource()->primaryOwner(),
            $user1->id()
        );
        $user4 = User::create('user4', null, $user3->id());
        Assert::equal(
            $user4->resource()->owner(),
            $user3->id()
        );
        Assert::equal(
            $user4->resource()->primaryOwner(),
            $user1->id()
        );
    }

    public function testResource4()
    {
        $cat = SuiteCat::create('debby', 'blue');
        $id = ResourceId::encode($cat->id(), 'cat');
        $rsrc = Resource_::load(['resourceId' => $id]);
        Assert::equal($rsrc->id(), $cat->resource()->id());
    }

    public function testResourceFail2()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $rsrc = Resource_::create('lizard');
    }

    public function testResourceFail3()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $rsrc = Resource_::create('cat');
        $id = $rsrc->id();
        $rsrc->delete();
        Resource_::load(['id' => $id]);
    }

    public function testGroupCreate()
    {
        $group =
            Group::create([
                'name' => 'frogs',
                'title' => 'Frogs',
                'description' => 'The group of all frogs',
                'domain' => 'any'
            ]);
        Assert::equal($group->name(), 'frogs');
        Assert::equal($group->title(), 'Frogs');
        Assert::equal($group->description(), 'The group of all frogs');
        Assert::equal($group->domain()->name(), 'any');
    }

    public function testGroupLoad()
    {
        $group1 =
            Group::create([
                'name' => 'frogs',
                'title' => 'Frogs',
                'description' => 'The group of all frogs',
                'domain' => 'any'
            ]);
        $group2 = Group::load(['id' => $group1->id()]);
        Assert::equal($group2->name(), 'frogs');
        Assert::equal($group2->title(), 'Frogs');
        Assert::equal($group2->description(), 'The group of all frogs');
        Assert::equal($group2->domain()->name(), 'any');
        $group3 = Group::load(['name' => 'frogs']);
        Assert::equal($group3->name(), 'frogs');
        Assert::equal($group3->title(), 'Frogs');
        Assert::equal($group3->description(), 'The group of all frogs');
        Assert::equal($group3->domain()->name(), 'any');
    }

    public function testGroupUpdate()
    {
        $group1 =
            Group::create([
                'name' => 'frogs',
                'title' => 'Frogs',
                'description' => 'The group of all frogs',
                'domain' => 'any'
            ]);
        $group1->setTitle('Toads');
        $group1->setDescription('The group of all toads');
        Assert::equal($group1->title(), 'Toads');
        Assert::equal($group1->description(), 'The group of all toads');
        $group2 = Group::load(['id' => $group1->id()]);
        Assert::equal($group2->title(), 'Toads');
        Assert::equal($group2->description(), 'The group of all toads');
    }

    public function testGroupFail1()
    {
        $this->setExpectedStatusCode('OBJECT_EXISTS');
        $def1 =
            Group::create([
                'name' => 'frogs',
                'title' => 'Frogs',
                'description' => 'The group of all frogs',
                'domain' => 'any'
            ]);
        $def2 =
            Group::create([
                'name' => 'frogs',
                'title' => 'More frogs',
                'description' => 'Another group of all frogs',
                'domain' => 'any'
            ]);
    }

    public function testGroupFail2()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        Group::load(['name' => 'frogs']);
    }

    public function testHierarchy1()
    {
        // Diamond pattern:  g1
        //                  /  \
        //                 g2  g3
        //                  \  /
        //                   g4
        $def =
            Group::create([
                'name' => 'example',
                'domain' => 'any'
            ]);
        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g1->addChild($g2);
        $g1->addChild($g3);
        $g2->addChild($g4);
        $g3->addChild($g4);

        // Test parenthood
        Assert::isFalse($g1->parentOf($g1));
        Assert::isTrue($g1->parentOf($g2));
        Assert::isTrue($g1->parentOf($g3));
        Assert::isFalse($g1->parentOf($g4));
        Assert::isFalse($g2->parentOf($g1));
        Assert::isFalse($g2->parentOf($g2));
        Assert::isFalse($g2->parentOf($g3));
        Assert::isTrue($g2->parentOf($g4));
        Assert::isFalse($g3->parentOf($g1));
        Assert::isFalse($g3->parentOf($g2));
        Assert::isFalse($g3->parentOf($g3));
        Assert::isTrue($g3->parentOf($g4));
        Assert::isFalse($g4->parentOf($g1));
        Assert::isFalse($g4->parentOf($g2));
        Assert::isFalse($g4->parentOf($g3));
        Assert::isFalse($g4->parentOf($g4));

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isTrue($g1->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isFalse($g2->ancestorOf($g3));
        Assert::isTrue($g2->ancestorOf($g4));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isTrue($g3->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
    }

    public function testHierarchy2()
    {
        // X pattern:  g1  g2
        //              \  /
        //               g3
        //              /  \
        //             g4   g5

        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g5 = Group::create(['name' => 'g5']);
        $g1->addChild($g3);
        $g2->addChild($g3);
        $g3->addChild($g4);
        $g3->addChild($g5);

        // Test parenthood
        Assert::isFalse($g1->parentOf($g1));
        Assert::isFalse($g1->parentOf($g2));
        Assert::isTrue($g1->parentOf($g3));
        Assert::isFalse($g1->parentOf($g4));
        Assert::isFalse($g1->parentOf($g5));
        Assert::isFalse($g2->parentOf($g1));
        Assert::isFalse($g2->parentOf($g2));
        Assert::isTrue($g2->parentOf($g3));
        Assert::isFalse($g2->parentOf($g4));
        Assert::isFalse($g2->parentOf($g5));
        Assert::isFalse($g3->parentOf($g1));
        Assert::isFalse($g3->parentOf($g2));
        Assert::isFalse($g3->parentOf($g3));
        Assert::isTrue($g3->parentOf($g4));
        Assert::isTrue($g3->parentOf($g5));
        Assert::isFalse($g4->parentOf($g1));
        Assert::isFalse($g4->parentOf($g2));
        Assert::isFalse($g4->parentOf($g3));
        Assert::isFalse($g4->parentOf($g4));
        Assert::isFalse($g4->parentOf($g5));
        Assert::isFalse($g5->parentOf($g1));
        Assert::isFalse($g5->parentOf($g2));
        Assert::isFalse($g5->parentOf($g3));
        Assert::isFalse($g5->parentOf($g4));
        Assert::isFalse($g5->parentOf($g5));

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isFalse($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isTrue($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isTrue($g2->ancestorOf($g4));
        Assert::isTrue($g2->ancestorOf($g5));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isTrue($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g5));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
    }

    public function testHierarchy3()
    {
        // Double V pattern:  g1  g2
        //                     \  /
        //                      g3
        //                       |
        //                      g4
        //                      /  \
        //                    g5   g6
        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g5 = Group::create(['name' => 'g5']);
        $g6 = Group::create(['name' => 'g6']);

        // Create initial configuration
        $g1->addChild($g3);
        $g2->addChild($g3);
        $g3->addChild($g4);
        $g4->addChild($g5);
        $g4->addChild($g6);

        // Remove middle link
        $g3->removeChild($g4);

        // Test parenthood
        Assert::isFalse($g1->parentOf($g1));
        Assert::isFalse($g1->parentOf($g2));
        Assert::isTrue($g1->parentOf($g3));
        Assert::isFalse($g1->parentOf($g4));
        Assert::isFalse($g1->parentOf($g5));
        Assert::isFalse($g1->parentOf($g6));
        Assert::isFalse($g2->parentOf($g1));
        Assert::isFalse($g2->parentOf($g2));
        Assert::isTrue($g2->parentOf($g3));
        Assert::isFalse($g2->parentOf($g4));
        Assert::isFalse($g2->parentOf($g5));
        Assert::isFalse($g2->parentOf($g6));
        Assert::isFalse($g3->parentOf($g1));
        Assert::isFalse($g3->parentOf($g2));
        Assert::isFalse($g3->parentOf($g3));
        Assert::isFalse($g3->parentOf($g4));
        Assert::isFalse($g3->parentOf($g5));
        Assert::isFalse($g3->parentOf($g6));
        Assert::isFalse($g4->parentOf($g1));
        Assert::isFalse($g4->parentOf($g2));
        Assert::isFalse($g4->parentOf($g3));
        Assert::isFalse($g4->parentOf($g4));
        Assert::isTrue($g4->parentOf($g5));
        Assert::isTrue($g4->parentOf($g6));
        Assert::isFalse($g5->parentOf($g1));
        Assert::isFalse($g5->parentOf($g2));
        Assert::isFalse($g5->parentOf($g3));
        Assert::isFalse($g5->parentOf($g4));
        Assert::isFalse($g5->parentOf($g5));
        Assert::isFalse($g6->parentOf($g6));
        Assert::isFalse($g6->parentOf($g1));
        Assert::isFalse($g6->parentOf($g2));
        Assert::isFalse($g6->parentOf($g3));
        Assert::isFalse($g6->parentOf($g4));
        Assert::isFalse($g6->parentOf($g5));
        Assert::isFalse($g6->parentOf($g6));

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isFalse($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isFalse($g1->ancestorOf($g5));
        Assert::isFalse($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isFalse($g3->ancestorOf($g5));
        Assert::isFalse($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isTrue($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isFalse($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));

        // Restore middle link
        $g3->addChild($g4);

        // Test parenthood
        Assert::isFalse($g1->parentOf($g1));
        Assert::isFalse($g1->parentOf($g2));
        Assert::isTrue($g1->parentOf($g3));
        Assert::isFalse($g1->parentOf($g4));
        Assert::isFalse($g1->parentOf($g5));
        Assert::isFalse($g1->parentOf($g6));
        Assert::isFalse($g2->parentOf($g1));
        Assert::isFalse($g2->parentOf($g2));
        Assert::isTrue($g2->parentOf($g3));
        Assert::isFalse($g2->parentOf($g4));
        Assert::isFalse($g2->parentOf($g5));
        Assert::isFalse($g2->parentOf($g6));
        Assert::isFalse($g3->parentOf($g1));
        Assert::isFalse($g3->parentOf($g2));
        Assert::isFalse($g3->parentOf($g3));
        Assert::isTrue($g3->parentOf($g4));
        Assert::isFalse($g3->parentOf($g5));
        Assert::isFalse($g3->parentOf($g6));
        Assert::isFalse($g4->parentOf($g1));
        Assert::isFalse($g4->parentOf($g2));
        Assert::isFalse($g4->parentOf($g3));
        Assert::isFalse($g4->parentOf($g4));
        Assert::isTrue($g4->parentOf($g5));
        Assert::isTrue($g4->parentOf($g6));
        Assert::isFalse($g5->parentOf($g1));
        Assert::isFalse($g5->parentOf($g2));
        Assert::isFalse($g5->parentOf($g3));
        Assert::isFalse($g5->parentOf($g4));
        Assert::isFalse($g5->parentOf($g5));
        Assert::isFalse($g6->parentOf($g6));
        Assert::isFalse($g6->parentOf($g1));
        Assert::isFalse($g6->parentOf($g2));
        Assert::isFalse($g6->parentOf($g3));
        Assert::isFalse($g6->parentOf($g4));
        Assert::isFalse($g6->parentOf($g5));
        Assert::isFalse($g6->parentOf($g6));

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isFalse($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isTrue($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isTrue($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isTrue($g2->ancestorOf($g4));
        Assert::isTrue($g2->ancestorOf($g5));
        Assert::isTrue($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isTrue($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isTrue($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isTrue($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isFalse($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));
    }

    public function testHierarchy4()
    {
        // Double V pattern:    g1
        //                     /  \
        //                    g2  g3
        //                    |    |
        //                    g4  g5
        //                     \  /
        //                      g6
        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g5 = Group::create(['name' => 'g5']);
        $g6 = Group::create(['name' => 'g6']);

        // Create initial configuration
        $g1->addChild($g2);
        $g1->addChild($g3);
        $g2->addChild($g4);
        $g3->addChild($g5);
        $g4->addChild($g6);
        $g5->addChild($g6);

        // Remove middle link on left
        $g2->removeChild($g4);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isTrue($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isFalse($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isTrue($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));

        // Remove middle link on right
        $g3->removeChild($g5);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isFalse($g1->ancestorOf($g5));
        Assert::isFalse($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isFalse($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isFalse($g3->ancestorOf($g5));
        Assert::isFalse($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));

        // Restore middle link on right
        $g3->addChild($g5);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isTrue($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isFalse($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isTrue($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));

        // Restore middle link on left
        $g2->addChild($g4);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isTrue($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isTrue($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isFalse($g2->ancestorOf($g3));
        Assert::isTrue($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isTrue($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isTrue($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));
    }

    public function testHierarchy5()
    {
        // Zigzag pattern:
        //                   g2    g4    g6    g8
        //                  /  \  /  \  /  \  /
        //                 g1   g3    g5    g7
        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g5 = Group::create(['name' => 'g5']);
        $g6 = Group::create(['name' => 'g6']);
        $g7 = Group::create(['name' => 'g7']);
        $g8 = Group::create(['name' => 'g8']);

        // Create initial configuration
        $g2->addChild($g1);
        $g2->addChild($g3);
        $g4->addChild($g3);
        $g4->addChild($g5);
        $g6->addChild($g5);
        $g6->addChild($g7);
        $g8->addChild($g7);

        // Remove middle link
        $g4->removeChild($g5);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isFalse($g1->ancestorOf($g2));
        Assert::isFalse($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isFalse($g1->ancestorOf($g5));
        Assert::isFalse($g1->ancestorOf($g6));
        Assert::isFalse($g1->ancestorOf($g7));
        Assert::isFalse($g1->ancestorOf($g8));
        Assert::isTrue($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g7));
        Assert::isFalse($g2->ancestorOf($g8));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isFalse($g3->ancestorOf($g5));
        Assert::isFalse($g3->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g7));
        Assert::isFalse($g3->ancestorOf($g8));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isTrue($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isFalse($g4->ancestorOf($g5));
        Assert::isFalse($g4->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g7));
        Assert::isFalse($g4->ancestorOf($g8));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isFalse($g5->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g7));
        Assert::isFalse($g5->ancestorOf($g8));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isTrue($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));
        Assert::isTrue($g6->ancestorOf($g7));
        Assert::isFalse($g6->ancestorOf($g8));
        Assert::isFalse($g7->ancestorOf($g1));
        Assert::isFalse($g7->ancestorOf($g2));
        Assert::isFalse($g7->ancestorOf($g3));
        Assert::isFalse($g7->ancestorOf($g4));
        Assert::isFalse($g7->ancestorOf($g5));
        Assert::isFalse($g7->ancestorOf($g6));
        Assert::isTrue($g7->ancestorOf($g7));
        Assert::isFalse($g7->ancestorOf($g8));
        Assert::isFalse($g8->ancestorOf($g1));
        Assert::isFalse($g8->ancestorOf($g2));
        Assert::isFalse($g8->ancestorOf($g3));
        Assert::isFalse($g8->ancestorOf($g4));
        Assert::isFalse($g8->ancestorOf($g5));
        Assert::isFalse($g8->ancestorOf($g6));
        Assert::isTrue($g8->ancestorOf($g7));
        Assert::isTrue($g8->ancestorOf($g8));

        // Restore middle link
        $g4->addChild($g5);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isFalse($g1->ancestorOf($g2));
        Assert::isFalse($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isFalse($g1->ancestorOf($g5));
        Assert::isFalse($g1->ancestorOf($g6));
        Assert::isFalse($g1->ancestorOf($g7));
        Assert::isFalse($g1->ancestorOf($g8));
        Assert::isTrue($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g7));
        Assert::isFalse($g2->ancestorOf($g8));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isFalse($g3->ancestorOf($g5));
        Assert::isFalse($g3->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g7));
        Assert::isFalse($g3->ancestorOf($g8));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isTrue($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isTrue($g4->ancestorOf($g5));
        Assert::isFalse($g4->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g7));
        Assert::isFalse($g4->ancestorOf($g8));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isFalse($g5->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g7));
        Assert::isFalse($g5->ancestorOf($g8));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isTrue($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));
        Assert::isTrue($g6->ancestorOf($g7));
        Assert::isFalse($g6->ancestorOf($g8));
        Assert::isFalse($g7->ancestorOf($g1));
        Assert::isFalse($g7->ancestorOf($g2));
        Assert::isFalse($g7->ancestorOf($g3));
        Assert::isFalse($g7->ancestorOf($g4));
        Assert::isFalse($g7->ancestorOf($g5));
        Assert::isFalse($g7->ancestorOf($g6));
        Assert::isTrue($g7->ancestorOf($g7));
        Assert::isFalse($g7->ancestorOf($g8));
        Assert::isFalse($g8->ancestorOf($g1));
        Assert::isFalse($g8->ancestorOf($g2));
        Assert::isFalse($g8->ancestorOf($g3));
        Assert::isFalse($g8->ancestorOf($g4));
        Assert::isFalse($g8->ancestorOf($g5));
        Assert::isFalse($g8->ancestorOf($g6));
        Assert::isTrue($g8->ancestorOf($g7));
        Assert::isTrue($g8->ancestorOf($g8));
    }

    public function testHierarchy6()
    {
        // Vertical line:  g1
        //                 |
        //                 g2
        //                 |
        //                 g3
        //                 |
        //                 g4
        //                 |
        //                 g5
        //                 |
        //                 g6
        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g5 = Group::create(['name' => 'g5']);
        $g6 = Group::create(['name' => 'g6']);

        // Create initial configuration
        $g1->addChild($g2);
        $g2->addChild($g3);
        $g3->addChild($g4);
        $g4->addChild($g5);
        $g5->addChild($g6);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isTrue($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isTrue($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isTrue($g2->ancestorOf($g4));
        Assert::isTrue($g2->ancestorOf($g5));
        Assert::isTrue($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isTrue($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isTrue($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isTrue($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));

        // Remove middle link
        $g3->removeChild($g4);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isFalse($g1->ancestorOf($g4));
        Assert::isFalse($g1->ancestorOf($g5));
        Assert::isFalse($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isFalse($g2->ancestorOf($g4));
        Assert::isFalse($g2->ancestorOf($g5));
        Assert::isFalse($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isFalse($g3->ancestorOf($g4));
        Assert::isFalse($g3->ancestorOf($g5));
        Assert::isFalse($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isTrue($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));

        // Restore middle link
        $g3->addChild($g4);

        // Test ancestry
        Assert::isTrue($g1->ancestorOf($g1));
        Assert::isTrue($g1->ancestorOf($g2));
        Assert::isTrue($g1->ancestorOf($g3));
        Assert::isTrue($g1->ancestorOf($g4));
        Assert::isTrue($g1->ancestorOf($g5));
        Assert::isTrue($g1->ancestorOf($g6));
        Assert::isFalse($g2->ancestorOf($g1));
        Assert::isTrue($g2->ancestorOf($g2));
        Assert::isTrue($g2->ancestorOf($g3));
        Assert::isTrue($g2->ancestorOf($g4));
        Assert::isTrue($g2->ancestorOf($g5));
        Assert::isTrue($g2->ancestorOf($g6));
        Assert::isFalse($g3->ancestorOf($g1));
        Assert::isFalse($g3->ancestorOf($g2));
        Assert::isTrue($g3->ancestorOf($g3));
        Assert::isTrue($g3->ancestorOf($g4));
        Assert::isTrue($g3->ancestorOf($g5));
        Assert::isTrue($g3->ancestorOf($g6));
        Assert::isFalse($g4->ancestorOf($g1));
        Assert::isFalse($g4->ancestorOf($g2));
        Assert::isFalse($g4->ancestorOf($g3));
        Assert::isTrue($g4->ancestorOf($g4));
        Assert::isTrue($g4->ancestorOf($g5));
        Assert::isTrue($g4->ancestorOf($g6));
        Assert::isFalse($g5->ancestorOf($g1));
        Assert::isFalse($g5->ancestorOf($g2));
        Assert::isFalse($g5->ancestorOf($g3));
        Assert::isFalse($g5->ancestorOf($g4));
        Assert::isTrue($g5->ancestorOf($g5));
        Assert::isTrue($g5->ancestorOf($g6));
        Assert::isFalse($g6->ancestorOf($g1));
        Assert::isFalse($g6->ancestorOf($g2));
        Assert::isFalse($g6->ancestorOf($g3));
        Assert::isFalse($g6->ancestorOf($g4));
        Assert::isFalse($g6->ancestorOf($g5));
        Assert::isTrue($g6->ancestorOf($g6));
    }

    public function testMembership()
    {
        // X pattern:  g1  g2
        //              \  /
        //               g3
        //              /  \
        //             g4   g5

        // Construct initial hierarchy
        $u = Group::universal();
        $g1 = Group::create(['name' => 'g1']);
        $g2 = Group::create(['name' => 'g2']);
        $g3 = Group::create(['name' => 'g3']);
        $g4 = Group::create(['name' => 'g4']);
        $g5 = Group::create(['name' => 'g5']);
        $g1->addChild($g3);
        $g2->addChild($g3);
        $g3->addChild($g4);
        $g3->addChild($g5);
        $c1 = SuiteCat::create('c1', 'c1');
        $c2 = SuiteCat::create('c2', 'c2');
        $c3 = SuiteCat::create('c3', 'c3');
        $c4 = SuiteCat::create('c4', 'c4');
        $c5 = SuiteCat::create('c5', 'c5');
        $g1->add($c1);
        $g2->add($c2);
        $g3->add($c3);
        $g4->add($c4);
        $g5->add($c5);

        // Verify membership relations
        Assert::isTrue($u->contains($c1));
        Assert::isTrue($u->contains($c2));
        Assert::isTrue($u->contains($c3));
        Assert::isTrue($u->contains($c4));
        Assert::isTrue($u->contains($c5));
        Assert::isTrue($g1->contains($c1));
        Assert::isFalse($g1->contains($c2));
        Assert::isTrue($g1->contains($c3));
        Assert::isTrue($g1->contains($c4));
        Assert::isTrue($g1->contains($c5));
        Assert::isFalse($g2->contains($c1));
        Assert::isTrue($g2->contains($c2));
        Assert::isTrue($g2->contains($c3));
        Assert::isTrue($g2->contains($c4));
        Assert::isTrue($g2->contains($c5));
        Assert::isFalse($g3->contains($c1));
        Assert::isFalse($g3->contains($c2));
        Assert::isTrue($g3->contains($c3));
        Assert::isTrue($g3->contains($c4));
        Assert::isTrue($g3->contains($c5));
        Assert::isFalse($g4->contains($c1));
        Assert::isFalse($g4->contains($c2));
        Assert::isFalse($g4->contains($c3));
        Assert::isTrue($g4->contains($c4));
        Assert::isFalse($g4->contains($c5));
        Assert::isFalse($g5->contains($c1));
        Assert::isFalse($g5->contains($c2));
        Assert::isFalse($g5->contains($c3));
        Assert::isFalse($g5->contains($c4));
        Assert::isTrue($g5->contains($c5));

        // Modify hierarchy
        $g3->delete();

        // Verify membership relations
        Assert::isTrue($u->contains($c1));
        Assert::isTrue($u->contains($c2));
        Assert::isTrue($u->contains($c3));
        Assert::isTrue($u->contains($c4));
        Assert::isTrue($u->contains($c5));
        Assert::isTrue($g1->contains($c1));
        Assert::isFalse($g1->contains($c2));
        Assert::isFalse($g1->contains($c3));
        Assert::isFalse($g1->contains($c4));
        Assert::isFalse($g1->contains($c5));
        Assert::isFalse($g2->contains($c1));
        Assert::isTrue($g2->contains($c2));
        Assert::isFalse($g2->contains($c3));
        Assert::isFalse($g2->contains($c4));
        Assert::isFalse($g2->contains($c5));
        Assert::isFalse($g4->contains($c1));
        Assert::isFalse($g4->contains($c2));
        Assert::isFalse($g4->contains($c3));
        Assert::isTrue($g4->contains($c4));
        Assert::isFalse($g4->contains($c5));
        Assert::isFalse($g5->contains($c1));
        Assert::isFalse($g5->contains($c2));
        Assert::isFalse($g5->contains($c3));
        Assert::isFalse($g5->contains($c4));
        Assert::isTrue($g5->contains($c5));
    }

    public function testCreateUser()
    {
        $user1 = User::create('user1');
        Assert::equal(
            $user1->resource()->owner(),
            User::ROOT
        );
        Assert::equal(
            $user1->username(),
            'user1'
        );
        $user2 = User::create('user2', null, $user1->id());
        Assert::equal(
            $user2->resource()->owner(),
            $user1->id()
        );
        Assert::equal(
            $user2->username(),
            'user2'
        );
    }

    public function testCreateUserFail()
    {
        $this->setExpectedStatusCode('OBJECT_EXISTS');
        $user1 = User::create('user1');
        Assert::equal(
            $user1->resource()->owner(),
            User::ROOT
        );
        Assert::equal(
            $user1->username(),
            'user1'
        );
        $user1 = User::create('user1');
    }

    public function testLoadUser()
    {
        $user1 = User::create('user1');
        $user2 = User::load(['id' => $user1->id()]);
        Assert::equal(
            $user2->resource()->owner(),
            User::ROOT
        );
        Assert::equal(
            $user2->username(),
            'user1'
        );
        $user3 = User::load(['username' => 'user1']);
        Assert::equal(
            $user3->resource()->owner(),
            User::ROOT
        );
        Assert::equal(
            $user3->username(),
            'user1'
        );
    }

    public function testUserAuthenticate()
    {
        $password = Random::string(30);
        $user1 = User::create('user1', $password);
        $session =
            Session::authenticate([
                'username' => 'user1',
                'password' => $password
            ]);
        Assert::equal($session->user()->id(), $user1->id());
        $password = Random::string(30);
        $user1->setPassword($password);
        $session =
            Session::authenticate([
                'username' => 'user1',
                'password' => $password
            ]);
        Assert::equal($session->user()->id(), $user1->id());
    }

    public function testUserGroups()
    {
        $user1 = User::load(['id' => User::ROOT]);
        $user2 = User::create('user2', null, $user1->id());
        $user3 = User::create('user3', null, $user2->id());
        $public1 = Group::load(['id' => $user1->publicGroupId()]);
        $public2 = Group::load(['id' => $user2->publicGroupId()]);
        $public3 = Group::load(['id' => $user3->publicGroupId()]);
        $private1 = Group::load(['id' => $user1->privateGroupId()]);
        $private2 = Group::load(['id' => $user2->privateGroupId()]);
        $private3 = Group::load(['id' => $user3->privateGroupId()]);
        $singleton1 = Group::load(['id' => $user1->singletonGroupId()]);
        $singleton2 = Group::load(['id' => $user2->singletonGroupId()]);
        $singleton3 = Group::load(['id' => $user3->singletonGroupId()]);
        Assert::isTrue($public1->ancestorOf($public2));
        Assert::isTrue($public2->ancestorOf($public3));
        Assert::isTrue($private1->ancestorOf($private2));
        Assert::isTrue($private2->ancestorOf($private3));
        Assert::isTrue($singleton1->contains($user1));
        Assert::isTrue($singleton2->contains($user2));
        Assert::isTrue($singleton3->contains($user3));
    }

    public function testPermissionCreate()
    {
        $group =
            Permission::create([
                'name' => 'leap',
                'title' => 'Leap',
                'description' => 'Permission to leap',
                'domain' => 'any'
            ]);
        Assert::equal($group->name(), 'leap');
        Assert::equal($group->title(), 'Leap');
        Assert::equal($group->description(), 'Permission to leap');
        Assert::equal($group->domain()->name(), 'any');
    }

    public function testPermissionLoad()
    {
        $group1 =
            Permission::create([
                'name' => 'leap',
                'title' => 'Leap',
                'description' => 'Permission to leap',
                'domain' => 'any'
            ]);
        $group2 = Permission::load(['id' => $group1->id()]);
        Assert::equal($group2->name(), 'leap');
        Assert::equal($group2->title(), 'Leap');
        Assert::equal($group2->description(), 'Permission to leap');
        Assert::equal($group2->domain()->name(), 'any');
        $group3 = Permission::load(['name' => 'leap']);
        Assert::equal($group3->name(), 'leap');
        Assert::equal($group3->title(), 'Leap');
        Assert::equal($group3->description(), 'Permission to leap');
        Assert::equal($group3->domain()->name(), 'any');
    }

    public function testPermissionUpdate()
    {
        $group1 =
            Permission::create([
                'name' => 'leap',
                'title' => 'Leap',
                'description' => 'Permission to leap',
                'domain' => 'any'
            ]);
        $group1->setTitle('Jump');
        $group1->setDescription('Permission to jump');
        Assert::equal($group1->title(), 'Jump');
        Assert::equal($group1->description(), 'Permission to jump');
        $group2 = Permission::load(['id' => $group1->id()]);
        Assert::equal($group2->title(), 'Jump');
        Assert::equal($group2->description(), 'Permission to jump');
    }

    public function testPermissionFail1()
    {
        $this->setExpectedStatusCode('OBJECT_EXISTS');
        $def1 =
            Permission::create([
                'name' => 'leap',
                'title' => 'Leap',
                'description' => 'Permission to leap',
                'domain' => 'any'
            ]);
        $def2 =
            Permission::create([
                'name' => 'leap',
                'title' => 'Leap again',
                'description' => 'Permission to leap again',
                'domain' => 'any'
            ]);
    }

    public function testPermissionFail2()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        Permission::load(['name' => 'leap']);
    }

    public function testPermissionHierarchy()
    {
        $names =
            [ 'view', 'modify', 'use', 'list', 'add-member',
              'remove-member' ];
        $children =
            [
               'modify' => ['view'],
               'use' => ['view']
            ];
        $any = Permission::load(['name' => 'any']);
        $permissions = [];
        foreach ($names as $n)
            $permissions[$n] = Permission::load(['name' => $n]);
        foreach ($permissions as $p)
            Assert::isTrue($any->parentOf($p));
        foreach ($permissions as $m => $a) {
            foreach ($permissions as $n => $b) {
                $expected = isset($children[$m]) && in_array($n, $children[$m]);
                Assert::equal(
                    $a->parentOf($b),
                    $expected,
                    "$m is not a parent of $n"
                );
            }
        }
    }

    public function testGrant1()
    {
        // Create objects
        $p = Permission::create(['name' => 'p']);
        $ug = Group::create(['name' => 'u']);
        $cg = Group::create(['name' => 'c']);
        $u = User::create('u');
        $c = SuiteCat::create('c', 'c');
        $ug->add($u);
        $cg->add($c);

        // Test granting and revoking using IDs
        Permission::grant([
            'permission' => $p->id(),
            'grantee' => $ug->id(),
            'target' => $cg->id()
        ]);
        Assert::isTrue(
            Permission::test([
                'permission' => $p->id(),
                'user' => $u->id(),
                'resource' => $c->resource()->id()
            ])
        );
        Permission::revoke([
            'permission' => $p->id(),
            'grantee' => $ug->id(),
            'target' => $cg->id()
        ]);
        Assert::isFalse(
            Permission::test([
                'permission' => $p->id(),
                'user' => $u->id(),
                'resource' => $c->resource()->id()
            ])
        );

        // Test granting and revoking using names for named objects
        // objects for resources
        Permission::grant([
            'permission' => $p->name(),
            'grantee' => $ug->name(),
            'target' => $cg->name()
        ]);
        Assert::isTrue(
            Permission::test([
                'permission' => $p->name(),
                'user' => $u->username(),
                'resource' => $c->resource()
            ])
        );
        Permission::revoke([
            'permission' => $p->name(),
            'grantee' => $ug->name(),
            'target' => $cg->name()
        ]);
        Assert::isFalse(
            Permission::test([
                'permission' => $p->name(),
                'user' => $u->username(),
                'resource' => $c->resource()
            ])
        );

        // Test granting and revoking using objects
        Permission::grant([
            'permission' => $p,
            'grantee' => $ug,
            'target' => $cg
        ]);
        Assert::isTrue(
            Permission::test([
                'permission' => $p,
                'user' => $u,
                'resource' => $c
            ])
        );
        Permission::revoke([
            'permission' => $p,
            'grantee' => $ug,
            'target' => $cg
        ]);
        Assert::isFalse(
            Permission::test([
                'permission' => $p,
                'user' => $u,
                'resource' => $c
            ])
        );
    }

    public function testGrant2()
    {
        $permission = $userGroup = $catGroup = $user = $cat = [];
        for ($i = 0; $i < 5; ++$i) {
            $permission[$i] = Permission::create(['name' => "p$i"]);
            $userGroup[$i] = Group::create(['name' => "u$i"]);
            $catGroup[$i] = Group::create(['name' => "c$i"]);
            $user[$i] = User::create("u$i");
            $cat[$i] = SuiteCat::create("c$i", "c$i");
            $userGroup[$i]->add($user[$i]);
            $catGroup[$i]->add($cat[$i]);
            if ($i > 0) {
                $permission[$i - 1]->addChild($permission[$i]);
                $userGroup[$i - 1]->addChild($userGroup[$i]);
                $catGroup[$i - 1]->addChild($catGroup[$i]);
            }
        }
        Permission::grant([
            'permission' => $permission[2],
            'grantee' => $userGroup[2],
            'target' => $catGroup[2]
        ]);
        foreach ($permission as $i => $p) {
            foreach ($user as $j => $u) {
                foreach ($cat as $k => $c) {
                    $actual =
                        Permission::test([
                            'permission' => $p,
                            'user' => $u,
                            'resource' => $c
                        ]);
                    $expected = $i >= 2 & $j >= 2 && $k >= 2;
                    if ($actual != $expected) {
                        $verb = $actual ? 'has' : "doesn't have";
                        throw new
                            Error([
                                'status' => 'ASSERTION_FAILED',
                                'message' =>
                                    "User $u $verb $p access to resource " .
                                    "cat$k"

                            ]);
                    }
                }
            }
        }
    }

    public function testGrant3()
    {
        $permission = $group = $user = null;
        for ($i = 0; $i < 5; ++$i) {
            $permission[$i] =
                Permission::create([
                    'name' => "p$i",
                    'domain' => 'none'
                ]);
            $group[$i] = Group::create(['name' => "u$i"]);
            $user[$i] = User::create("u$i");
            $group[$i]->add($user[$i]);
            if ($i > 0) {
                $permission[$i - 1]->addChild($permission[$i]);
                $group[$i - 1]->addChild($group[$i]);
            }
        }
        Permission::grant([
            'permission' => $permission[2],
            'grantee' => $group[2]
        ]);
        foreach ($permission as $i => $p) {
            foreach ($user as $j => $u) {
                $actual =
                    Permission::test([
                        'permission' => $p,
                        'user' => $u
                    ]);
                $expected = $i >= 2 & $j >= 2;
                if ($actual != $expected) {
                    $verb = $actual ? 'has' : "doesn't have";
                    throw new
                        Error([
                            'status' => 'ASSERTION_FAILED',
                            'message' => "User $u $verb $p"
                        ]);
                }
            }
        }
    }

    /**
     * Tests the method Permission::test()
     */
    public function testPermissionTest()
    {
        $hierarchy = $this->createGrantHierarchy();
        $this->checkGrantHierarchy(
            $hierarchy,
            function($p, $u, $r)
            {
                return
                    Permission::test([
                        'permission' => $p,
                        'user' => $u,
                        'resource' => $r
                    ]);
            }
        );
    }

    /**
     * Tests the method Permission::join()
     */
    public function testPermissionJoin()
    {
        $db = new Db;
        $hierarchy = $this->createGrantHierarchy();
        $this->checkGrantHierarchy(
            $hierarchy,
            function($p, $u, $r) use ($db)
            {
                $join =
                    Permission::join([
                         'permission' => $p,
                         'user' => $u->id(),
                         'resource' => 'c.resource'
                    ]);
                $sql =
                    "SELECT COUNT(*)
                     FROM Cat c
                     $join
                     WHERE c.RecordID = " . $r->id();
                return $db->fetchValue($sql) > 0;
            }
        );
    }

    /**
     * Tests the view AccessCheck
     */
    public function testAccessCheck()
    {
        $db = new Db;
        $hierarchy = $this->createGrantHierarchy();
        $result =
            $db->query(
                "SELECT a.permission, a.username, c.RecordID
                 FROM AccessCheck a
                 JOIN Cat c
                   ON c.resource = a.resource
                 WHERE a.permission LIKE 'p_' AND
                       a.username LIKE 'u_'"
            );
        $hasAccess = [];
        while ($row = $result->fetchRow()) {
            [$p, $u, $r] = $row;
            $hasAccess["$p#$u#$r"] = 1;
        }
        $this->checkGrantHierarchy(
            $hierarchy,
            function($p, $u, $r) use ($hasAccess)
            {
                $key = $p->name() . '#' . $u->username() . '#' . $r->id();
                return isset($hasAccess[$key]);
            }
        );
    }

    protected function componentInitialize($component)
    {
        // Construct new project configuration
        $initial = Config::current();
        $properties = [];
        foreach (['host', 'username', 'password'] as $name) {
            $properties["db.$name"] =
                $initial->getProperty("test.db.$name");
        }
        $properties['db.database'] =
            '__test_' . Random::string(self::RANDOM_STRING_LENGTH);
        $new = new \CodeRage\Sys\Config\Array_($properties, $initial);

        // Create database
        $this->params = \CodeRage\Db\Params::create($new);
        Operations::createDatabase(self::SCHEMA, $this->params);

        // Install configuration
        Config::setCurrent($new);
        $this->initialConfig = $initial;

        // Populate database
        Access::initialize();
        ResourceType::create([
            'name' => 'cat',
            'title' => 'Cat',
            'description' => 'A cat',
            'tableName' => 'Cat',
            'columnName' => 'name'
        ]);
        ResourceType::create([
            'name' => 'dog',
            'title' => 'Dog',
            'description' => 'A dog',
            'tableName' => 'Dog',
            'columnName' => 'name'
        ]);
    }

    protected function componentCleanup($component)
    {
        try {
            Operations::dropDatabase($this->params->database(), $this->params);
        } finally {
            if ($this->initialConfig !== null)
                Config::setCurrent($this->initialConfig);
        }
    }

    private function createGrantHierarchy()
    {
        $hierarchy = [];
        foreach ([1, 2, 3, 4, 5] as $i) {
            $hierarchy["p$i"] = Permission::create(['name' => "p$i"]);
            $hierarchy["ug$i"] = Group::create(['name' => "ug$i"]);
            $hierarchy["rg$i"] = Group::create(['name' => "rg$i"]);
            $hierarchy["u$i"] = User::create("u$i", null);
            $hierarchy["r$i"] = SuiteCat::create("r$i", 'brown');
            $hierarchy["ug$i"]->add($hierarchy["u$i"]);
            $hierarchy["rg$i"]->add($hierarchy["r$i"]);
            if ($i > 1)
                foreach (['p', 'ug', 'rg'] as $n)
                    $hierarchy[$n . ($i - 1)]->addChild($hierarchy[$n . $i]);
        }
        Permission::grant([
            'permission' => 'p3',
            'grantee' => 'ug3',
            'target' => 'rg3',
        ]);
        return $hierarchy;
    }

    private function checkGrantHierarchy(array $hierarchy, callable $test)
    {
        $range = [1, 2, 3, 4, 5];
        foreach ($range as $p) {
            foreach ($range as $u) {
                foreach ($range as $r) {
                    $expected = $p >= 3 && $u >=3 && $r >= 3;
                    $actual =
                        $test(
                            $hierarchy["p$p"],
                            $hierarchy["u$u"],
                            $hierarchy["r$r"]
                        );
                    Assert::equal(
                        $actual,
                        $expected,
                        "Failed testing access for [p$p, u$u ({$hierarchy["u$u"]->id()}), r$r]"
                    );
                }
            }
        }
    }

    /**
     * Connection parameter for test database
     *
     * @var CodeRage\Db\Params
     */
    private $params;

    /**
     * The current configuration at the time of suite execution, to be
     * reinstalled as the current configuration when the suite terminates
     *
     * @var CodeRage\Config
     */
    private $initialConfig;
}
