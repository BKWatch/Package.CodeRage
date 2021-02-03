<?php

/**
 * Defines the class CodeRage\Xml
 *
 * File:        CodeRage/Xml.php
 * Date:        Thu Sep 17 02:04:09 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

use DOMDocument;
use DOMElement;
use CodeRage\Error;
use CodeRage\File;


/**
 * Container for static methods for manipulating XML
 */
final class Xml {

    /**
     * Returns the given element's as an associative array, indexed by name
     *
     * @param DOMElement $elt The element
     * @param string $namespace An optional namespace URI
     * @param boolean $caseSensitive true to preserve case of attributes names;
     *   if false, attribute names are converted to lowercase
     * @return array
     */
    public static function attributes(\DOMElement $elt, ?string $namespace = null,
        bool $caseSensitive = true) : array
    {
        $result = [];
        $attributes = $elt->attributes;
        for ($z = 0, $n = $attributes->length; $z < $n; ++$z) {
            $attr = $attributes->item($z);
            if ($namespace === null || $attr->namespaceURI === $namespace) {
                $name = $caseSensitive ?
                    $attr->nodeName :
                    strtolower($attr->nodeName);
                $result[$name] = $attr;

            }
        }
        return $result;
    }

    /**
     * Returns the text value of the first child of the given element having the
     * given local name
     *
     * @param DOMElement $elt
     * @param string $localName
     * @return string
     */
    public static function childContent(DOMElement $elt, string $localName) :
        ?string
    {
        return ($kid = self::firstChildElement($elt, $localName)) ?
            self::textContent($kid) :
            '';
    }

    /**
     * Returns the list of the given element's child elements that meet the
     * specified criteria
     *
     * @param DOMElement $elt The element
     * @param string $localName The localName of the child elements to be
     *   returned, or null if any local name is admissible
     * @param string $namespace The namespace URI of the child elements to be
     *   returned, or null if any namespace, or the lack of a namespace, is
     *   admissible
     * @return array
     */
    public static function childElements(DOMElement $elt, ?string $localName = null,
        ?string $namespace = null) : array
    {
        $result = [];
        $kids = $elt->childNodes;
        for ($z = 0, $n = $kids->length; $z < $n; ++$z) {
            $k = $kids->item($z);
            if ( $k->nodeType == XML_ELEMENT_NODE &&
                 ($localName === null || $k->localName == $localName) &&
                 ($namespace === null || $k->namespaceURI === $namespace) )
            {
                $result[] = $k;
            }
        }
        return $result;
    }

    /**
     * Returns the file pathname of the document from which the given
     * DOMDocument was parsed, if the documentURI property is set and has scheme
     * 'file', and null otherwise.
     *
     * @param string $path
     * @return DOMDocument
     */
    public static function documentPath(DOMDocument $dom) : ?string
    {
        return ($uri = $dom->documentURI) ?
            urldecode(parse_url($uri, PHP_URL_PATH)) :
            null;
    }


    /**
     * Returns the first child element of the given element meeting the
     * specified criteria, if any.
     *
     * @param DOMElement $elt The element
     * @param string $localName The localName of the child elements to be
     *   returned, or null if any local name is admissible.
     * @param string $namespace The namespace URI of the child elements to be
     *   returned, or null if any namespace, or the lack of a namespace, is
     *   admissible
     * @return DOMElement
     */
    public static function firstChildElement(
        \DOMElement $elt, ?string $localName = null, $namespace = null) : ?DOMElement
    {
        $kids = $elt->childNodes;
        for ($z = 0, $n = $kids->length; $z < $n; ++$z) {
            $k = $kids->item($z);
            if ( $k->nodeType == XML_ELEMENT_NODE &&
                 ($localName === null || $k->localName == $localName) &&
                 ($namespace === null || $k->namespaceURI === $namespace) )
            {
                return $k;
            }
        }
        return null;
    }

    /**
     * Returns the value of the named attribute of the given element, or the
     * given default value if no such attribute exists.
     *
     * @param DOMElement $elt The element
     * @param string $name The attribute name
     * @param string $default The default value; defaults to null.
     * @return string
     */
    public static function getAttribute(\DOMElement $elt, string $name,
        ?string $default = null) : ?string
    {
        return $elt->hasAttribute($name) ?
            $elt->getAttribute($name) :
            $default;
    }

    /**
     * Returns the value of the named attribute of the given element,
     * interpretted as a boolean value, or the given default value if no such
     * attribute exists.
     *
     * @param DOMElement $elt The element
     * @param string $name The attribute name
     * @param boolean $default The default value
     * @return boolean
     */
    public static function getBooleanAttribute(\DOMElement $elt, string $name,
        ?string $default = null)
    {
        if ($elt->hasAttribute($name)) {
            $value = $elt->getAttribute($name);
            return $value == 'true' || $value == '1';
        } elseif ($default === null) {
            throw new Error(['details' => "Missing boolean attribute '$name'"]);
        } else {
            return $default;
        }
    }



