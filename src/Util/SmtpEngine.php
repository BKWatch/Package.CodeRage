<?php

/**
 * Defines the interface CodeRage\Util\SmtpEngine
 * 
 * File:        CodeRage/Util/SmtpEngine.php
 * Date:        Thu Aug 24 14:32:53 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage;
use CodeRage\Error;


/**
 * Defines the SMTP engine interface for use with CodeRage\Util\Smtp
 */
interface SmtpEngine {

    /**
     *  Sends an email message
     *
     * @param array $options The options array; supports the following options:
     *   from - The email address of the sender
     *   to - A comma-separated list of recipient addressess
     *   cc - A comma-separated list of CC addressess (optional)
     *   bcc - A comma-separated list of BCC addressess (optional)
     *   subject - The subject header
     *   text - The body of the email, in plain text format (optional)
     *   html - The body of the email, in HTML format (optional)
     *   attachments - A list of associative arrays with keys "path",
     *     "contentType", and "filename", representing attachments (optional)
     *   inlines - A list of associative arrays with keys "path",
     *     "contentType", and "filename", representing inline attachments
     *     (optional)
     */
    public function send($options);
}
