<?php

/**
 * Defines the class CodeRage\Util\Smtp
 * 
 * File:        CodeRage/Util/Smtp.php
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
use CodeRage\File;
use CodeRage\Util\Args;
use CodeRage\Util\Factory;


/**
 * Sends email messages by delegating to a user-specified SMTP engine
 */
final class Smtp {

    /**
     * @var string
     */
    const DEFAULT_ENGINE = 'CodeRage.Util.Smtp.Legacy';

    /**
     * Constructs an instance of CodeRage\Util\Smtp.
     *
     * @param array $options The options array; supports the following options:
     *   engine - The class name of an SMTP engine in dot-separated format;
     *     defaults to the value of the configuration variable
     *     'coderage.util.smtp.engine'
     *   params - An associative array of string-valued options (optional)
     */
    function __construct($options = [])
    {
        // Validate options
        $engine = null;
        if (!isset($options['engine'])) {
            if (isset($options['params']))
                throw new
                    Error([
                        'status' => 'INCONSISTENT_PARAMETERS',
                        'details' =>
                            'SMTP engine parameters maynot be specified ' .
                            'without SMTP engine class name'
                    ]);
            $engine = self::DEFAULT_ENGINE;
        } else {
            Args::checkKey($options, 'engine', 'string', [
                'label' => 'SMTP engine class name'
            ]);
            $engine = $options['engine'];
        }
        Args::checkKey($options, 'params', 'map[string]', [
            'label' => 'SMTP engine parameters',
            'default' => []
        ]);

        // Load engine
        $this->engine =
            Factory::create([
               'class' => $engine,
               'params' => $options['params']
            ]);
    }

    /**
     * Invokes the send() method of the underlying SMTP engine
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
    public final function send($options)
    {
        // Validate options
        Args::checkKey($options, 'from', 'string', [
            'label' => 'sender address',
            'required' => true
        ]);
        Args::checkKey($options, 'to', 'string', [
            'label' => 'receipient addresses',
            'required' => true
        ]);
        Args::checkKey($options, 'cc', 'string', [
            'label' => 'CC addresses'
        ]);
        Args::checkKey($options, 'bcc', 'string', [
            'label' => 'BCC addresses'
        ]);
        Args::checkKey($options, 'subject', 'string', [
            'label' => 'subject header',
            'required' => true
        ]);
        Args::checkKey($options, 'text', 'string', [
            'label' => 'text body'
        ]);
        Args::checkKey($options, 'html', 'string', [
            'label' => 'HTML body'
        ]);
        Args::checkKey($options, 'attachments', 'array', [
            'label' => 'attachments'
        ]);
        Args::checkKey($options, 'inlines', 'array', [
            'label' => 'inline attachments'
        ]);
        foreach (['attachments', 'inlines'] as $opt) {
            if (!isset($options[$opt]))
                continue;
            foreach ($options[$opt] as $att) {
                Args::checkKey($att, 'filename', 'string', [
                    'label' => "email $opt name",
                    'required' => true
                ]);
                Args::checkKey($att, 'contentType', 'string', [
                    'label' => "email $opt content type",
                    'required' => true
                ]);
                Args::checkKey($att, 'path', 'string', [
                    'label' => "email $opt path",
                    'required' => true
                ]);
                File::checkReadable($att['path']);
            }
        }

        // Send message
        $this->engine->send($options);
    }

    /**
     * An smtp engine
     *
     * @var CodeRage\Util\SmtpEngine
     */
    private $engine;
}
