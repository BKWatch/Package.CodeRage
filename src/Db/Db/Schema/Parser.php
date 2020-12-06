<?php

/**
 * Contains the definition of the class CodeRage\Db\Schema\Parser
 *
 * File:        CodeRage/Db/Schema/Parser.php
 * Date:        Tue Apr 24 02:09:22 MDT 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db\Schema;

use DOMElement;
use CodeRage\Db\Params;
use CodeRage\File;
use CodeRage\Text;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Constructs instances of CodeRage\Db\Schema\DataSource and
 * CodeRage\Db\Schema\Data from XML definitions
 */
final class Parser extends ParserBase {

    /**
     * Path to the data source schema
     *
     * @var string
     */
    public const SCHEMA_PATH = __DIR__ . '/dataSource.xsd';

    /**
     * Constructs an instance of CodeRage\Db\Schema\DataSource from an XML
     * data source definition
     *
     * @param string $path The path to the data source definition
     * @return CodeRage\Db\Schema\DataSource
     */
    public static function parseDataSourceXml($path)
    {
        $dom = self::parseXml($path, self::SCHEMA_PATH);
        $root = $dom->documentElement;
        if ($root->nodeName != "dataSource")
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' =>
                        "Invalid dataSource definition '$path': expected " .
                        "'dataSource' element; found '$root->nodeName'"
                ]);
        return self::parseDataSourceDom($root, $path);
    }

    /**
     * Constructs an instance of CodeRage\Db\Schema\DataSource from a
     * "dataSource" element
     *
     * @param DOMElement $ds A "dataSource" element
     * @param string $baseUri The URI for resolving relative paths referenced by
     *   $ds.
     * @return CodeRage\Db\Schema\DataSource
     */
    public static function parseDataSourceDom(DOMElement $ds, $baseUri)
    {
        $name = $ds->getAttribute('name');
        $paramElt = Xml::firstChildElement($ds, 'connectionParams');
        $params = [];
        if (($elt = Xml::firstChildElement($paramElt, 'dbms')))
            $params['dbms'] =
                Text::expandExpressions($elt->nodeValue);
        if (($elt = Xml::firstChildElement($paramElt, 'host')))
            $params['host'] =
                Text::expandExpressions($elt->nodeValue);
        if (($elt = Xml::firstChildElement($paramElt, 'port')))
            $params['port'] =
                Text::expandExpressions($elt->nodeValue);
        if (($elt = Xml::firstChildElement($paramElt, 'username')))
            $params['username'] =
                Text::expandExpressions($elt->nodeValue);
        if (($elt = Xml::firstChildElement($paramElt, 'password')))
            $params['password'] =
                Text::expandExpressions($elt->nodeValue);
        $options = [];
        foreach (Xml::childElements($paramElt, 'option') as $k)
            $options[$k->getAttribute('name')] =
                Text::expandExpressions(Xml::textContent($k));
        if (!empty($options))
            $params['options'] = $options;
        $database =
            self::parseDatabaseDom(
                Xml::firstChildElement($ds, 'database'),
                $baseUri
            );
        $params['database'] = $database->name();
        return new
            \CodeRage\Db\Schema\DataSource(
                new Params($params),
                $database,
                $name
            );
    }

    /**
     * Constructs an instance of CodeRage\Db\Schema\Database from an XML
     * database definition
     *
     * @param string $path The path to the database definition
     * @return CodeRage\Db\Schema\Database
     */
    public static function parseDatabaseXml($path)
    {
        $dom = self::parseXml($path, self::SCHEMA_PATH);
        $root = $dom->documentElement;
        switch ($root->nodeName) {
        case 'dataSource':
            $dataSource =
                self::parseDataSourceDom(
                    $root, $path
                );
            return $dataSource->database();
        case 'database':
            return  self::parseDatabaseDom($root, $path);
        default:
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' =>
                        "Invalid database definition '$path': expected " .
                        "'dataSource' or 'database' element; found " .
                        "'$root->nodeName'"
                ]);
        }
    }

    /**
     * Constructs an instance of CodeRage\Db\Schema\DataSource from a
     * "dataSource" element
     *
     * @param DOMElement $ds A "dataSource" element
     * @param string $baseUri The URI for resolving relative paths referenced by
     *   $ds.
     * @return CodeRage\Db\Schema\Database
     */
    public static function parseDatabaseDom(DOMElement $node, $baseUri)
    {
        if ($src = Xml::getAttribute($node, 'src')) {
            $src = File::resolve($src, $baseUri);
            $dom = self::parseXml($src, self::SCHEMA_PATH);
            return self::parseDatabaseDom($dom->documentElement, $src);
        } else {
            $name = Text::expandExpressions($node->getAttribute("name"));
            return new Database($name);
        }
    }

    /**
     *
     * Reads a datasource XML file and expands common columns
     * and includes.
     *
     * @param string $xml The path of the XML file to parse.
     * @param string $schema The path of an XML schema.
     * @return DOMDocument Document containing the expanded XML content.
     */
    private static function parseXml($xml, $schema)
    {
        $dom = Xml::loadDocument($xml, $schema);
        $root = $dom->documentElement;
        if ($root->nodeName == 'dataSource')
            $root = Xml::firstChildElement($root, 'database');
        foreach (Xml::childElements($root, 'include') as $inc) {
            $path = self::resolveSrcAttribute($inc);
            $dom2 = self::parseXml($path, $schema);
            $root2 = $dom2->documentElement;
            foreach (Xml::childElements($root2) as $elt) {
                $copy = $root->ownerDocument->importNode($elt, true);
                $root->appendChild($copy);
            }
        }
        foreach (Xml::childElements($root, 'include') as $inc)
            $root->removeChild($inc);
        return $dom;
    }
}
