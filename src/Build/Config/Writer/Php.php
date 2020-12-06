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

/**
 * @ignore
 */
require_once('CodeRage/File/generate.php');
require_once('CodeRage/Util/printScalar.php');

/**
 * Generates a PHP configuration file of the format expected by the class
 * CodeRage\Config.
 */
class Php implements \CodeRage\Build\Config\Writer {

    /**
     * Writes the given property bundle to the specified file.
     *
     * @param CodeRage\Build\ProjectConfig $properties
     * @param string $path
     * @throws Exception
     */
    function write(\CodeRage\Build\ProjectConfig $properties, $path)
    {
        $items = [];
        foreach ($properties->propertyNames() as $n) {
            $p = $properties->lookupProperty($n);
            $items[] = "'$n'=>" . $this->printLiteral($p->value());
        }
        $content = "\$config=array(" . join(",", $items) . ");\n";
        \CodeRage\File\generate($path, $content, 'php');
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
            return ctype_print($value) ?
                "'" . addcslashes($value, "\\'") . "'" :
                "base64_decode('" . base64_encode($value) . "')";
        case 'array':
            $literals = [];
            foreach ($value as $v)
                $literals[] = self::printLiteral($v);
            return 'array(' . join(',', $literals) . ')';
        default:
            throw new
                \Exception(
                    "Invalid property value: " . \CodeRage\Util\printScalar($value)
                );
        }
    }
}
