<?php

/**
 * Defines the class CodeRage\WebService\WsdlParser
 *
 * File:        CodeRage/WebService/WsdlParser.php
 * Date:        Sun Oct 11 19:01:10 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use CodeRage\Error;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Parses a WSDL to retrieve the target namespace os the XML Schema, the service
 * address, and the XML Schema text
 */
class WsdlParser {

    /**
     * Constructs an instance of CodeRage\WebService\WsdlParser
     *
     * @param string $path The path to the WSDL
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the path to the WSDL
     *
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Returns the target namespace of the XML Schema embedded in the WSDL
     *
     * @return string
     */
    public function targetNamespace()
    {
        if (!isset(self::$cache[$this->path]))
            self::$cache[$this->path] = $this->parse();
        return self::$cache[$this->path][0];
    }

    /**
     * Returns the service address
     *
     * @return string
     */
    public function serviceAddress()
    {
        if (!isset(self::$cache[$this->path]))
            self::$cache[$this->path] = $this->parse();
        return self::$cache[$this->path][1];
    }

    /**
     * Returns an XML Schema document constructed from the WSDL
     *
     * @return string The XML data
     */
    public function schema()
    {
        if (!isset(self::$cache[$this->path]))
            self::$cache[$this->path] = $this->parse();
        return self::$cache[$this->path][2];
    }

    /**
     * Parses the WSDL
     *
     * @param string $wsdl The path to the WSDL
     * @return array A triple [$targetNamespace, $serviceAddress, $schema]
     */
    public function parse()
    {
        $targetNamespace = $serviceAddress = $schema = null;

        // Parse XML
        $path = $this->path;
        $dom = null;
        try {
            $dom = Xml::loadDocument($path);
        } catch (Throwable $e) {
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Failed parsing WSDL '$path'",
                    'inner' => $e
                ]);
        }

        // Fetch schema element
        $wsdl = $dom->documentElement;
        $types = Xml::firstChildElement($wsdl, 'types');
        if (!$types)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Missing 'types' element in WSDL '$path'"
                ]);
        $schemaElt = Xml::firstChildElement($types, 'schema');
        if (!$schemaElt)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Missing 'schema' element in WSDL '$path'"
                ]);
        if (!$schemaElt->hasAttribute('targetNamespace'))
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' =>
                        "Missing target namespace on 'schema' in WSDL '$path'"
                ]);
        $targetNamespace = $schemaElt->getAttribute('targetNamespace');

        // Fetch service address
        $service = Xml::firstChildElement($wsdl, 'service');
        if (!$service)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Missing 'service' element in WSDL '$path'"
                ]);
        $port = Xml::firstChildElement($service, 'port');
        if (!$port)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Missing 'port' element in WSDL '$path'"
                ]);
        $address = Xml::firstChildElement($port, 'address');
        if (!$address)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Missing 'address' element in WSDL '$path'"
                ]);
        if (!$address->hasAttribute('location'))
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Missing service URI in WSDL '$path'"
                ]);
        $serviceAddress = $address->getAttribute('location');

        // Collect information on namespace bindings in document
        $namespaces = [];
        $prefixes = [];
        $stack = [$wsdl];
        while (!empty($stack)) {
            $elt = array_pop($stack);
            if ($elt->namespaceURI !== null)
                $namespaces[$elt->namespaceURI] = $elt->namespaceURI;
            foreach (['type', 'itemType', 'base', 'ref'] as $attr) {
                if ($elt->hasAttribute($attr)) {
                    list($prefix) = explode(':', $elt->getAttribute($attr));
                    if ($uri = $elt->lookupNamespaceURI($prefix)) {
                        $prefixes[$prefix] = $prefix;
                        $namespaces[$uri] = $uri;
                    }
                }
            }
            foreach (Xml::childElements($elt) as $k)
                $stack[] = $k;
        }

        // Construct new XML document, setting namespace bindings of the
        // document element to match those in effect at "schema" element in
        // original document
        $schemaDom = new \DOMDocument;
        $copy = $schemaDom->importNode($schemaElt, true);
        foreach ([$wsdl, $types, $schemaElt] as $elt) {
            $copy->setAttribute('xmlns', $elt->lookupNamespaceURI(null));
            foreach ($namespaces as $uri) {
                $prefix = $elt->lookupPrefix($uri);
                if (!$prefix || $copy->hasAttribute("xmlns:$prefix"))
                    continue;
                $a = $schemaDom->createAttribute("xmlns:$prefix");
                $a->nodeValue = $uri;
                $copy->setAttributeNode($a);
            }
            foreach ($prefixes as $prefix) {
                if ($copy->hasAttribute("xmlns:$prefix"))
                    continue;
                $uri = $elt->lookupNamespaceURI($prefix);
                if (!$uri)
                    continue;
                $a = $schemaDom->createAttribute("xmlns:$prefix");
                $a->nodeValue = $uri;
                $copy->setAttributeNode($a);
            }
        }
        $schemaDom->appendChild($copy);
        $schema = $schemaDom->saveXml();

        return [$targetNamespace, $serviceAddress, $schema];
    }

    /**
     * Clears the global cache
     */
    public static function clearCache()
    {
        self::$cache = [];
    }

    /**
     * Maps WSDL paths to triples [$targetNamespace, $address, $schema]
     *
     * @var array
     */
    private static $cache = [];

    /**
     * The WSDL path
     *
     * @var string
     */
    private $path;
}
