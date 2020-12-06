<?php

/**
 * Defines the class CodeRage\Tool\Test\MockRequestLogger
 *
 * File:        CodeRage/Tool/Test/MockRequestLogger.php
 * Date:        Fri Jan 12 16:39:25 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

use Psr\Http\Message\UriInterface as Uri;
use CodeRage\Tool\Tool;
use CodeRage\Util\Args;


/**
 * Implementation of CodeRage\Tool\Robot\RequestLogger used by
 * CodeRage\Tool\Test\RobotSuite
 */
class MockRequestLogger implements \CodeRage\Tool\Robot\RequestLogger {

    /**
     * Constructs a CodeRage\Tool\Test\MockRequestLogger
     *
     * @param array $preLoggers A list of callables with signature
     *   log($robot, $method, $url)
     * @param array $postLoggers A list of callables with signature
     *   log($robot, $method, $url, $error = null);
     */
    public function __construct($preLoggers, $postLoggers = [])
    {
        Args::check($preLoggers, 'array', 'request');
        foreach ($preLoggers as $log)
            Args::check($log, 'callable', 'request');
        if (empty($preLoggers))
            $preLoggers[] = function() { };
        Args::check($postLoggers, 'array', 'request');
        foreach ($postLoggers as $log)
            Args::check($log, 'callable', 'request');
        if (empty($postLoggers))
            $postLoggers[] = function() { };
        $this->preLoggers = $preLoggers;
        $this->postLoggers = $postLoggers;
    }

    public function preRequest(Tool $robot, string $method, Uri $uri) : void
    {
        static $count = 0;
        $log = $this->preLoggers[min($count++, count($this->preLoggers) - 1)];
        $log($robot, $method, $uri);
    }

    public function postRequest(Tool $robot, string  $method, Uri $uri,
        $error = null) : void
    {
        static $count = 0;
        $log = $this->postLoggers[min($count++, count($this->postLoggers) - 1)];
        $log($robot, $method, $uri, $error);
    }

    /**
     * A list of callables with signature log($robot, $method, $url)
     *
     * @var array
     */
    private $preLoggers;

    /**
     * A list of callables with signature
     * log($robot, $method, $url, $error = null);
     *
     * @var array
     */
    private $postLoggers;
}