    /**
     * Parses the given file and returns an instance of DOMDocument
     *
     * @param string $file A file pathname
     * @param string $schema The file pathname of an XML schema document
     * @param array $options The options array; supports the following options:
     *     html - true to treat the document as HTML
     *     errorLevel - The error level to pass to set_error_handler()
     *     preserveWhitespace - true to preserv whitespace
     * @throws CodeRage\Error if the file or schema does not exist, if the file
     *   is not well-formed XML, or if a schema is supplied and the document
     *   fails to validate
     */
    public static function loadDocument(string $file, ?string $schema = null,
        array $options = []) : DOMDocument
    {
        return self::loadDocumentImpl($file, 'location', $schema, $options);
    }

    /**
     * Parses the given string and returns an instance of DOMDocument
     *
     * @param string $xml A string consisting of XML
     * @param string $schema The file pathname of an XML schema document
     * @param array $options The options array; supports the following options:
     *     html - true to treat the document as HTML
     *     errorLevel - The error level to pass to set_error_handler()
     *     preserveWhitespace - true to preserv whitespace
     * @return DOMDocument
     * @throws CodeRage\Error if the schema does not exist, if the string is not
     *   well-formed XML, or if a schema is supplied and the document fails to
     *   validate.
     */
    public static function loadDocumentXml(string $xml, ?string $schema = null,
        array $options = []) : DOMDocument
    {
        return self::loadDocumentImpl($xml, 'string', $schema, $options);
    }

    /**
     * Returns the result of concatenating the values of the text node and CDATA
     * section children of the given element.
     *
     * @param DOMElement $elt The element
     * @return string
     * @throws Exception if a child element is encountered
     */
    public static function textContent(DOMElement $elt, bool $nothrow = false)
    {
        $result = '';
        $kids = $elt->childNodes;
        for ($z = 0, $n = $kids->length; $z < $n; ++$z) {
            $k = $kids->item($z);
            switch ($k->nodeType) {
            case XML_TEXT_NODE:
            case XML_CDATA_SECTION_NODE:
                $result .= $k->nodeValue;
                break;
            case XML_ELEMENT_NODE:
                if ($nothrow)
                    return null;
                $outer =
                    ($elt->namespaceURI ? "$elt->namespaceURI." : '') .
                    $elt->localName;
                $inner =
                    ($k->namespaceURI ? "$k->namespaceURI." : '') .
                    $k->localName;
                throw new
                    \Exception(
                        "Element '$outer' contains invalid content: expected " .
                        "text; found element '$inner'"
                    );
            default:
                break;
            }
        }
        return $result;
    }

    /**
     * Parses the given file or string and returns an instance of DOMDocument
     *
     * @param string $value A file pathname or string
     * @param string $type One of the values 'location' or 'string'
     * @param string $schema The file pathname of an XML schema document
     * @param array $options The options array; supports the following options:
     *     html - true to treat the document as HTML
     *     errorLevel - The error level to pass to set_error_handler(),
     *     preserveWhitespace - true to preserv whitespace
     * @return DOMDocument
     * @throws CodeRage\Error if the file or schema does not exist, if the file
     *   is not well-formed XML, or if a schema is supplied and the document
     *   fails to validate
     */
    private static function loadDocumentImpl(string $value, string $type,
        ?string $schema = null, array $options)
    {
        $html = isset($options['html']) && $options['html'];
        $errorLevel = $options['errorLevel'] ?? null;
        if ($type == 'location')
            File::checkReadable($value, null, null, true);
        $doc = new DOMDocument;
        if (!($options['preserveWhitespace'] ?? true))
            $doc->preserveWhiteSpace = false;
        $handler = new \CodeRage\Util\ErrorHandler($errorLevel);
        $method = $type == 'location' ?
            ($html ? '_loadHTMLFile' : '_load') :
            ($html ? '_loadHTML' : '_loadXml');
        $success = $handler->$method($doc, $value);
        if ($handler->errno()) {
            $content = $type == 'location' ?
                file_get_contents($value) :
                $value;
            $teaser = substr($content, 0, 1000) . " ...";
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' =>
                        $handler->formatError(
                            "Failed parsing " .
                            ($type == 'location' ? "'$value'" : "XML") .
                            " ($teaser)"
                        )
                ]);
        }
        if (!$success)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' =>
                        "Failed parsing " .
                        ($type == 'location' ? "'$value'" : "XML")
                ]);
        if ($schema) {
            File::checkReadable($schema);
            $success = $handler->_schemaValidate($doc, $schema);
            if ($handler->errno())
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' =>
                            $handler->formatError(
                                "Failed validating " .
                                ($type == 'location' ? "'$value'" : "XML") .
                                " against schema '$schema'"
                            )
                    ]);
            if (!$success)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' =>
                            "Failed validating " .
                            ($type == 'location' ? "'$value'" : "XML") .
                            " against schema '$schema'"
                    ]);
        }
        return $doc;
    }
}
