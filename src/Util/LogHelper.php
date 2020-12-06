<?php

/**
 * Defines the trait CodeRage\Util\LogHelper
 * 
 * File:        CodeRage/Util/LogHelper.php
 * Date:        Tue Feb  7 06:03:23 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Log;


/**
 * Assists in implementing the logXXX() methods of CodeRage\Util\SystemHandle
 */
trait LogHelper {
    public final function logMessage($msg)
    {
        if ($log = $this->log()->getStream(Log::INFO))
            $log->write($msg);
    }

    public final function logWarning($msg)
    {
        if ($log = $this->log()->getStream(Log::WARNING))
            $log->write($msg);
    }

    public final function logError($msg)
    {
        if ($log = $this->log()->getStream(Log::ERROR))
            $log->write($msg);
    }

    public final function logCritical($msg)
    {
        if ($log = $this->log()->getStream(Log::CRITICAL))
            $log->write($msg);
    }
}
