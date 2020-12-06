<?php

/**
 * Defines the class CodeRage\Build\TryAgain.
 *
 * File:        CodeRage/Build/TryAgain.php
 * Date:        Tue Jan 13 15:43:55 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Thrown by CodeRage\Build\Target::execute() to indicate that execute() should be
 * invoked again during a later iteration of the build process.
 *
 */
class TryAgain extends \Exception {

}
