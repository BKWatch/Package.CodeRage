<?php

/**
 * Defines the class CodeRage\Util\Test\SmtpSuite which tests CodeRage\Util\Smtp
 * class and the classes implementing CodeRage\Util\SmtpEngine interface
 *
 * File:        CodeRage/Util/Test/SmtpSuite.php
 * Date:        Thu Aug 31 08:41:16 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\Factory;
use CodeRage\Util\Smtp;
use CodeRage\Util\Time;

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Util\Smtp and for classes in namespace
 * CodeRage/Util/Smtp
 */
class SmtpSuite extends \CodeRage\Test\ReflectionSuite {

    const MAILBOX_DOMAIN = '@sitekitmail.com';
    const MAILBOX_PASSWORD = 'Xxxx12345678xxxX';
    const FROM = 'Bkwatch@qacer.com';
    const TIMEOUT = 120;
    const MAILGUN_TEST_API_KEY = '0000000000000000000';
    const MAILGUN_TEST_API_URl = '/mailgun/test/url';

    /**
     * Constructs an instance of CodeRage\Util\Test\SmtpSuite
     */
    public function __construct()
    {
        parent::__construct(
            'SMTP Test Suite',
            'Tests the class CodeRage/Util/Smtp and the ' .
                'classes implementing the interface in CodeRage/Util/SmtpEngine'
        );
    }

    protected function suiteInitialize()
    {
        self::$emailNumber = 0;
    }

    protected function componentInitialize($component)
    {
        self::$emailNumber++;
    }

//     public function testCreatingSmtpEngine()
//     {
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testCreatingSmtpLegacyEngineWithParams()
//     {
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'host' => 'localhost',
//                             'username' => 'TestUsername',
//                             'password' => 'TestPassword'
//                         ]
//                 ]
//             );
//     }

//     public function testMissingFromParameterFailure()
//     {
//         $this->setExpectedStatusCode('MISSING_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//             ];
//         $smtp->send($options);
//     }

//     public function testMissingToParameterFailure()
//     {
//         $this->setExpectedStatusCode('MISSING_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $options =
//             [
//                 'from' => self::FROM,
//                 'subject' => 'Test email ' . self::$emailNumber,
//             ];
//         $smtp->send($options);
//     }

//     public function tesInvalidCcParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'cc' => 123
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidBccParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'bcc' => 123
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidSubjectParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 123,
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidTextParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'text' => 123
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidHtmlParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'html' => 123
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidAttachmentsParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'attachments' => 123
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidAttachmentWithMissingAttachmentPropertyPathFailure()
//     {
//         $this->setExpectedStatusCode('MISSING_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'attachments' =>
//                     [
//                         [
//                            'contentType' => 'text/xml',
//                            'filename' => 'xxx.xml',
//                         ]
//                     ]
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidAttachmentWithMissingAttachmentPropertyFilenameFailure()
//     {
//         $this->setExpectedStatusCode('MISSING_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $tmp = temp();
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'attachments' =>
//                     [
//                         [
//                            'contentType' => 'text/xml',
//                            'path' => $tmp
//                         ]
//                     ]
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidAttachmentWithMissingAttachmentPropertyContentTypeFailure()
//     {
//         $this->setExpectedStatusCode('MISSING_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $tmp = temp();
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'attachments' =>
//                     [
//                         [
//                            'filename' => 'xxx.xml',
//                            'path' => $tmp
//                         ]
//                     ]
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidAttachmentWithNonExistingPath()
//     {
//         $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'attachments' =>
//                     [
//                         [
//                            'path' => 'xxx',
//                            'contentType' => 'text/xml',
//                            'filename' => 'xxx.xml',
//                         ]
//                     ]
//             ];
//         $smtp->send($options);
//     }

//     public function testInvalidInlinesParameterFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp = new Smtp(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//         $to =  'testmailboxname' . self::MAILBOX_DOMAIN;
//         $options =
//             [
//                 'from' => self::FROM,
//                 'to' => $to,
//                 'subject' => 'Test email ' . self::$emailNumber,
//                 'inlines' => 123
//             ];
//         $smtp->send($options);
//     }

//     /**
//      *  Test Legacy engine in php
//      */

//     public function testCreatingSmtpLegacyEngineWithUsernameAndWithoutPasswordFailure()
//     {
//         $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'host' => 'localhost',
//                             'username' => 'TestUsername'
//                         ]
//                 ]
//             );
//     }

//     public function testCreatingSmtpLegacyEngineInvalidUsernameFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'host' => 'localhost',
//                             'username' => 1,
//                             'password' => 'xxx'
//                         ]
//                 ]
//             );
//     }

//     public function testCreatingSmtpLegacyEngineInvalidPasswordFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'host' => 'localhost',
//                             'username' => 'xxx',
//                             'password' => 1
//                         ]
//                 ]
//             );
//     }

//     public function testCreatingSmtpLegacyEngineInvalidHostFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'host' => 0
//                         ]
//                 ]
//             );
//     }

//     public function testCreatingSmtpLegacyEngineInvalidPortFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'port' => 0
//                         ]
//                 ]
//             );
//     }

//     public function testCreatingSmtpLegacyEngineInvalidSslFailure()
//     {
//         $this->setExpectedStatusCode('INVALID_PARAMETER');
//         $smtp =
//             new Smtp(
//                 [
//                     'engine' => 'CodeRage.Util.Smtp.Legacy',
//                     'params' =>
//                         [
//                             'ssl' => 'true'
//                         ]
//                 ]
//             );
//     }

//     public function testSendingEmailWithLegacyEngine()
//     {
//         $this->sendEmail(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithBccAndCc()
//     {
//         $this->sendEmailWithBccAndCc(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithHtml()
//     {
//         $this->sendEmailWithHtml(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithAttachments()
//     {
//         $this->sendEmailWithAttachments(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithInlines()
//     {
//         $this->sendEmailWithInlines(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithHtmlAndInlines()
//     {
//         $this->sendEmailWithHtmlAndInlines(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithTextAndHtml()
//     {
//         $this->sendEmailWithTextAndHtml(['engine' => 'CodeRage.Util.Smtp.Legacy']);
//     }

//     public function testSendingEmailWithLegacyEngineWithMultipleRecipients()
//     {
//         $this->sendEmailWithMultipleRecipients(
//             ['engine' => 'CodeRage.Util.Smtp.Legacy']
//         );
//     }

