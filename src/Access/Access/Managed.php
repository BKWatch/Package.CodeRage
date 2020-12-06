<?php

/**
 * Defines the interface CodeRage\Access\Managed
 *
 * File:        CodeRage/Access/Managed.php
 * Date:        Sun Jun 10 12:45:50 EDT 2012
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

/**
 * Represents an entity that is associated with a resource so that
 * it can participate in the access control system
 */
interface Managed {

    /**
     * Returns the associated resource
     *
     * @return CodeRage\Access\Resource_
     */
    function resource();
}
