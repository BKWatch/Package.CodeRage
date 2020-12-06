<?php

/**
 * Defines the class CodeRage\Tool\BasicRobot
 * 
 * File:        CodeRage/Tool/BasicRobot.php
 * Date:        Sun Jan 14 20:43:12 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;

use DOMDocument;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Form;
use Throwable;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Text\Regex;
use CodeRage\Tool\RobotConstants as Constants;
use CodeRage\Tool\Robot\ContentRecorder;
use CodeRage\Tool\Robot\DefaultContentRecorder;
use CodeRage\Tool\Robot\FileUploadFieldSetter;
use CodeRage\Tool\Robot\RequestLogger;


/**
 * Subclass of CodeRage\Tool\Tool that uses the trait CodeRage\Tool\Robot
 */
class BasicRobot extends Tool {
    use Robot;

    /**
     * Constructs an instance of CodeRage\Tool\BasicRobot
     *
     * @param array $options The options array; supports all options supported
     *   by the CodeRage\Tool\Tool constructor and by
     *   CodeRage\Tool\Robot::robotInitialize()
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->robotInitialize($options);
    }
}
