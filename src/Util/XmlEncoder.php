<?php

/**
 * Contains the definition of the function CodeRage\Util\XmlEncoder
 *
 * File:        CodeRage/Util/XmlEncoder.php
 * Date:        Thu May  3 15:59:49 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use stdClass;
use CodeRage\Error;
use CodeRage\Util\Array_;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Converts between XML elements and native data structures, i.e., values
 * composed from scalars using indexed arrays and instances of stdClass
 */
class XmlEncoder {

    /**
     * Namespace to which the prefix 'xmlns' is automatically bound
     *
     * @var string
     */
    const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    /**
     * XML Schema namespace URI
     *
     * @var string
     */
    const XML_SCHEMA_NAMESPACE = 'http://www.w3.org/2001/XMLSchema';

    /**
     * XML Schema Instance namespace URI
     *
     * @var string
     */
    const XML_SCHEMA_INSTANCE_NAMESPACE =
        'http://www.w3.org/2001/XMLSchema-instance';

    /**
     * Constructs an instance of CodeRage\Util\XmlEncoder. Can be called two ways:
     * with two arguments 'namespace' and 'listElements', or with an options
     * array
     *
     * @param array $options The options array; supports the followbign options
     *   namespace - A namespace URI
     *   listElements - An associative array mapping local names of XML
     *     elements that represent lists of items to the local names of the
     *     corresponding child elements; the character '*' can be used to
     *     specify a default local name for child elements
     *   xsiNilAttribute - Respect the attributes "nil" in the XML
     *     Schema Instance namespace "http://www.w3.org/2001/XMLSchema-instance"
     *   xsiTypeAttribute - Respect the attributes "type" in the XML
     *     Schema Instance namespace "http://www.w3.org/2001/XMLSchema-instance"
     */
    public function __construct()
    {
        $args = func_get_args();
        $count = sizeof($args);
        $options = $count == 0 ?
            [] :
            ( is_array($args[0]) ?
                  $args[0] :
                  [
                      'namespace' => $args[0],
                      'listElements' => $count > 1 ? $args[1] : null
                  ] );
        $options +=
            [
                'namespace' => null,
                'listElements' => [],
                'xsiNilAttribute' => false,
                'xsiTypeAttribute' => false
            ];
        $this->namespace = $options['namespace'];
        $this->listElements = $options['listElements'];
        $this->xsiNilAttribute = $options['xsiNilAttribute'];
        $this->xsiTypeAttribute = $options['xsiTypeAttribute'];
    }

    /**
     * Returns the namespace URI
     *
     * @return string
     */
    public function _namespace() { return $this->namespace; }

    /**
     * Returns an associative array mapping local names of XML elements that
     * represent lists of items to the local names of the corresponding child
     * elements; the character '*' can be used to specify a default local name
     * for child elements
     *
     * @return string
     */
    public function listElements() { return $this->listElements; }

    /**
     * Returns true if the attribute "nil" in the XML Schema Instance namespace
     * "http://www.w3.org/2001/XMLSchema-instance" should be respected
     *
     * @return boolean
     */
    public function xsiNilAttribute() { return $this->xsiNilAttribute; }

    /**
     * Returns true if the attribute "type" in the XML Schema Instance namespace
     * "http://www.w3.org/2001/XMLSchema-instance" should be respected
     *
     * @return boolean
     */
    public function xsiTypeAttribute() { return $this->xsiTypeAttribute; }

