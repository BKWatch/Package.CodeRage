<?php

/**
 * Defines the class CodeRage\Test\Assert
 * 
 * File:        CodeRage/Test/Assert.php
 * Date:        Mon May 21 13:34:26 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use DOMElement;
use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Defines static methods for comparing values
 */
class Assert {

    /**
     * Throws an exception if the given boolean is false
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isTrue($value, $message = null)
    {
        if (!$value) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' => "{$prefix}The specified value is false"
                ]);
        }
    }

    /**
     * Throws an exception if the given boolean is true
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isFalse($value, $message = null)
    {
        if ($value) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' => "{$prefix}The specified value is true"
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is boolean
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isBool($value, $message = null)
    {
        if (!is_bool($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected boolean; found " .
                        Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is an int
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isInt($value, $message = null)
    {
        if (!is_int($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected integer; found " .
                        Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is a float
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isFloat($value, $message = null)
    {
        if (!is_float($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected floating point value; found " .
                        Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is numeric
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isNumeric($value, $message = null)
    {
        if (!is_numeric($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected numeric value; found " .
                        Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is a string
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isString($value, $message = null)
    {
        if (!is_string($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected string; found " . Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is an array
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isArray($value, $message = null)
    {
        if (!is_array($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected array; found " . Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is an object
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isObject($value, $message = null)
    {
        if (!is_object($value)) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected object; found " . Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception unless the given value is null
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isNull($value, $message = null)
    {
        if ($value !== null) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected null value; found " .
                        Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception if the given value is null
     *
     * @param boolean $value The value to be tested
     * @param string $message The error message, if any
     */
    public static function isNonNull($value, $message = null)
    {
        if ($value === null) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected non-null value; found " .
                        Error::formatValue($value)
                ]);
        }
    }

    /**
     * Throws an exception if the two native data structures are not equivalent
     *
     * @param mixed $actual A native data structure, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param mixed $expected A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param string $message The error message, if any
     */
    public static function equal($actual, $expected, $message = null)
    {
        try {
            $traversal = new BiTraversal($actual, $expected);
            $traversal->traverse(new AssertVisitor);
        } catch (Error $e) {
            if ($e->status() == 'ASSERTION_FAILED') {
                $msg = $e->details();
                if ($message !== null)
                    $msg = $message . ': ' . lcfirst($msg);
                $diff =
                    (new Diff($expected, $actual))->format([
                        'throwOnError' => false
                    ]);
                if ($diff !== null)
                    $msg .= " (diff:\n$diff)";
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => $msg
                    ]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Synonym of equal()
     *
     * @param mixed $actual A native data structure, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param mixed $expected A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param string $message The error message, if any
     */
    public static function equivalentData($actual, $expected, $message = null)
    {
        self::equal($actual, $expected, $message);
    }

    /**
     * Throws an exception if the two native data structures are equivalent
     *
     * @param mixed $actual A native data structure, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param mixed $expected A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param string $message The error message, if any
     */
    public static function notEqual($actual, $expected, $message = null)
    {
        try {
            self::equal($actual, $expected);
        } catch (Throwable $e) {
            return;
        }
        $prefix = $message !== null ? "$message: " : '';
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    "{$prefix}The specified native data structures are " .
                    "equivalent"
            ]);
    }

    /**
     * Throws an exception if either of the two values is not numeric or if the
     * they differ by more than the given amount
     *
     * @param number $actual The actual value
     * @param mixed $expected A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param string $message The error message, if any
     */
    public static function almostEqual($actual, $expected, $epsilon, $message = null)
    {
        Assert::isNumeric($actual, 'Incorrect actual value');
        Assert::isNumeric($expected, 'Incorrect expected value');
        Assert::isNumeric($epsilon, 'Incorrect epsilon');
        if (abs($actual - $expected) > $epsilon) {
            $prefix = $message !== null ? "$message: " : '';
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}Expected a value close to $expected; found " .
                        $actual
                ]);
        }
    }

    /**
     * Synonym of notEqual()
     *
     * @param mixed $actual A native data structure, i.e., a value composed from
     *   scalars using indexed arrays and instances of stdClass
     * @param mixed $expected A native data structure, i.e., a value composed
     *   from scalars using indexed arrays and instances of stdClass
     * @param string $message The error message, if any
     */
    public static function inequivalentData($actual, $expected, $message = null)
    {
        self::notEqual($actual, $expected, $message);
    }

    /**
     * Throws an exception if the two strings do not contain equivalent XML
     * elements, ignoring comments, processing instructions, and the text node
     * children of elements with child elements
     *
     * @param string $actual
     * @param string $expected
     * @param string $message The error message, if any
     */
    public static function equivalentXmlData($actual, $expected, $message = null)
    {
        $lDom = Xml::loadDocumentXml($actual);
        $rDom = Xml::loadDocumentXml($expected);
        self::equivalentXmlElements(
            $lDom->documentElement,
            $rDom->documentElement,
            false,
            $message
        );
    }

    /**
     * Throws an exception if the two strings do not contain equivalent HTML
     * elements, ignoring comments, processing instructions, and the text node
     * children of elements with child elements
     *
     * @param string $actual
     * @param string $expected
     * @param string $message The error message, if any
     */
    public static function equivalentHtmlData($actual, $expected, $message = null)
    {
        $lDom = Xml::loadDocumentXml($actual, null, ['html' => true]);
        $rDom = Xml::loadDocumentXml($expected, null, ['html' => true]);
        self::equivalentXmlElements(
            $lDom->documentElement,
            $rDom->documentElement,
            false,
            $message
        );
    }

    /**
     * Throws an exception if the two XML elements are not equivalent, ignoring
     * comments, processing instructions, and the text node children of elements
     * with child elements
     *
     * @param DOMElement $actual
     * @param DOMElement $expected
     * @param string $message the error message, if any
     */
    public static function
        equivalentXmlElements(
            DOMElement $actual,
            DOMElement $expected,
            $normalize = true,
            $message = null
        )
    {
        if ($normalize) {
            $actual = $actual->cloneNode(true);
            $expected = $expected->cloneNode(true);
            $actual->normalize();
            $expected->normalize();
            $normalize = false;
        }
        $prefix = $message !== null ? "$message: " : '';

        // Compare local names and namespace URIs
        if ( $actual->localName != $expected->localName ||
             $actual->namespaceURI !== $expected->namespaceURI )
        {
            $lDesc = self::nodeDescription($actual);
            $rDesc = self::nodeDescription($expected);
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' => "{$prefix}Expected $lDesc; found $rDesc"
                ]);
        }

        // Compare attribute collections
        $lAttributes = $actual->attributes;
        $rAttributes = $expected->attributes;
        $n = $lAttributes->length;
        for ($i = 0, $n = $lAttributes->length; $i < $n; ++$i) {
            $lAtt = $lAttributes->item($i);
            $name = $lAtt->localName;
            $uri = $lAtt->namespaceURI;
            $rAtt = $uri ?
                $rAttributes->getNamedItemNS($uri, $name) :
                $rAttributes->getNamedItem($name);
            if (!$rAtt) {
                $eltDesc = self::nodeDescription($actual);
                $attDesc = self::nodeDescription($lAtt);
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "{$prefix}Unexpected $attDesc on $eltDesc"
                    ]);
            }
            if ($lAtt->nodeValue != $rAtt->nodeValue) {
                $eltDesc = self::nodeDescription($actual);
                $attDesc = self::nodeDescription($lAtt);
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "{$prefix}Incorrect value for $attDesc on " .
                            "$eltDesc: expected '$rAtt->nodeValue'; " .
                            "found '$lAtt->nodeValue'"
                    ]);
            }
        }
        for ($i = 0, $n = $rAttributes->length; $i < $n; ++$i) {
            $rAtt = $rAttributes->item($i);
            $name = $rAtt->localName;
            $uri = $rAtt->namespaceURI;
            $lAtt = $uri ?
                $lAttributes->getNamedItemNS($uri, $name) :
                $lAttributes->getNamedItem($name);
            if (!$lAtt) {
                $eltDesc = self::nodeDescription($actual);
                $attDesc = self::nodeDescription($rAtt);
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "{$prefix}Missing $attDesc on element $eltDesc"
                    ]);
            }
        }

        // Compare collections of child elements
        $lKids = [];
        $rKids = [];
        for ($i = 0, $n = $actual->childNodes->length; $i < $n; ++$i) {
            $k = $actual->childNodes->item($i);
            if ( $k->nodeType == XML_ELEMENT_NODE ||
                 $k->nodeType == XML_TEXT_NODE ||
                 $k->nodeType == XML_CDATA_SECTION_NODE )
            {
                $lKids[] = $k;
            }
        }
        for ($i = 0, $n = $expected->childNodes->length; $i < $n; ++$i) {
            $k = $expected->childNodes->item($i);
            if ( $k->nodeType == XML_ELEMENT_NODE ||
                 $k->nodeType == XML_TEXT_NODE ||
                 $k->nodeType == XML_CDATA_SECTION_NODE )
            {
                $rKids[] = $k;
            }
        }
        $lSize = sizeof($lKids);
        $rSize = sizeof($rKids);
        if ($lSize != $rSize) {
            $eltDesc = ucfirst(self::nodeDescription($actual));
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "{$prefix}$eltDesc has incorrect number of child " .
                        "element and text nodes: expected $rSize; found " .
                        $lSize
                ]);
        }
        for ($i = 0; $i < $lSize; ++$i) {
            $lNode = $lKids[$i];
            $rNode = $rKids[$i];
            if ($lNode->nodeType != $rNode->nodeType) {
                $eltDesc = self::nodeDescription($actual);
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "{$prefix}Incorrect type of child node of " .
                            "$eltDesc at position $i: expected " .
                            self::nodeType($rNode->nodeType) . "; found " .
                            self::nodeType($lNode->nodeType)
                    ]);
            }
            switch ($lNode->nodeType) {
            case XML_TEXT_NODE:
                if ($lNode->nodeValue != $rNode->nodeValue) {
                    throw new
                        Error([
                            'status' => 'ASSERTION_FAILED',
                            'message' =>
                                "{$prefix} Incorrect node text: expected " .
                                "'$rNode->nodeValue'; found " .
                                "'$lNode->nodeValue'"
                        ]);
                }
                break;
            case XML_CDATA_SECTION_NODE:
                if ($lNode->nodeValue != $rNode->nodeValue) {
                    throw new
                        Error([
                            'status' => 'ASSERTION_FAILED',
                            'message' =>
                                "{$prefix}Incorrect CDATA section text: " .
                                "expected '$rNode->nodeValue'; found " .
                                "'$lNode->nodeValue'"
                        ]);
                }
                break;
            case XML_ELEMENT_NODE:
                self::equivalentXmlElements(
                    $lNode,
                    $rNode,
                    $normalize,
                    $message
                );
                break;
            default:
                break;
            }
        }
    }

    /**
     * Throws an exception if the two strings contain equivalent XML elements,
     * ignoring comments, processing instructions, and the text node children of
     * elements with child elements
     *
     * @param string $actual
     * @param string $expected
     * @param string $message The error message, if any
     */
    public static function inequivalentXmlData(
        $actual, $expected, $message = null)
    {
        try {
            self::equivalentXmlData($actual, $expected);
        } catch (Throwable $e) {
            return;
        }
        $prefix = $message !== null ? "$message: " : '';
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    "{$prefix}The specified XML elements are equivalent"
            ]);
    }

    /**
     * Throws an exception if the two strings contain equivalent HTML elements,
     * ignoring comments, processing instructions, and the text node children of
     * elements with child elements
     *
     * @param string $actual
     * @param string $expected
     * @param string $message The error message, if any
     */
    public static function inequivalentHtmlData(
        $actual, $expected, $message = null)
    {
        try {
            self::equivalentHtmlData($actual, $expected);
        } catch (Throwable $e) {
            return;
        }
        $prefix = $message !== null ? "$message: " : '';
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    "{$prefix}The specified XML elements are equivalent"
            ]);
    }

    /**
     * Throws an exception if the two XML elements are equivalent, ignoring
     * comments, processing instructions, and the text node children of elements
     * with child elements
     *
     * @param DOMElement $actual
     * @param DOMElement $expected
     * @param string $message The error message, if any
     */
    public static function inequivalentXmlElements(
        DOMElement $actual, DOMElement $expected, $message = null)
    {
        try {
            self::equivalentXmlElements($actual, $expected, $message);
        } catch (Throwable $e) {
            return;
        }
        $prefix = $message !== null ? "$message: " : '';
        throw new
            Error([
                'status' => 'ASSERTION_FAILED',
                'message' =>
                    "{$prefix}The specified XML elements are equivalent"
            ]);
    }

    /**
     * Returns the specified node type as a string
     *
     * @param int $type
     * @return string
     */
    private static function nodeType($type)
    {
        switch ($type) {
        case 1:
            return 'XML_ELEMENT_NODE';
        case 2:
            return 'XML_ATTRIBUTE_NODE';
        case 3:
            return 'XML_TEXT_NODE';
        case 4:
            return 'XML_CDATA_SECTION_NODE';
        case 5:
            return 'XML_ENTITY_REF_NODE';
        case 6:
            return 'XML_ENTITY_NODE';
        case 7:
            return 'XML_PI_NODE';
        case 8:
            return 'XML_COMMENT_NODE';
        case 9:
            return 'XML_DOCUMENT_NODE';
        case 10:
            return 'XML_DOCUMENT_TYPE_NODE';
        case 11:
            return 'XML_DOCUMENT_FRAG_NODE';
        case 12:
            return 'XML_NOTATION_NODE';
        case 13:
            return 'XML_HTML_DOCUMENT_NODE';
        case 14:
            return 'XML_DTD_NODE';
        case 15:
            return 'XML_ELEMENT_DECL_NODE';
        case 16:
            return 'XML_ATTRIBUTE_DECL_NODE';
        case 17:
            return 'XML_ENTITY_DECL_NODE';
        case 18:
            return 'XML_NAMESPACE_DECL_NODE';
        default:
            return null;
        }
    }

    /**
     * Returns a descriptive label for the given element or attribute node
     *
     * @param XMLNode $node
     * @return string
     */
    private static function nodeDescription($node)
    {
        return
            ($node->nodeType == XML_ELEMENT_NODE ? 'element' : 'attribute') .
            " '$node->localName'" .
            ( $node->namespaceURI ?
                  " in namespace URI '$node->namespaceURI'" :
                  "" );
    }
}
