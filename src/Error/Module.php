<?php

/**
 * Defines the class CodeRage\Error\Module
 *
 * File:        CodeRage/Error/Module.php
 * Date:        Wed Dec 16 19:52:11 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Error;

use DOMDocument;
use CodeRage\Sys\ProjectConfig;
use CodeRage\Sys\Engine;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Os;
use CodeRage\Xml;

/**
 * Error module
 */
final class Module extends \CodeRage\Sys\BasicModule {

    /**
     * Constructs an instance of CodeRage\Error\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'Error',
            'description' => 'Error module',
            'statusCodes' => dirname(__DIR__) . '/error.xml'
        ]);
    }

    public function build(Engine $engine): void
    {
        $statusCodes = [];
        foreach ($engine->moduleStore()->modules() as $module) {
            if ($path = $module->statusCodes()) {
                $codes = $this->loadStatusCodes($path);
                foreach ($codes as $code => $def) {
                    if (isset($statusCodes[$code])) {
                        throw new
                            Error([
                                'status' => 'CONFIGURATION_ERROR',
                                'message' =>
                                    "The status code '$code', defined in " .
                                    "'$path', was previously defined in " .
                                    $statusCodes[$code]['path']
                            ]);
                    }
                    $statusCodes[$code] = $def;
                }
            }
        }
        $definition = "return [\n";
        foreach ($statusCodes as $code => $def) {
            $definition .=
                "    '$code' => [\n" .
                "        'code' => '$code',\n" .
                "        'message' => " . $this->formatString($def['message']) . ",\n" .
                "        'path' => " . $this->formatString($def['path']). "\n" .
                "    ],\n";
        }
        $definition .= "]\n;";
        $path = Config::projectRoot() . '/' . Error::STATUS_CODES_PATH;
        File::generate($path, $definition, 'php');
        $engine->recordGeneratedFile($path);
    }

    /**
     * Loads status codes from the specified XML file
     *
     * @param string $path
     * return array
     */
    private function loadStatusCodes($path): array
    {
        $doc = Xml::loadDocument($path);
        $statusCodes = [];
        foreach (Xml::childElements($doc->documentElement, 'status') as $status) {
            $code = $message = null;
            foreach (Xml::childElements($status) as $k) {
                if ($k->localName == 'code') {
                    if ($code !== null)
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Malformed error status definition in " .
                                    "'$path': the element 'code' may occur " .
                                    "only once"
                            ]);
                    $code = $k->nodeValue;
                    if (!preg_match('/[._a-z0-9]+$/i', $code))
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Malformed status code '$code' in " .
                                    "'$path': status codes may contain only " .
                                    "letters, digits, underscores, and dots"
                            ]);
                }
                if ($k->localName == 'message') {
                    if ($message !== null)
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Malformed error status definition in " .
                                    "'$path': the element 'message' may occur " .
                                    "only once"
                            ]);
                    $message = preg_replace('/\s+/', ' ', trim($k->nodeValue));
                }
            }
            $statusCodes[$code] =
                [
                    'code' => $code,
                    'message' => $message,
                    'path' => $path
                ];
        }
        return $statusCodes;
    }

    /**
     * Returns a PHP expression evaluating to the given string
     *
     * @param string $value
     * @return string
     */
    private function formatString(string $value)
    {
        return strlen($value) == 0 || ctype_print($value) ?
            "'" . addcslashes($value, "\\'") . "'" :
            "base64_decode('" . base64_encode($value) . "')";
    }
}
