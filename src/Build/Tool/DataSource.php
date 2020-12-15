<?php

/**
 * Defines the class CodeRage\Build\Tool\DataSource.
 *
 * File:        CodeRage/Build/Tool/DataSource.php
 * Date:        Mon Jan 19 23:57:32 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Tool;

use CodeRage\Build\Info;
use CodeRage\Build\Target\Callback;
use CodeRage\Config;
use CodeRage\File;
use CodeRage\Xml;

class DataSource extends Basic {

    /**
     * Constructs a CodeRage\Build\Tool\DataSource.
     */
    function __construct()
    {
        $info =
           new Info([
                   'label' => 'Data source configuration tool',
                   'description' =>
                      'Generates runtime data source configurations'
               ]);
        parent::__construct($info);
    }

    /**
     * Returns true if $localName is 'dataSource' and $namespace is the
     * CodeRage.Build project namespace.
     *
     * @param string $localName
     * @param string $namespace
     * @return boolean
     */
    function canParse($localName, $namespace)
    {
        return $localName == 'dataSource' &&
               $namespace = \CodeRage\Build\NAMESPACE_URI;
    }

    /**
     * Returns a target that when executed generates runtime support files
     * for the underlying data source.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @param DOMElement $element
     * @param string $baseUri The URI for resolving relative paths referenced by
     * $elt
     * @return CodeRage\Build\Target
     * @throws CodeRage\Error
     */
    function parseTarget(\CodeRage\Build\Engine $engine, \DOMElement $elt, $baseUri)
    {
        $config = Config::current();
        if ($config->hasProperty('default_datasource')) {
            $default = $config->getProperty('default_datasource');
            $dataSource =
                File::resolve(
                    $config->getRequiredProperty("datasource.$default"),
                    $baseUri
                );
            return new
                Callback(
                    function() use($dataSource) { $this->generate($dataSource); },
                    null,
                    [],
                    new Info([
                            'label' => "Data source '$default'",
                            'description' =>
                                "Generates runtime support files for data " .
                                "source '$default'"
                        ])
                );
        } else {
            return new
                Callback(
                    function() {},
                    null,
                    [],
                    new Info([
                            'label' => 'Null target',
                            'description' => 'No op'
                        ])
                );
        }
    }

    /**
     * Generates runtime support files for the underlying data source.
     *
     * @param mixed $dataSource The path to the data source definition or
     *   an instance of DOMElement representing the data source definition
     */
    function generate($dataSource)
    {
        $elt = is_string($dataSource) ?
            Xml::loadDocument($dataSource)->documentElement :
            $dataSource;
        $path = Xml::documentPath($elt->ownerDocument);
        $ds = \CodeRage\Db\Schema\Parser::parseDataSourceDom($elt, $path);
        $gen = new \CodeRage\Db\Schema\Generator($ds);
        $gen->generate();
    }
}
