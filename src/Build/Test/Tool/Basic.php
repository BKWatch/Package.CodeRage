<?php

/**
 * Defines the class CodeRage\Build\Test\Tool\Basic, the base class for the
 * tools CodeRage\Build\Test\Tool\Foo and CodeRage\Build\Test\Tool\Bar.
 *
 * File:        CodeRage/Build/Test/Tool/Basic.php
 * Date:        Sat Mar 21 13:30:26 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test\Tool;

use CodeRage\Error;

/**
 * @ignore
 */
require_once('CodeRage/File/checkReadable.php');

/**
 * Base class for the tools CodeRage\Build\Test\Tool\Foo and CodeRage\Build\Test\Tool\Bar.
 */
class Basic extends \CodeRage\Build\Tool\Basic {

    /**
     * The simple name of the tool type, e.g., "foo" or "bar"
     *
     * @var string
     */
    private $type;

    /**
     * Constructs a CodeRage\Build\Test\Tool\Basic.
     *
     * @param string $type The simple name of the tool type, e.g., "foo" or
     * "bar".
     */
    function __construct($type)
    {
        parent::__construct();
        $this->type = $type;
    }

    /**
     * Returns true if $localName is equal to the underlying tool type.
     * @param string $localName
     * @param string $namespace
     * @return boolean
     */
    function canParse($localName, $namespace)
    {
        return $localName == $this->type;
    }

    /**
     * Returns an instance of CodeRage\Build\Target newly created from the given
     * XML element.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param DOMElement $element
     * @param string $baseUri The URI for resolving relative paths referenced by
     * $elt
     * @return CodeRage\Build\Target
     * @throws CodeRage\Error
     */
    function parseTarget(\CodeRage\Build\Run $run, \DOMElement $elt, $baseUri)
    {
        if ($elt->localName != $this->type)
            throw new
                Error(['message' =>
                    "Can't parse target: expected '$this->type'; found " .
                    "'$elt->localName'"
                ]);
        if (!$elt->hasAttribute('status'))
            throw new
                Error(['message' =>
                    "Failed parsing target '$elt->localName': missing " .
                    "'status' attribute"
                ]);
        $status = $elt->getAttribute('status');
        switch ($status) {
        case 'success':
        case 'fail-build':
            $name = ucfirst($this->type);
            $tools = $run->buildConfig()->toolsPath();
            $path = "$tools/CodeRage/Build/Test/Target/$name.php";
            \CodeRage\File\checkReadable($path);
            require_once($path);
            $class = "CodeRage\\Build\\Test\\Target\\$name";
            return new $class($status == 'success');
        case 'fail-parse':
            throw new Error(['message' => "An error occurred"]);
        default:
            throw new Error(['message' => "Invalid 'status' attribute: $status"]);
        }
    }
}
