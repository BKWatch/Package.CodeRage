<?php

/**
 * Defines the class CodeRage\Build\Info
 *
 * File:        CodeRage/Build/Info.php
 * Date:        Thu Dec 25 14:46:28 MST 2008
 * Notice:      This document contains confidential information and
 *              trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Xml;

/**
 * Represents information about a project or target.
 */
class Info {

    const PROPERTIES =
        'label description version date copyright license author link';

    /**
     * Associative array whose keys are the identifiers specified by PROPERTIES.
     *
     * @var array
     */
    private static $keys;

    /**
     * A short descriptive name
     *
     * @var string
     */
    private $label;

    /**
     * A detailed description
     *
     * @var string
     */
    private $description;

    /**
     * A version string, in any format
     *
     * @var string
     */
    private $version;

    /**
     * A human readable date
     *
     * @var string
     */
    private $date;

    /**
     * The copyright information for this CodeRage\Build\Info
     *
     * @var string
     */
    private $copyright;

    /**
     * The copyright information for this CodeRage\Build\Info
     *
     * @var string
     */
    private $license;

    /**
     * A semicolon-separated list of author name
     *
     * @var string
     */
    private $author;

    /**
     * The location of additional information
     *
     * @var string
     */
    private $link;

    /**
     * Constructs a CodeRage\Build\Info.
     *
     * @param array $properties An associtive array with zero or more of the
     * following keys:
     * <ul>
     * <li>label - A short descriptive name</li>
     * <li>description - A detailed description</li>
     * <li>version - A version string, in any format</li>
     * <li>date - A UNIX timestamp</li>
     * <li>copyright - The copyright information for the CodeRage\Build\Info under
     * construction</li>
     * <li>license - The copyright information for the CodeRage\Build\Info under
     * construction</li>
     * <li>author - A semicolon-separated list of author name</li>
     * <li>link - The location of additional information</li>
     * </ul>
     */
    function __construct($properties = [])
    {
        if (!self::$keys) {
            self::$keys = [];
            foreach (Text::split(self::PROPERTIES) as $p)
                self::$keys[$p] = 1;
        }
        foreach ($properties as $n => $v) {
            if (isset(self::$keys[$n])) {
                if (!is_string($v))
                    throw new
                        Error(['message' =>
                            "Invalid value of property '$n': expected " .
                            "string; found" . Error::formatValue($v)
                        ]);
                $this->$n = $v;
            } else {
                throw new Error(['message' => "Invalid property name: $n"]);
            }
        }
    }

    /**
     * Returns a short descriptive name
     *
     * @return string
     */
    function label()
    {
        return $this->label;
    }

    /**
     * Returns a detailed description
     *
     * @return string
     */
    function description()
    {
        return $this->description;
    }

    /**
     * Returns a version string, in any format
     *
     * @return string
     */
    function version()
    {
        return $this->version;
    }

    /**
     * Returns a human readable date
     *
     * @return string
     */
    function date()
    {
        return $this->date;
    }

    /**
     * Returns the copyright information for this CodeRage\Build\Info
     *
     * @return string
     */
    function copyright()
    {
        return $this->copyright;
    }

    /**
     * Returns the copyright information for this CodeRage\Build\Info
     *
     * @return string
     */
    function license()
    {
        return $this->license;
    }

    /**
     * Returns a semicolon-separated list of author name
     *
     * @return string
     */
    function author()
    {
        return $this->author;
    }

    /**
     * Returns the location of additional information
     *
     * @return string
     */
    function link()
    {
        return $this->link;
    }

    /**
     * Returns a newly constructed instance of CodeRage\Build\Info, supplying
     * default values suitable for built-n components.
     *
     */
    static function create($properties = [])
    {
        if ( !isset($properties['description']) &&
             isset($properties['label']) )
        {
            $properties['description'] = $properties['label'];
        }
        if (!isset($properties['author']))
            $properties['author'] = 'CodeRage';
        if (!isset($properties['copyright']))
            $properties['copyright'] = date('Y') . ' CodeRage';
        if (!isset($properties['license']))
            $properties['license'] = 'All rights reserved';
        return new Info($properties);
    }

    /**
     * Constructs and returns an instance of CodeRage\Build\Info from the given
     * 'info' element in an XML document conforming to the schema 'project.xsd'.
     *
     * @param DOMElement $info
     * @return CodeRage\Build\Info
     */
    static function fromXml(\DOMElement $info)
    {
        $properties = [];
        foreach (Xml::childElements($info) as $k)
            $properties[$k->localName] = $k->nodeValue;
        return new Info($properties);
    }

}
