<?php

/**
 * Contains the definition of the class CodeRage\Db\Schema\ParserBase
 *
 * File:        CodeRage/Db/Schema/ParserBase.php
 * Date:        Fri Sep 25 21:22:53 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Db\Schema;

use DOMElement;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Base class for CodeRage\Db\Schema\Parser and CodeRage\Db\Deleter
 */
abstract class ParserBase {

    /**
     * Path to the data source schema
     *
     * @var string
     */
    public const SCHEMA_PATH = __DIR__ . '/dataSource.xsd';

    /**
     * Resolves the "src" attribute of a schema element
     *
     * @param DOMElement $elt The schema element
     * @return string The resolved file path
     */
    protected static function resolveSrcAttribute(DOMElement $elt) : ?string
    {
        $src = $elt->getAttribute('src');
        $baseUri = Xml::documentPath($elt->ownerDocument);
        $path = File::resolve($src, $baseUri);
        if (!file_exists($path))
            $path = File::resolve($src, __DIR__ . '/../../../..');
        if ($path === null)
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' =>
                        "Failed resolving 'src' attribute '$src' in '$baseUri'"
                ]);
        return $path;
    }
}
