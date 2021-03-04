<?php

/**
 * Defines the class CodeRage\Sys\Config\Writer\Php.
 *
 * File:        CodeRage/Sys/Config/Writer/Php.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config\Writer;

use CodeRage\Error;
use CodeRage\File;

/**
 * Generates a PHP configuration file of the format expected by the class
 * CodeRage\Config.
 */
final class Php implements \CodeRage\Sys\Config\Writer {

    /**
     * Writes the given property bundle to the specified file.
     *
     * @param CodeRage\Sys\ProjectConfig $properties
     * @param string $path
     * @throws Exception
     */
    public function write(\CodeRage\Sys\ProjectConfig $properties, string $path): void
    {
        $items = [];
        foreach ($properties->propertyNames() as $n) {
            $p = $properties->lookupProperty($n);
            $items[] =
                "'$n' => [" .  $p->storage() . ', ' .
                $this->printLiteral($p->value()) . ']';
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
    private static function printLiteral(string $value): string
    {
        return strlen($value) == 0 || ctype_print($value) ?
            "'" . addcslashes($value, "\\'") . "'" :
            "base64_decode('" . base64_encode($value) . "')";
    }
}
