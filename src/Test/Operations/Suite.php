<?php

/**
 * Defines the class CodeRage\Test\Operations\Suite
 *
 * File:        CodeRage/Test/Operations/Suite.php
 * Date:        Tue May  8 23:56:33 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

/**
 * @ignore
 */

/**
 * Test suite whose cases are instances of CodeRage\Test\Operations\Case
 * constructed from XML files in a specified directory
 */
class Suite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Suite.
     *
     * @param string $name The suite name
     * @param string $description The suite description
     * @param string $directory The directory containing XML documents
     * @param array $ext A list of file extensions to process; if not supplied,
     *   only files with the extension 'xml' will be processed
     */
    public function __construct($name, $description, $directory, $ext = null)
    {
        parent::__construct($name, $description);
        foreach (Operation::loadAll($directory, $ext) as $op)
            $this->add(new Case_($op));
    }
}
