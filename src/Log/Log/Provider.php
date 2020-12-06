<?php

/**
 * Defines the interface CodeRage\Log\Provider
 *
 * File:        CodeRage/Log/Provider.php
 * Date:        Tue Jul 14 21:53:57 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log;

/**
 * Delivers log entries
 */
interface Provider {

    /**
     * The symbolic name of this provider
     *
     * @return string
     */
    function name();

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
    */
    function dispatchEntry(Entry $entry);
}
