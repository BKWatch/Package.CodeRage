<?php

/**
 * Test suite for CodeRage\Util\Test\Base62
 *
 * File:        CodeRage/Util/Test/Base62Suite.php
 * Date:        Tue May 16 15:34:19 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CodeRage
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Test\Assert;
use CodeRage\Util\Base62;

/**
 * Test suite for the CodeRage\Util\Test\Base62
 */
class Base62Suite extends \CodeRage\Test\ReflectionSuite  {

    /**
     * Constructs an instance of CodeRage\Util\Test\Base62
     */
    public function __construct()
    {
        parent::__construct(
            "Base62 Test Suite",
            "Tests the Base62 class"
        );
    }

    public function testEncodeDecodeInteger1()
    {
        $encoded = Base62::encode(44378123123);
        Assert::equal(
            Base62::decode($encoded, true),
            44378123123,
            'encoded string'
        );
    }

    public function testEncodeDecodeInteger2()
    {
        $encoded = Base62::encode(23123);
        Assert::equal(Base62::decode($encoded, true), 23123, 'encoded string');
    }

    public function testEncodeDecodeInteger3()
    {
        $encoded = Base62::encode(0);
        Assert::equal(Base62::decode($encoded, true), 0, 'encoded string');
    }

    public function testEncodeDecodeInteger4()
    {
        $encoded = Base62::encode(11111);
        Assert::equal(
            Base62::decode($encoded, true),
            11111,
            'encoded string'
        );
    }

    public function testEncodeDecodeString1()
    {
        $encoded = Base62::encode('44378123123');
        Assert::equal(Base62::decode($encoded), '44378123123', 'encoded string');
    }

    public function testEncodeDecodeString2()
    {
        $encoded = Base62::encode('23123');
        Assert::equal(Base62::decode($encoded), '23123', 'encoded string');
    }

    public function testEncodeDecodeString3()
    {
        $encoded = Base62::encode('This is test string');
        Assert::equal(
            Base62::decode($encoded),
            'This is test string',
            'encoded string'
        );
    }

    public function testEncodeDecodeString4()
    {
        $encoded = Base62::encode('0000011111');
        Assert::equal(Base62::decode($encoded), '0000011111', 'encoded string');
    }

    public function testEncodeDecodeString5()
    {
        $encoded = Base62::encode('0');
        Assert::equal(
            Base62::decode($encoded),
            '0',
            'encoded string'
        );
    }

    public function testEncodeDecodeString6()
    {
        $encoded = Base62::encode('My # = 1121');
        Assert::equal(
            Base62::decode($encoded),
            'My # = 1121',
            'encoded string'
        );
    }
}
