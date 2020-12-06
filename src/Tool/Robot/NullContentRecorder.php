<?php

/**
 * Defines the class CodeRage\Tool\Robot\NullContentRecorder
 * 
 * File:        CodeRage/Tool/Robot/NullContentRecorder.php
 * Date:        Fri Jan 12 16:39:25 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Robot;

use CodeRage\Error;


/**
 * Content recorder that does not record data
 */
final class NullContentRecorder implements ContentRecorder {

    /**
     * Returns a phrase to be used in lieu of a file path or URL, indicating
     * that no data has been stored
     *
     * @return string
     */
    public function recordContent(string $content, string $contentType) : string
    {
        return '<no data available>';
    }
}
