<?php

/**
 * Contains the definition of CodeRage\Db\Schema\Generator
 *
 * File:        CodeRage/Db/Schema/Generator.php
 * Date:        Tue May 29 17:20:36 MDT 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db\Schema;

use CodeRage\File;

/**
 * @ignore
 */

/**
 * Generates descriptions of data sources for use at runtime.
 */
final class Generator {

    /**
     * Constructs a CodeRage\Db\Schema\Generator based on the given instance of
     * CodeRage\Db\DataSource
     *
     * @param CodeRage\Db\DataSource $dataSource
     */
    public function __construct(\CodeRage\Db\Schema\DataSource $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * Generates the runtime data source definition
     */
    public function generate()
    {
        $name = $this->dataSource->name();
        $params = $this->dataSource->params();
        $options = [];
        foreach (
            [ 'dbms', 'host', 'port', 'username', 'password',
              'database', 'options']
            as $opt)
        {
            $options[$opt] = $params->$opt();
        }
        $path =
            \CodeRage\Config::current()->getRequiredProperty('project_root') .
            "/.coderage/db/$name.json";
        File::mkdir(dirname($path));
        file_put_contents($path, json_encode($options));
    }

    /**
     * @var CodeRage\Db\Schema\Generator
     */
    private $dataSource;
}
