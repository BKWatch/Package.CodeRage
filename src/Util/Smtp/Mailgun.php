<?php

/**
 * Defines the class CodeRage\Util\Smtp\Mailgun, implementing the interface
 * CodeRage\Util\SmtpEngine using Mailgun api
 *
 * File:        CodeRage/Util/Smtp/Mailgun.php
 * Date:        Thu Aug 24 14:32:53 UTC 2017
 * Notice:      This document contains confidential information and
 *              trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Smtp;

use CodeRage;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\WebService\HttpRequest;


/**
 * Implements the mailgun SMTP engine using Mailgun API
 */
final class Mailgun implements CodeRage\Util\SmtpEngine {

    /**
     * Constructs an instance of CodeRage\Util\Smtp\Mailgun.
     *
     * @param array $options The options array; supports the following options:
     *   apiKey - The Mailgun API key; defaults to the value of configuration
     *     variable 'mailgun.api_key'
     *   apiUrl - The Mailgun API url; defaults to the value of configuration
     *     variable 'mailgun.api_url'
     */
    function __construct($options = [])
    {
        // Validate options
        $config = \CodeRage\Config::current();
        if (isset($options['apiKey'])) {
            Args::checkKey($options, 'apiKey', 'string', [
                'label' => 'Mailgun API key'
            ]);
        } else {
            $options['apiKey'] =
                $config->getRequiredProperty('mailgun.api_key');
        }
        if (isset($options['apiUrl'])) {
            Args::checkKey($options, 'apiUrl', 'string', [
                'label' => 'Mailgun API url'
            ]);
        } else {
            $options['apiUrl'] =
                $config->getRequiredProperty('mailgun.api_url');
        }
        $this->apiKey = $options['apiKey'];
        $this->apiUrl = $options['apiUrl'];
    }

    public final function send($options)
    {
        if (!isset($options['text']) && !isset($options['html']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' =>
                        "One of the 'text' or 'html' option must be set"
                ]);

        // Create attachments
        $attachments = [];
        if (isset($options['attachments'])) {
            foreach ($options['attachments'] as $i => $attachment) {
                $attachments["attachment[$i]"] =
                    new \CurlFile(
                        $attachment['path'],
                        $attachment['contentType'],
                        $attachment['filename']
                    );
            }
        }

        // Create inlines
        $inlines = [];
        if (isset($options['inlines'])) {
            foreach ($options['inlines'] as $i => $inline) {
                $inlines["inline[$i]"] =
                    new \CurlFile(
                        $inline['path'],
                        $inline['contentType'],
                        $inline['filename']
                    );
            }
        }

        // Construct message
        $message =
            [
                'from' => $options['from'],
                'to' => $options['to'],
                'subject' => $options['subject']
            ];
        if (isset($options['text']))
            $message['text'] = $options['text'];
        if (isset($options['html']))
            $message['html'] = $options['html'];
        if (isset($options['cc']))
            $message['cc'] = $options['cc'];
        if (isset($options['bcc']))
            $message['bcc'] = $options['bcc'];
        $message = array_merge($message, $attachments, $inlines);
        $request =
            new HttpRequest(
                    $this->apiUrl . '/messages',
                    'POST',
                    $message
                );

        // Set headers
        $request->setCredentials('api', $this->apiKey);
        if (isset($options['attachments']) || isset($options['inlines']))
            $request->setHeaderField('Content-Type', 'multipart/form-data');

        // Send message
        $request->submit(['throwOnError' => true]);
    }

    /**
     * The Mailgun API key
     *
     * @var string
     */
    private $apiKey;

    /**
     * The Mailgun API url
     *
     * @var string
     */
    private $apiUrl;
}
