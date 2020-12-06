<?php

/**
 * Defines the function CodeRage\Build\Svn\hasLocalModifications.
 *
 * File:        CodeRage/Build/Svn/hasLocalModifications.php
 * Date:        Mon Jan 12 14:46:51 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Svn;

use CodeRage\Error;
use CodeRage\Log;
use function CodeRage\Xml\firstChildElement;

/**
 * @ignore
 */
require_once('CodeRage/File/isAbsolute.php');
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/system.php');
require_once('CodeRage/Xml/childElements.php');
require_once('CodeRage/Xml/firstChildElement.php');

/**
 * Returns true if the specified working copy has local modifications.
 *
 * @param CodeRage\Build\Run $run The current run of the build system.
 * @param string $path The working copy directory.
 */
function hasLocalModifications(\CodeRage\Build\Run $run, $path)
{
    // Prepare path
    if (!\CodeRage\File\isAbsolute($path))
        throw new
            Error(
                "Invalid working copy: expected absolute path; found " .
                "'$path'"
            );
    if (!file_exists($path))
        throw new
            Error(
                "Invalid working copy; the file '$path' does not exist"
            );
    $path = \CodeRage\Util\escapeShellArg(realpath($path));

    // Execute command
    $output = \CodeRage\Util\system("svn status --non-interactive --xml $path");

    // Parse output
    $dom = new \DOMDocument;
    if (!@$dom->loadXml($output)) {
        throw new
            Error(
                "Failed parsing XML output of 'svn status' for working " .
                "copy '$path'"
            );
    }
    if (($name = $dom->documentElement->localName) != 'status') {
        throw new
            Error(
                "Failed parsing XML output of 'svn status' for working " .
                "copy '$path': expected 'status' element; found '$name'"
            );
    }
    $target = firstChildElement($dom->documentElement, 'target');
    if (!$target)
        throw new
            Error(
                "Failed parsing XML output of 'svn status' for working " .
                "copy '$path': no 'target' element"
            );
    $hasModifications = false;
    $info = $run->getStream(Log::INFO);
    $debug = $run->getStream(Log::DEBUG);
    foreach (\CodeRage\Xml\childElements($target, 'entry') as $entry) {
        if (!$hasModifications) {
            $hasModifications = true;
            $info->write(
                "The working copy '$path' has local modifications; run " .
                "'svn status' for details"
            );
        }
        if (!$debug)
            break;
        $wcStatus = firstChildElement('wc-status');
        if ($wcStatus)
            $debug->write(
                "The path '" . $entry->getAttribute('path') . "' has " .
                "status '" . $wcStatus->getAttribute('item') . "'"
            );
    }
    return $hasModifications;
}
