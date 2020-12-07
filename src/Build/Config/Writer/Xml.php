<?php

/**
 * Defines the class CodeRage\Build\Config\Writer\Xml.
 *
 * File:        CodeRage/Build/Config/Writer/Xml.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Writer;

use CodeRage\Build\Config\Property;
use CodeRage\File;

/**
 * Generates an XML configuration file conforming to the schema "project.xsd"
 * and having document element "config."
 */
class Xml implements \CodeRage\Build\Config\Writer {

    /**
     * Writes the given property bundle to the specified file, as an XML
     * document conforming to the schema "project.xsd" and having document
     * element "config."
     *
     * @param CodeRage\Build\ProjectConfig $properties
     * @param string $path
     * @throws Exception
     */
    function write(\CodeRage\Build\ProjectConfig $properties, $path)
    {
        $content =
            "<config xmlns=\"" . \CodeRage\Build\NAMESPACE_URI . "\">\n";
        foreach ($properties->propertyNames() as $n) {
            $p = $properties->lookupProperty($n);
            $content .= self::writeProperty($p);
        }
        $content .= "</config>\n";
        File::generate($path, $content, 'xml');
    }

    /**
     * Returns a "property" element.
     *
     * @param CodeRage\Build\Config\Property $property
     */
    private static function writeProperty(Property $property)
    {
        $name = $property->name();
        $result = "<property name=\"$name\"";
        if ($type = $property->type())
            $result .=
                ' type="' .
                Property::translateType($type) . '"';
        if ($property->isList())
            $result .= ' list="true"';
        if ($property->required())
            $result .= ' required="true"';
        if ($property->sticky())
            $result .= ' sticky="true"';
        if ($property->obfuscate())
            $result .= ' obfuscate="true"';
        if ($specifiedAt = $property->specifiedAt())
            $result .=
                ' specifiedAt="' .
                htmlspecialchars(
                    Property::translateLocation($specifiedAt)
                ) .
                '"';
        if ($setAt = $property->setAt())
            $result .=
               ' setAt="' .
               htmlspecialchars(
                    Property::translateLocation($setAt)
               ) .
               '"';
        if ($property->isSet())
            $result .= self::writeValue($property->value());
        $result .= '/>';
        return $result;
    }

    /**
     * Outputs a 'value' attribute and possibly an 'encoding' attribute and/or
     * a 'separator' attribute.
     *
     * @param mixed $value
     */
    private static function writeValue($value)
    {
        if (is_array($value)) {

            // Convert values to strings
            $value =
                array_map(
                    function($v)
                    {
                        return is_bool($v) ? ($v ? '1' : '0') : (string) $v;
                    },
                    $value
                );
            if (!self::matchValues('/[^[:print:]]/', $value)) {

                // Look for an acceptable separator character to use, in this
                // order: " ,;:|/\-"
                if (!self::matchValues('/\s/', $value))
                    return
                        ' value="' . htmlspecialchars(join(' ', $value)) . '"';
                foreach ([',', ';', ':', '|', '/', '\\', '-'] as $sep) {
                    if (!self::matchValues("#$sep#", $value))
                        return
                            ' value="' . htmlspecialchars(join($sep, $value)) .
                            "\" separator=\"$sep\"";
                }
            }
            return ' value="' . join(' ', array_map('base64_encode', $value)) .
                   '" encoding="base64"';
        } else {
            $value = is_bool($value) ?
                ($value ? '1' : '0') :
                strval($value);
            return ctype_print($value) ?
                ' value="' . htmlspecialchars($value, ENT_QUOTES) . '"':
                ' value="' . base64_encode($value) . '" encoding="base64"';
        }
    }

    /**
     * Returns true if a string in the give array contains a character matched
     * by the given regular expression.
     *
     * @param string $pattern
     * @param array $values
     * @return boolean
     */
    private static function matchValues($pattern, $values)
    {
        foreach ($values as $v)
            if (preg_match($pattern, $v))
                return true;
        return false;
    }
}
