<?php

/**
 * Defines the class CodeRage\Access\Build\Tool
 *
 * File:        CodeRage/Access/Build/Tool.php
 * Date:        Tue Jan  1 15:44:47 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Build;

use CodeRage\Access;
use CodeRage\Build\Info;
use CodeRage\Build\Run;
use CodeRage\Build\Target\Callback;
use CodeRage\Build\Tool\Basic;
use CodeRage\Config;
use CodeRage\Db;
use CodeRage\Db\Operations;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');

/**
 * Initializes the access control system for the default data source, if it has
 * not already been initialized
 */
class Tool extends Basic {

    /**
     * Constructs a CodeRage\Build\Tool\DataSource.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns true if $localName is 'access' and $namespace is the
     * CodeRage.Build project namespace
     *
     * @param string $localName
     * @param string $namespace
     * @return boolean
     */
    function canParse($localName, $namespace)
    {
        return $localName == 'access' &&
               $namespace = \CodeRage\Build\NAMESPACE_URI;
    }

    /**
     * Returns a target that initializes the access control system for the
     * default data source, if it has not already been initialized
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param DOMElement $element
     * @param string $baseUri The URI for resolving relative paths referenced by
     *   $elt
     * @return CodeRage\Build\Target
     * @throws CodeRage\Error
     */
    function parseTarget(Run $run, \DOMElement $elt, $baseUri)
    {
        $info =
            new Info([
                    'label' => 'Access Control Build Tool',
                    'description' => 'Initializes the access control system'
                ]);
        return new
            Callback(
                function() use($run) { $this->execute($run); },
                null, [],
                $info,
                $elt, $baseUri
            );
    }

    /**
     * Runs the underlying collection of test suites and emails the output.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        $config = Config::current();
        if (!$config->hasProperty('default_datasource'))
            return;
        $params = (new Db)->params();
        if (!in_array($params->database(), Operations::listDatabases($params)))
            return;
        if (!Access::initialized())
            Access::initialize();
    }
}
