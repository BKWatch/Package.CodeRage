<?php

/**
 * Defines the class CodeRage\Build\Config\Writer\Php.
 *
 * File:        CodeRage/Build/Config/Writer/Php.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config\Writer;

use CodeRage\Error;
use CodeRage\File;

/**
 * Generates a PHP configuration file of the format expected by the class
 * CodeRage\Config.
 */
class Php implements \CodeRage\Build\Config\Writer {

    /**
     * Writes the given property bundle to the specified file.
     *
     * @param CodeRage\Build\ExtendedConfig $properties
     * @param string $path
     * @throws Exception
     */
    function write(\CodeRage\Build\ExtendedConfig $properties, $path)
    {
        $items = [];
        foreach ($properties->propertyNames() as $n) {
            $p = $properties->lookupProperty($n);
            $items[] = "'$n' => " . $this->printLiteral($p->value());
        }
        asort($items);
        $content = "return [\n    " . join(",\n    ", $items) . "\n];\n";
        File::generate($path, $content, 'php');
    }

    /**
     * Returns a PHP expression evaluating to the given value
     *
     * @param mixed $value A scalar or indexed array of scalars.
     */
    private static function printLiteral($value)
    {
        switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
        case 'integer':
        case 'double':
            return strval($value);
        case 'string':
            return strlen($value) == 0 || ctype_print($value) ?
                "'" . addcslashes($value, "\\'") . "'" :
                "base64_decode('" . base64_encode($value) . "')";
        case 'array':
            $literals = [];
            foreach ($value as $v)
                $literals[] = self::printLiteral($v);
            return '[' . join(',', $literals) . ']';
        default:
            throw new
                \Exception(
                    "Invalid property value: " . Error::formatValue($value)
                );
        }
    }
}
