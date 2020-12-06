<?php

/**
 * Entry point of web service called by CodeRage::Tool::Runner->run()
 *
 * File:        CodeRage/Tool/__www__/run.php
 * Date:        Thu Mar 12 04:42:29 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

require_once('CodeRage.php');

\CodeRage\Tool\Runner::handleRequest();