    /**
     *  Test Mailgun engine in php
     */

    public function testCreatingSmtpMailgunEngineWithParams()
    {
        $smtp =
            new Smtp(
                [
                    'engine' => 'CodeRage.Util.Smtp.Mailgun',
                    'params' =>
                        [
                            'apiKey' => self::MAILGUN_TEST_API_KEY,
                            'apiUrl' => self::MAILGUN_TEST_API_URl
                        ]
                ]
            );
    }

    public function testCreatingSmtpEngineWithMissingApiUrl()
    {
        $smtp =
            new Smtp(
                [
                    'engine' => 'CodeRage.Util.Smtp.Mailgun',
                    'params' =>
                        [
                            'apiKey' => self::MAILGUN_TEST_API_KEY
                        ]
                ]
            );
    }

    public function testCreatingSmtpEngineWithMissingApiKey()
    {
        $smtp =
            new Smtp(
                [
                    'engine' => 'CodeRage.Util.Smtp.Mailgun',
                    'params' =>
                        [
                            'apiUrl' => self::MAILGUN_TEST_API_URl
                        ]
                ]
            );
    }

     public function testSendingEmailWithMailgunEngine()
    {
        $this->sendEmail(['engine' => 'CodeRage.Util.Smtp.Mailgun']);
    }

