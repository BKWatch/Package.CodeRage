<?php

/**
 * Defines the class CodeRage\Access\Test\SuiteDog
 * 
 * File:        CodeRage/Access/Test/SuiteDog.php
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

class SuiteDog extends SuiteAnimal {
    protected function __construct($name, $color, $resource, $id)
    {
        parent::__construct($name, $color, $resource, $id);
    }

    public static function create($name, $color, $owner = null)
    {
        return parent::createImpl($name, $color, $owner, 'dog');
    }

    public static function load($nameOrId)
    {
        return parent::loadImpl($nameOrId, 'dog');
    }
}
