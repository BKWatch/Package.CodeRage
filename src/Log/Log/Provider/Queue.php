<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\Queue
 *
 * File:        CodeRage/Log/Provider/Queue.php
 * Date:        Fri Feb  1 13:05:21 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

/**
 * @ignore
 */

/**
 * Implementation of CodeRage\Log\Provider that adds entries to a queue
 */
final class Queue implements \CodeRage\Log\Provider {

    function name() { return 'queue'; }

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    public function dispatchEntry(\CodeRage\Log\Entry $entry)
    {
        $this->entries[] = $entry;
    }

    /**
     * Returns a reference to the underlying queue
     */
    public function &entries() { return $this->entries; }

    /**
     * @var array
     */
    private $entries = [];
}