    public function testSendingEmailWithMailgunEngineWithBccAndCc()
    {
        $this->sendEmailWithBccAndCc(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    public function testSendingEmailWithMailgunEngineWithHtml()
    {
        $this->sendEmailWithHtml(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    public function testSendingEmailWithMailgunEngineWithAttachments()
    {
        $this->sendEmailWithAttachments(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    public function testSendingEmailWithMailgunEngineWithInlines()
    {
        $this->sendEmailWithInlines(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    public function testSendingEmailWithMailgunEngineWithHtmlAndInlines()
    {
        $this->sendEmailWithHtmlAndInlines(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    public function testSendingEmailWithMailgunEngineWithTextAndHtml()
    {
        $this->sendEmailWithTextAndHtml(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    public function testSendingEmailWithMailgunEngineWithMultipleRecipients()
    {
        $this->sendEmailWithMultipleRecipients(
            ['engine' => 'CodeRage.Util.Smtp.Mailgun']
        );
    }

    /**
     * Send email with required and 'text' option of
     * CodeRage\Util\SmptEngine->send() method and validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmail($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $options =
            [
                'from' => self::FROM,
                'to' => $mailbox . self::MAILBOX_DOMAIN,
                'subject' => 'Test email ' . self::$emailNumber,
                'text' => 'Test email'
            ];
        $this->sendAndValidate($smtp, $options);
    }

    /**
     * Send email with required, 'cc' and 'bcc' options of
     * CodeRage\Util\SmptEngine->send() method, where 'to', 'cc' and 'bcc'
     * options are comma-separated list of recipient addressess and validate the
     * email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithMultipleRecipients($smtp)
    {
        $cc1 = $this->createRandomMailbox() . self::MAILBOX_DOMAIN;
        $cc2 = $this->createRandomMailbox() . self::MAILBOX_DOMAIN;
        $bcc1 = $this->createRandomMailbox() . self::MAILBOX_DOMAIN;
        $bcc2 = $this->createRandomMailbox() . self::MAILBOX_DOMAIN;
        $to1 = $this->createRandomMailbox() . self::MAILBOX_DOMAIN;
        $to2 = $this->createRandomMailbox() . self::MAILBOX_DOMAIN;
        $options =
            [
                'from' => self::FROM,
                'to' => $to1 . ',' . $to2,
                'subject' => 'Test email ' . self::$emailNumber,
                'text' => 'Test Email',
                'cc' => $cc1 . ',' . $cc2,
                'bcc' => $bcc1 . ',' . $bcc2,
            ];

        // Send email
        $smtp = new Smtp($smtp);
        $smtp->send($options);

        // Fetch emails
        $msgs1 = $this->fetchEmails($to1);
        $msgs2 = $this->fetchEmails($to2);
        $msgs3 = $this->fetchEmails($cc1);
        $msgs4 = $this->fetchEmails($cc2);
        $msgs5 = $this->fetchEmails($bcc1);
        $msgs6 = $this->fetchEmails($bcc2);

        // Assert
        $this->validateMessages($msgs1, $options);
        $this->validateMessages($msgs2, $options);
        $this->validateMessages($msgs3, $options);
        $this->validateMessages($msgs4, $options);
        $this->validateMessages($msgs5, $options);
        $this->validateMessages($msgs6, $options);
    }

    /**
     * Send email with required, 'bcc' and 'cc' options of
     * CodeRage\Util\SmptEngine->send() method and then fetch email and
     * validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithBccAndCc($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $bcc = $this->createRandomMailbox();
        $cc = $this->createRandomMailbox();
        $to = $mailbox . self::MAILBOX_DOMAIN;
        $options =
            [
                'from' => self::FROM,
                'to' => $to,
                'subject' => 'Test email ' . self::$emailNumber,
                'text' => 'Test Email',
                'bcc' => $bcc . self::MAILBOX_DOMAIN,
                'cc' => $cc . self::MAILBOX_DOMAIN
            ];

        // Send email
        $smtp = new Smtp($smtp);
        $smtp->send($options);

        // Fetch emails
        $msgs = $this->fetchEmails($to);
        $msgs1 = $this->fetchEmails($bcc . self::MAILBOX_DOMAIN);
        $msgs2 = $this->fetchEmails($cc . self::MAILBOX_DOMAIN);

        // Assert
        $this->validateMessages($msgs, $options);
        $this->validateMessages($msgs1, $options);
        $this->validateMessages($msgs2, $options);
    }

    /**
     * Send email with required and 'html' option of
     * CodeRage\Util\SmptEngine->send() method and then fetch email and
     * validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithHtml($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $options =
            [
                'from' => self::FROM,
                'to' => $mailbox . self::MAILBOX_DOMAIN,
                'subject' => 'Test email ' . self::$emailNumber,
                'html' =>
                    '<!DOCTYPE html><html><body><h1>Test email</h1></body></html>'
            ];
        $this->sendAndValidate($smtp, $options);
    }

    /**
     * Send email with required and 'attachments' option of
     * CodeRage\Util\SmptEngine->send() method and then fetch email and
     * validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithAttachments($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $options =
            [
                'from' => self::FROM,
                'to' => $mailbox . self::MAILBOX_DOMAIN,
                'subject' => 'Test email ' . self::$emailNumber,
                'text' => 'Testing email for attachments',
                'attachments' =>
                    [
                        $this->getAttachment('attachment1.txt'),
                        $this->getAttachment('attachment2.xml')
                    ]
            ];
        $this->sendAndValidate($smtp, $options);
    }

    /**
     * Send email with required and 'inlines' option of
     * CodeRage\Util\SmptEngine->send() method and then fetch email and
     * validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithInlines($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $options =
            [
                'from' => self::FROM,
                'to' => $mailbox . self::MAILBOX_DOMAIN,
                'subject' => 'Test email ' . self::$emailNumber,
                'text' => "Text body with inline images",
                'inlines' =>
                    [
                        $this->getAttachment('inline1.png')
                    ]
            ];
        $this->sendAndValidate($smtp, $options);
    }

    /**
     * Send email with required, 'html' and 'inlines' options of
     * CodeRage\Util\SmptEngine->send() method and then fetch email and
     * validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithHtmlAndInlines($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $options =
            [
                'from' => self::FROM,
                'to' => $mailbox . self::MAILBOX_DOMAIN,
                'subject' => 'Test email ' . self::$emailNumber,
                'html' =>
                    "<body>Html with <i>my</i> inline images " .
                    "image1:<img src=\"cid:inline1.png\"> " .
                    "image2:<img src=\"cid:inline2.png\"></body>",
                'inlines' =>
                    [
                        $this->getAttachment('inline1.png'),
                        $this->getAttachment('inline2.png')
                    ]
            ];
        $this->sendAndValidate($smtp, $options);
    }

    /**
     * Send email with required, 'text' and 'html' options of
     * CodeRage\Util\SmptEngine->send() method and then fetch email and
     * validate the email content
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     */
    private function sendEmailWithTextAndHtml($smtp)
    {
        $mailbox = $this->createRandomMailbox();
        $options =
            [
                'from' => self::FROM,
                'to' => $mailbox . self::MAILBOX_DOMAIN,
                'subject' => 'Test email ' . self::$emailNumber,
                'text' => "Test email text body",
                'html' => "<body>Html for testing my email service</body>"
            ];
        $this->sendAndValidate($smtp, $options);
    }

    /**
     * Send the email and validate the parts of email that are specified
     * in $options array
     *
     * @param array $smtp An array of options for CodeRage\Util\Smpt
     * @param array $options An array of options for
     *   CodeRage\Util\SmptEngine->send() method
     */
    private function sendAndValidate($smtp, $options)
    {
        $smtp = new Smtp($smtp);
        $smtp->send($options);
        $msgs = $this->fetchEmails($options['to']);

        // Assert
        $this->validateMessages($msgs, $options);
    }

    /**
     * Creates a random mail box and returns it's name
     *
     * @return string
     */
    private function createRandomMailbox()
    {
        $config = \CodeRage\Config::current();
        $pass = $config->getRequiredProperty('service_password');
        $manager = new \CodeRage\Util\Test\MailboxManager;
        return
            $manager->createRandomMailbox([
                'clientUsername' => 'SiteKit',
                'clientPassword' => $pass,
                'domain' => 'sitekitmail.com',
                'password' => self::MAILBOX_PASSWORD
            ]);
    }

    /**
     * Fetch and returns list of arrays of email parts with keys among
     * 'headers', 'html', 'text', 'inlines' and 'attachments'
     *
     * @param string $user A mailbox username
     * @param int $sleep A value to delay fetching of emails before trying to
     *   fetch email again in case when no email is found in mailbox
     */
    private function fetchEmails($user, $sleep = 4)
    {
        $lister =
            Factory::create(['class' => 'CodeRage.Util.Test.Smtp.EmailLister']);
        $options =
            [
                'host' => 'mail.sitekitmail.com',
                'port' => 995,
                'username' => $user,
                'password' =>  self::MAILBOX_PASSWORD,
            ];
        $msgs = $lister->execute($options);
        $time = Time::get();
        while(count($msgs) == 0) {
            sleep($sleep);
            $msgs = $lister->execute($options);
            if (Time::real() - $time > self::TIMEOUT)
                break;
        }
        return $msgs;
    }

    /**
     * Validates the email contents by comparing it with values in options
     * array. Options array contains the same options as the
     * CodeRage\Util\SmptEngine->send() method
     *
     * @param array $msgs A list of email messages
     * @param array $options An option array to validate values in message
     *   content
     * @throws Error
     */
    private function validateMessages($msgs, $options = [])
    {
        Assert::equal(count($msgs), 1, 'Invalid message count');
        $msg = $msgs[0];
        if (isset($options['from'])) {
            $options['from'] = preg_quote($options['from']);
            if (!preg_match("/From:\s?{$options['from']}/", $msg['headers']))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => "Invalid 'From' header"
                    ]);
        }
        if (isset($options['to'])) {
            if (!$this->matchRecipients($msg['headers'], $options['to'], 'To'))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => "Invalid 'To' header"
                    ]);
        }
        if (isset($options['subject'])) {
            $options['subject'] = preg_quote($options['subject']);
            if (!preg_match("/Subject:\s?{$options['subject']}/", $msg['headers']))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => "Invalid 'Subject' header"
                    ]);
        }
        if (isset($options['cc'])) {
            if (!$this->matchRecipients($msg['headers'], $options['cc'], 'Cc'))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => "Invalid 'Cc' header"
                    ]);
        }
        if (isset($options['bcc'])) {
            if ($this->matchRecipients($msg['headers'], $options['bcc'], 'Bcc'))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Expected no 'Bcc' recipient; found Bcc recipient"
                    ]);
        }
        if (isset($options['text'])) {
            if (!isset($msg['text'])) {

                // Locate text body in inline content of email message
                if (isset($msg['inlines']))
                    $text =
                        array_filter(
                            $msg['inlines'],
                            function($k) {
                                if ($k['type'] == 'text/plain')
                                    return true;
                            }
                        );
                if (count($text) == 0)
                    throw new
                        Error([
                            'status' => 'ASSERTION_FAILED',
                            'message' => "Missing text body in message"
                        ]);
                $text = array_values($text);
                $msg['text'] = $text[0]['content'];
            }
            $msg['text'] = trim($msg['text']);
            if ($options['text'] != $msg['text'])
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Invalid text body; Expected '{$options['text']}' " .
                            "found '{$msg['text']}'"
                    ]);
        }
        if (isset($options['html'])) {
            if (!isset($msg['html'])) {

                // Locate html body in inline content of email message
                if (isset($msg['inlines']))
                    $html =
                        array_filter(
                            $msg['inlines'],
                            function($k) {
                                if ($k['type'] == 'text/html')
                                    return true;
                            }
                        );
                if (count($html) == 0)
                    throw new
                        Error([
                            'status' => 'ASSERTION_FAILED',
                            'message' => "Missing 'html' in message"
                        ]);
                $html = array_values($html);
                $msg['html'] = $html[0]['content'];
            }
            $msg['html'] = trim($msg['html']);
            if ($options['html'] != $msg['html'])
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Invalid HTML; Expected '{$options['html']}' " .
                            "found '{$msg['html']}'"
                    ]);
        }
        foreach (['attachments', 'inlines'] as $opt) {
            if (!isset($options[$opt]))
                continue;
            if (!isset($msg[$opt]))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => "Missing $opt in message"
                    ]);
            foreach ($options[$opt] as $att) {
                $content1 = file_get_contents($att['path']);

                // Decoding content for comparison
                if (preg_match('/audio|image|video/', $att['contentType']))
                    $content1 = base64_encode($content1);
                $matched = false;
                foreach ($msg[$opt] as $emailAtt) {
                    $content2 = $emailAtt['content'];
                    if (preg_match('/audio|image|video/', $emailAtt['type'])) {

                        // Decoding and encoding to remove line breaks for
                        // comaprison
                        $content2 = base64_encode(base64_decode($content2));
                    }

                    // Replace windows line endings, if any
                    $content2 = preg_replace('/\r\n/', "\n", $content2);
                    if ( $att['contentType'] === $emailAtt['type'] &&
                         $att['filename'] === $emailAtt['filename'] &&
                         trim($content1) === trim($content2)
                       )
                    {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    throw new
                        Error([
                            'status' => 'ASSERTION_FAILED',
                            'message' => "Mismatch $opt properties"
                        ]);
                }
            }
        }
    }

    /**
     * Returns an array with keys 'path', 'contentType' and 'filename' for
     * the file specified. This function will look for files in
     * CodeRage/Util/Test/Smtp directory
     *
     * @param string $name A filename with extension
     * @return array
     */
    private function getAttachment($name)
    {
        $config = \CodeRage\Config::current();
        $attachment =
            $config->getRequiredProperty('project_root') .
                "/CodeRage/Util/Test/Smtp/$name";
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $type = null;
        switch ($ext) {
            case 'xml':
                $type = 'text/xml';
                break;
            case 'txt':
                $type = 'text/plain';
                break;
            case 'png':
                $type = 'image/png';
                break;
        }
        return
            [
                'path' => $attachment,
                'contentType' => $type,
                'filename' => $name
            ];
    }

    /**
     * Match the list of recipients in the headers string using the given header
     * name
     *
     * @param string $headers A string containing the message headers
     * @param array $recipients An array of recipients to match
     * @param string $headerName A header name to match recipients list of; one
     *   of the value among 'To', 'Cc' or 'Bcc'
     * @return boolean
     */
    private function matchRecipients($headers, $recipients, $headerName)
    {
        $recipients = explode(',', $recipients);
        $found = null;
        $regex = "/^$headerName: ((\s*[a-z0-9]{30}@.*,?\r?\n)+)/m";
        if (!preg_match($regex, $headers, $found))
            return false;
        $found = array_map('trim', explode(',', $found[1]));
        foreach ($recipients as $i => $r) {
            if ( !isset($found[$i]) ||
                 (isset($found[$i]) && $r !== $found[$i]))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * A counter for email
     *
     * @var int
     */
    private static $emailNumber;
}
