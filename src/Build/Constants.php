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
 * Container for class constants to be made available via autoload
 */
class Constants {

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
     * Indicates that a property has type boolean.
     */
    const BOOLEAN = ISSET_ << 1;

    /**
     * Indicates that a property has type int.
     */
    const INT = BOOLEAN << 1;

    /**
     * Indicates that a property has type float.
     */
    const FLOAT = INT << 1;

    /**
     * Indicates that a property has type string.
     */
    const STRING = FLOAT << 1;

    /**
     * Indicates that a property represents a list of values; this flag may be
     * combined with one of the flags CodeRage\BuildXXX, where XXX is
     * BOOLEAN, INT, FLOAT, or STRING.
     */
    const LIST_ = STRING << 1;

    /**
     * Indicates that a property is required.
     */
    const REQUIRED = LIST_ << 1;

    /**
     * Indicates that a property will be remembered and applied at the next build
     * if it is not explicitly set.
     */
    const STICKY = REQUIRED << 1;

    /**
     * Indicates that a property's value should not be displayed to the user.
     */
    const OBFUSCATE = STICKY << 1;

    const TYPE_MASK = BOOLEAN | INT | FLOAT | STRING;

    /**
     * The prefix to use when setting a Makeme variable using an environment
     * variable.
     */
    const CONFIG_ENVIRONMENT_PREFIX = 'coderage_';

    /**
     * The namespace URI for configuration and project definition files.
     */
    const NAMESPACE_URI = 'http://www.coderage.com/2008/project';

    /**
     * Git repository URL.
     */
    const REPO_URL = 'git@github.com:BKWatch/CodeRage';

    /**
     * Default Git ref for the CodeRage Git repository
     */
    const REPO_BRANCH = 'master';

    /**
     * Timezone to use if no timezone is set in php.ini.
     */
    const DEFAULT_TIMEZONE = 'America/Denver';
}