    /**
     * Transforms the given native data structure into an XML string
     *
     * @param string $localName The local name of the element to be returned
     * @param mixed $value A native data structures, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param boolean $xmlDeclaration true to include an XML declaration in the
     *   output; defaults to true
     * @param boolean $namespaceDeclarations true to include namespace
     *   declarations in the output; defaults to true
     * @return string
     */
    public function encodeAsString($localName, $value,
        $xmlDeclaration = true, $namespaceDeclarations = true)
    {
        // Construct document if necessary
        static $eltNames = [];

        // Construct element
        if (!isset($eltNames[$localName])) {
            if (!preg_match('/^[_a-zA-Z][_a-zA-Z0-9]*$/', $localName))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Invalid local name: $localName"
                    ]);
            $eltNames[$localName] = 1;
        }
        $name = $this->namespace !== null ?
            "x:$localName" :
            $localName;
        $result = '';

        // Include xml and namespace declaration to the root element
        if ($xmlDeclaration)
            $result = '<?xml version="1.0" encoding="UTF-8"?>';
        if ($namespaceDeclarations) {
            $result .=
                $this->namespace ?
                    "<$name xmlns:x=\"{$this->namespace}\"" :
                    "<$name";
            if ($this->xsiNilAttribute || $this->xsiTypeAttribute)
                $result .=
                    ' xmlns:xsi="' .
                    self::XML_SCHEMA_INSTANCE_NAMESPACE . '"';
            if ($this->xsiTypeAttribute)
                $result .=
                    ' xmlns:xsd="' .
                    self::XML_SCHEMA_NAMESPACE . '"';
        } else {
            $result .= "<$name";
        }
        if (is_scalar($value)) {
            $text = !is_bool($value) ?
                $value :
                ( $value ?
                      '1' :
                      '0' );
            if (!mb_check_encoding($text, 'UTF-8'))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Invalid text encoding for element $localName: $text"
                    ]);
            $text = htmlspecialchars($text, ENT_COMPAT, "UTF-8");
            $type = !$this->xsiTypeAttribute ?
                null :
                ( $type = is_bool($value) ?
                      'boolean' :
                      ( is_int($value) ?
                            'integer' :
                            ( is_float($value) ?
                                  'double' :
                                  null ) ) );
            if ($type != null) {
                $result .= " xsi:type=\"xsd:$type\">$text</$name>";
            } else {
                $result .= ">$text</$name>";
            }
        } elseif (is_array($value) && !empty($value) && Array_::isIndexed($value)) {
            if ( !isset($this->listElements[$localName]) &&
                 !isset($this->listElements['*']) )
            {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "No local name specified for children of " .
                            "'$localName' elements"
                    ]);
            }
            $itemName = isset($this->listElements[$localName]) ?
                $this->listElements[$localName] :
                $this->listElements['*'];
            $result .= '>';
            foreach ($value as $i => $v)
                $result .= $this->encodeAsString($itemName, $v, false, false);
             $result .= "</$name>";
        } elseif (is_array($value) || $value instanceof stdClass) {
            $result .= '>';
            foreach ($value as $n => $v)
                if (!is_scalar($v) || $v !== null)
                    $result .= $this->encodeAsString($n, $v, false, false);
            $result .= "</$name>";
        } elseif (is_null($value) && $this->xsiNilAttribute) {
            $result .= " xsi:nil=\"1\"/>";
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Expected scalar, array, or instance of ' .
                        'stdClass; found ' . Error::formatValue($value)
                ]);
        }
        return $result;
    }

    /**
     * Transforms the given native data structure into an instance of DOMElement
     *
     * @param string $localName The local name of the element to be returned
     * @param mixed $value A native data structures, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param DOMDocument $dom An instance of DOMDocument used to create new
     *   XML elements; if not provided, one will be constructed and the returned
     *   element will be appended as its document element
     * @return DOMElement
     */
    public function encode($localName, $value, $dom = null)
    {
        // Construct document if necessary
        $result = null;
        $newDom = false;
        if (!$dom) {
            $dom = new \DOMDocument;
            $newDom = true;
        }

        // Construct element
        $name = $this->namespace ?
            "x:$localName" :
            $localName;
        $result = $this->namespace ?
            $dom->createElementNS($this->namespace, $name) :
            $dom->createElement($name);
        if ($newDom) {
            if ($this->xsiNilAttribute) {
                $result->setAttributeNS(
                    self::XMLNS_NAMESPACE,
                    'xmlns:xsi',
                    self::XML_SCHEMA_INSTANCE_NAMESPACE
                );
            }
            if ($this->xsiTypeAttribute) {
                $result->setAttributeNS(
                    self::XMLNS_NAMESPACE,
                    'xmlns:xsd',
                    self::XML_SCHEMA_NAMESPACE
                );
            }
        }
        if (is_scalar($value)) {
            $text = !is_bool($value) ?
                $value :
                ( $value ?
                      '1' :
                      '0' );
            $result->appendChild(
                strpos($text, '</') !== false ?
                    $dom->createCDATASection($text) :
                    $dom->createTextNode($text)
            );
            if ($this->xsiTypeAttribute) {
                $type = is_bool($value) ?
                    'boolean' :
                    ( is_int($value) ?
                          'integer' :
                          ( is_float($value) ?
                                'double' :
                                null ) );
                if ($type !== null) {
                    if ( $result->lookupNamespaceURI('xsd') !=
                             self::XML_SCHEMA_NAMESPACE )
                    {
                        $result->setAttributeNS(
                            self::XMLNS_NAMESPACE,
                            'xmlns:xsd',
                            self::XML_SCHEMA_NAMESPACE
                        );
                    }
                    $result->setAttributeNS(
                        self::XML_SCHEMA_INSTANCE_NAMESPACE,
                        "xsi:type",
                        "xsd:$type"
                    );
                }
            }
        } elseif (is_array($value) && !empty($value) && Array_::isIndexed($value)) {
            if ( !isset($this->listElements[$localName]) &&
                 !isset($this->listElements['*']) )
            {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "No local name specified for children of " .
                            "'$localName' elements"
                    ]);
            }
            $itemName = isset($this->listElements[$localName]) ?
                $this->listElements[$localName] :
                $this->listElements['*'];
            foreach ($value as $i => $v)
                $result->appendChild($this->encode($itemName, $v, $dom));
        } elseif (is_array($value) || $value instanceof stdClass) {
            foreach ($value as $n => $v)
                if (!is_scalar($v) || $v !== null)
                    $result->appendChild($this->encode($n, $v, $dom));
        } elseif (is_null($value) && $this->xsiNilAttribute) {
            $result->setAttributeNS(
                self::XML_SCHEMA_INSTANCE_NAMESPACE,
                'xsi:nil',
                '1'
            );
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Expected scalar, array, or instance of ' .
                        'stdClass; found ' . Error::formatValue($value)
                ]);
        }

        // Return result
        if ($newDom)
            $dom->appendChild($result);
        return $result;
    }

    /**
     * Transforms the given XML element into a native data structure
     *
     * @param DOMElement $elt The element to be decoded
     * @param boolean $checkNamespace true if element namespace URIs should be
     *   checked to ensure they match the underlying namespace; defaults to
     *   false
     * @param boolean $objectsAsArrays true to encode associative structures
     *   as arrays rather than objects
     * @return mixed A native data structures, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     */
    public function decode(
        \DOMElement $elt, $checkNamespace = false, $objectsAsArrays = false)
    {
        // Validate input
        if ($checkNamespace && $elt->namespaceURI !== $this->namespace) {
            $expected = $this->namespace ?
                "namespace URI '$this->namespace'" :
                'no namespace URI';
            $found = $elt->namespaceURI ?
                "namespace URI '$elt->namespaceURI'" :
                'no namespace URI';
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Expected element with $expected; found $found"
                ]);
        }

        // Calculate type
        $type = null;
        if ( $this->xsiTypeAttribute &&
             ($attr = $elt->getAttributeNS(self::XML_SCHEMA_INSTANCE_NAMESPACE, 'type')) )
        {
            $type = null;
            $pos = strpos($attr, ':');
            if ( $pos !== false &&
                 ($prefix = substr($attr, 0, $pos)) &&
                 $elt->lookupNamespaceURI($prefix) ==
                     self::XML_SCHEMA_NAMESPACE )
            {
                $type = substr($attr, $pos + 1);
            } elseif (
                $pos === false &&
                $elt->isDefaultNamespace(self::XML_SCHEMA_NAMESPACE) )
            {
                $type = $attr;
            } else {
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'message' => "Unsupported type: $attr"
                    ]);
            }
        }
        $nil =
            $this->xsiNilAttribute &&
            ($nil = $elt->getAttributeNS(self::XML_SCHEMA_INSTANCE_NAMESPACE, 'nil')) &&
            ($nil == '1' || $nil == 'true');

        // Recursively decode child elements
        $properties = [];
        $isList = isset($this->listElements[$elt->localName]) || $type == 'list';
        foreach (Xml::childElements($elt) as $k) {
            $v = $this->decode($k, $checkNamespace, $objectsAsArrays);
            if ($isList)
                $properties[] = $v;
            else
                $properties[$k->localName] = $v;
        }
        if ($nil && !empty($properties))
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' =>
                        "The element '$elt->localName has children but is " .
                        "marked 'nil'"
                ]);

        // Decode element
        if ($isList) {
            return $properties;
        } elseif (count($properties) != 0) {
            return $objectsAsArrays ?
                $properties :
                (object) $properties;
        } else {
            $text = Xml::textContent($elt);
            if ($type !== null) {
                switch ($type) {
                case 'map':
                    if (strlen($text) > 0)
                        throw new
                            Error([
                                'status' => 'UNEXPECTED_CONTENT',
                                'message' =>
                                    "The element '$elt->localName contains " .
                                    "character data but it marked 'list'"
                            ]);

                    return $objectsAsArrays ? [] : new stdClass;
                case 'boolean':
                    return $text == 'true' || $text == '1';
                case 'integer':
                case 'positiveInteger':
                case 'negativeInteger':
                case 'nonNegativeInteger':
                case 'nonPositiveInteger':
                case 'long':
                case 'unsignedLong':
                case 'int':
                case 'unsignedInt':
                case 'short':
                case 'unsignedShort':
                case 'byte':
                case 'unsignedByte':
                    return (int)$text;
                case 'decimal':
                case 'float':
                case 'double':
                    return (float)$text;
                case 'string':
                case 'normalizedString':
                case 'token':
                case 'base64Binary':
                case 'hexBinary':
                case 'dateTime':
                case 'duration':
                case 'date':
                case 'time':
                case 'gYear':
                case 'gYearMonth':
                case 'gMonth':
                case 'gMonthDay':
                case 'gDay':
                case 'Name':
                case 'QName':
                case 'NCName':
                case 'anyURI':
                case 'language':
                default:
                    return $text;
                }
            } elseif (strlen($text) > 0) {
                return $text;
            } elseif ($nil) {
                return null;
            } else {
                return $objectsAsArrays ? [] : new stdClass;
            }
        }
    }

    /**
     * Corrects the idosyncratic endoing of SOAP requests by the class
     * SoapServer. Specifically, elements representing lists are encoded as
     * instances of stdClass with a single property of type array.
     */
    public function fixSoapEncoding($value)
    {
        $result = null;
        if (is_scalar($value) || $value === null) {
            $result = is_bool($value) ?
                (int) $value :
                $value;
        } elseif (is_array($value)) {
            $result = [];
            foreach ($value as $i => $v)
                $result[] = $this->fixSoapEncoding($v);
        } elseif ($value instanceof stdClass) {
            $result = new stdClass;
            foreach ($value as $n => $v) {
                if (isset($this->listElements[$n])) {

                    // This is the crux: instead of a list, $v is an instance
                    // of stdClass that is empty -- representing an
                    // empty list -- or that has a single list-valued property
                    if (!$v instanceof stdClass)
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' =>
                                    "Expected value of property '$n' to be " .
                                    "an instance of sdtClass with one list-" .
                                    "valued property: found " .
                                    Error::formatValue($v)
                            ]);
                    $vars = get_object_vars($v);
                    if (empty($vars)) {
                        $result->$n = [];
                    } elseif (count($vars) == 1) {
                        list($list) = array_values($vars);
                        if (!is_array($list))
                            throw new
                                Error([
                                    'status' => 'INVALID_PARAMETER',
                                    'details' =>
                                        "Expected list; found " .
                                        Error::formatValue($list)
                                ]);
                        $result->$n = $this->fixSoapEncoding($list);
                    } else {
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'details' =>
                                    "Expected value of property '$n' to " .
                                    "have a single property: found " .
                                    sizeof($vars)
                            ]);
                    }
                } else {
                    $result->$n = $this->fixSoapEncoding($v);
                }
            }
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Expected native data structure; found ' .
                        get_class($value)
                ]);
        }
        return $result;
    }

    /**
     * Creates an element with a namespace prefix definition for the XML Schema
     * namespace
     *
     * @param DOMDocument $dom The instance of DOMDocument used to create the
     *   element
     * @param string $localName The local name, unqualified in $namespace is
     *   null and otherwise possible namespace-qualified
     * @param string $namespace The namespace URI, if any
     */
    public static function createElement($dom, $name, $namespace = null)
    {
        $elt = $namespace !== null ?
            $dom->createElementNS($namespace, $name) :
            $dom->createElement($name);
        $elt->setAttributeNS(
            self::XMLNS_NAMESPACE,
            'xmlns:xsd',
            self::XML_SCHEMA_NAMESPACE
        );
        return $elt;
    }

    /**
     * Implements namespace()
     */
    public function __call($method, $arguments)
    {
        if ($method == 'namespace') {
            return call_user_func_array([$this, '_namespace'], $arguments);
        } else {
            throw new
                Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'details' => "No such method: $method"
                ]);
        }
    }

    /**
     * The namespace URI
     *
     * @var string
     */
    private $namespace;

    /**
     * An an associative array mapping local names of XML elements that
     * represent lists of items to the local names of the corresponding child
     * elements; the character '*' can be used to specify a default local name
     * for child elements
     *
     * @var string
     */
    private $listElements;

    /**
     * true if the attribute "nil" in the XML Schema Instance namespace
     * "http://www.w3.org/2001/XMLSchema-instance" should be respected
     *
     * @var boolean
     */
    private $xsiNilAttribute;

    /**
     * true if the attribute "type" in the XML Schema Instance namespace
     * "http://www.w3.org/2001/XMLSchema-instance" should be respected
     *
     * @var boolean
     */
    private $xsiTypeAttribute;
}
