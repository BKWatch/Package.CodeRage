<?php

/**
 * Contains the definition of the class CodeRage\Test\Case_, representing a
 * test instance with text output and a single boolean result
 *
 * File:        CodeRage/Test/Case_.php
 * Date:        Wed Mar 14 06:38:17 MDT 2007
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

/**
 * @ignore
 */

/**
 * Represents a test instance with text output and a single boolean result
 */
abstract class Case_ extends Component {

    /**
     * constructs a CodeRage\Test\Component with the given name and description
     *
     * @param string $name A descriptive name, unique within the list
     * of the children of this component's parent component
     * @param string $description A brief description
     */
    protected function __construct($name, $description)
    {
        parent::__construct($name, $description);
    }

    /**
     * Returns TYPE_CASE
     *
     * @return int
     */
    protected final function doType() { return self::TYPE_CASE; }

    /**
     * Override to execute this component, writing informative messages to
     * standard output  if desired.
     *
     * @param array $params an associate array of parameters.
     *
     * @return boolean true for success
     * @throws Exception if an error occurs
     */
    //protected abstract function doExecute($params);
}
