<?php

/**
 * Defines the interface CodeRage\Tool\Robot\ContentRecorder
 * 
 * File:        CodeRage/Tool/Robot/ContentRecorder.php
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
 * Interface for components that store data encountered during robot execution
 * and return an identifier that can be used to retrieve the data
 */
interface ContentRecorder {

    /**
     * Stores the given data and returns an identifier, such as a file path or
     * URL, that can be used to retrieve the data
     *
     * @param string $content The data to store
     * @param string $contentType The MIME media type of $content
     * @return string The identifier
     */
    function recordContent(string $content, string $contentType) : string;
}
