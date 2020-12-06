<?php

/**
 * Defines the class CodeRage\Queue\Test\MockProcessor
 *
 * File:        CodeRage/Queue/Test/MockProcessor.php
 * Date:        Mon Dec 30 21:22:09 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Queue\Test;

use CodeRage\Error;


/**
 * Queue processor used by the test suite CodeRage\Queue\Test\Suite
 */
final class MockProcessor extends \CodeRage\Tool\Tool {

    /**
     * Constructs a CodeRage\Queue\Test\MockProcessor
     *
     * @param array $options The options array; supports all options supported
     *   by CodeRage\Tool\Tool; plus the following options:
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * @param array $options The options array; supports the following options:
     *     lifetime - The task lifetime, in seconds (optional)
     * @return array The number of requests that were processed successfully
     */
    protected function doExecute(array $options)
    {

    }
}
