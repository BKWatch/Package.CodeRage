<?php

/**
 * Defines the class CodeRage\Build\Test\TargetSuite
 * 
 * File:        CodeRage/Build/Test/TargetSuite.php
 * Date:        Tue Mar 17 11:19:04 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test;

use CodeRage\Build\BuildConfigFile;
use CodeRage\Error;
use function CodeRage\Xml\firstChildElement;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');
require_once('CodeRage/Build/Tool/Packages.php');
require_once('CodeRage/File/rm.php');
require_once('CodeRage/Text/split.php');
require_once('CodeRage/Xml/firstChildElement.php');
require_once('CodeRage/Xml/getBooleanAttribute.php');
require_once('CodeRage/Xml/loadDom.php');

/**
 * Test suite for tools, targets, and target sets.
 */
class TargetSuite extends \CodeRage\Test\Suite {

    /**
     * Constructs a CodeRage\Build\Test\Suite.
     */
    public function __construct()
    {
        parent::__construct(
            'Tools and Targets Test Suite',
            'Tests tools, targets, and target sets'
        );
        $this->constructCases();
    }

    /**
     * Parses the underlying project definition file.
     *
     */
    private function constructCases()
    {
        $handler = new \CodeRage\Util\ErrorHandler;
        $path = dirname(__FILE__) . '/Project';
        $dir = $handler->_opendir($path);
        if ($handler->errno())
            throw new Error(['message' => "Failed reading directory: $path"]);
        while (($file = @readdir($dir)) !== false) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'xml')
                $this->add(new TargetCase("$path/$file"));
        }
        @closedir($dir);
    }
}
