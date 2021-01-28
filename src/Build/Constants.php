<?php

/**
 * Defines the class CodeRage\Build\Constants
 *
 * File:        CodeRage/Build/Constants.php
 * Date:        Mon Jan 21 13:55:27 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Indicates that configuration information was specified on the command line.
 */
const COMMAND_LINE = 1;

/**
 * Indicates that configuration information was specified in the environment.
 */
const ENVIRONMENT = 2;

/**
 * Indicates that configuration information was specified at the console.
 */
const CONSOLE = 3;

/**
 * Indicates that a property has been assigned a value, possibly null.
 */
const ISSET_ = 4;

/**
 * The namespace URI for configuration and project definition files.
 */
const NAMESPACE_URI = 'http://www.coderage.com/2008/project';
