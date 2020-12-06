<?php

/**
 * Defines the function CodeRage\Build\isBootstrapping.
 *
 * File:        CodeRage/Build/isBootstrapping.php
 * Date:        Fri Jan 16 15:11:31 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Returns true if the currently executing code is part of the boostrap code
 * base rather than the regular code base.
 *
 * @return boolean
 */
function isBootstrapping()
{
    return strncmp(__NAMESPACE__, 'Code', 4) != 0;
}
