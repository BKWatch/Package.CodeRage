<?php

/**
 * Defines the class CodeRage\Tool\Test\ContentRecorder
 *
 * File:        CodeRage/Tool/Test/ContentRecorder.php
 * Date:        Thu Sep 24 00:29:06 UTC 2020
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

/**
 * Content recorder that stores response bodies in an array
 */
final class ContentRecorder implements \CodeRage\Tool\Robot\ContentRecorder {

    /**
     * Returns a random string
     *
     * @param string $content The data to store
     * @param string $contentType The MIME media type of $content
     * @return string
     */
    public function recordContent(string $content, string $contentType) : string
    {
        $identifier = \CodeRage\Util\Random::string(50);
        self::$content[$identifier] = $content;
        return $identifier;
    }

    /**
     * Returns the response body associated with the given identifier, if any
     *
     * @param string $identifier
     * @return string
     */
    public static function getContent(string $identifier) : ?string
    {
        return self::$content[$identifier] ?? null;
    }

    /**
     * @var array
     */
    private static $content = [];
}
