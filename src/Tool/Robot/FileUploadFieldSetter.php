<?php

/**
 * Defines the class CodeRage\Tool\Robot\FileUploadFieldSetter
 *
 * File:        CodeRage/Tool/Robot/FileUploadFieldSetter.php
 * Date:        Thu Jan 18 03:02:39 UTC 2018
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Robot;

use Symfony\Component\DomCrawler\Field\FileFormField;
use CodeRage\File;
use CodeRage\Util\Args;


/**
 * Utility for setting the value of a file upload field
 */
final class FileUploadFieldSetter extends FileFormField {

    /**
     * The the value of the specified file upload field
     *
     * @param Symfony\Component\DomCrawler\Field\FileFormField $field
     * @param string $path The file path
     * @param string $filename The file name
     * @param string $contentType The MIME media type
     */
    static function set(FileFormField $field, $path, $filename, $contentType)
    {
        Args::check($path, 'string', 'path');
        Args::check($filename, 'string', 'file name');
        Args::check($contentType, 'string', 'content type');
        File::checkReadable($path);
        $temp = File::temp();
        copy($path, $temp);
        $field->value =
            [
                'name' => $filename,
                'type' => $contentType,
                'size' => filesize($path),
                'tmp_name' => $temp,
                'error' => UPLOAD_ERR_OK
            ];
    }

    /**
     * Copies the value from one file upload field to another
     *
     * @param Symfony\Component\DomCrawler\Field\FileFormField $lhs
     * @param Symfony\Component\DomCrawler\Field\FileFormField $rhs
     */
    static function copy(FileFormField $lhs, FileFormField $rhs)
    {
        $rhs->value = $lhs->value;
    }
}
