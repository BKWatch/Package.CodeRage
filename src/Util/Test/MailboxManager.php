<?php

/**
 * Defines the class CodeRage\Util\Test\MailboxManager
 *
 * File:        CodeRage/Util/Test/MailboxManager.php
 * Date:        Tue Dec 14 17:44:32 EDT 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Random;


/**
 * Wrapper for the MailboxManager email provisioning service
 */
class MailboxManager {

    /**
     * @var string
     */
    const SERVIVE_ADDRESS = 'https://sitekitmail.com/mailbox-manager';

    /**
     * @var string
     */
    const NAMESPACE_URI = 'http://www.counselnow.com/2010/mailbox-manager';

    /**
     * @var int
     */
    const RANDOM_MAILBOX_LENGTH = 30;

    /**
     * Creates a mailbox
     *
     * @param array $options The options array; supports the following options:
     *   clientUsername - The username of the webservice client
     *   clientPassword - The password of the webservice client
     *   username - The first component of the mailbox, i.e., the part before
     *     the "@" in the email address
     *   domain - The domain of the mailbox
     *   password - The mailbox password
     * @return boolean true unless an exception is thrown
     * @throws CodeRage\Error
     */
    public function createMailbox(array $options)
    {
        $this->processOptions($options);
        Args::checkKey($options, 'password', 'string', [
            'label' => 'mailbox password',
            'required' => true
        ]);
        $client = $this->soapClient();
        $response =
            $client->createMailbox(
                $options['clientUsername'],
                $options['clientPassword'],
                $options['username'],
                $options['domain'],
                $options['password']
            );
        if ($response->status)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' => $response->message
                ]);
        return true;
    }

    /**
     * Creates a mailbox with a random name, returning the name
     *
     * @param array $options The options array; supports the following options:
     *   clientUsername - The username of the webservice client
     *   clientPassword - The password of the webservice client
     *   domain - The domain of the mailbox
     *   password - The mailbox password
     * @return string The mailbox name
     * @throws CodeRage\Error
     */
    public function createRandomMailbox(array $options)
    {
        if (isset($options['username']))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'Unsupported option: username'
                ]);
        $options['username'] =
            strtolower(Random::string(self::RANDOM_MAILBOX_LENGTH));
        $this->processOptions($options);
        Args::checkKey($options, 'password', 'string', [
            'label' => 'mailbox password',
            'required' => true
        ]);
        $client = $this->soapClient();
        $response =
            $client->createMailbox(
                $options['clientUsername'],
                $options['clientPassword'],
                $options['username'],
                $options['domain'],
                $options['password']
            );
        if ($response->status)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' => $response->message
                ]);
        return $options['username'];
    }

    /**
     * Checks if a mailbox exists
     *
     * @param array $options The options array; supports the following options:
     *   clientUsername - The username of the webservice client
     *   clientPassword - The password of the webservice client
     *   username - The first component of the mailbox, i.e., the part before
     *    the "@" in the email address
     *   domain - The domain of the mailbox
     * @return boolean
     * @throws CodeRage\Error
     */
    public function mailboxExists(array $options)
    {
        $this->processOptions($options);
        $client = $this->soapClient();
        $response =
            $client->mailboxExists(
                $options['clientUsername'],
                $options['clientPassword'],
                $options['username'],
                $options['domain']
            );
        if ($response->status)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' => $response->message
                ]);
        return $response->exists == 'true';
    }


    /**
     * Deletes the specified mailbox
     *
     * @param array $options The options array; supports the following options:
     *   clientUsername - The username of the webservice client
     *   clientPassword - The password of the webservice client
     *   username - The first component of the mailbox, i.e., the part before
     *    the "@" in the email address
     *   domain - The domain of the mailbox
     * @return boolean true unless an exception is thrown
     * @throws CodeRage\Error
     */
    public function deleteMailbox(array $options)
    {
        $this->processOptions($options);
        $client = $this->soapClient();
        $response =
            $client->deleteMailbox(
                $options['clientUsername'],
                $options['clientPassword'],
                $options['username'],
                $options['domain']
            );
        if ($response->status)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' => $response->message
                ]);
        return true;
    }

    /**
     * Validates and processes options for web service wrapper methods
     *
     * @throws CodeRage\Error
     */
    private function processOptions($options)
    {
        Args::checkKey($options, 'clientUsername', 'string', [
            'label' => 'client username',
            'required' => true
        ]);
        Args::checkKey($options, 'clientPassword', 'string', [
            'label' => 'client password',
            'required' => true
        ]);
        Args::checkKey($options, 'username', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'domain', 'string', [
            'required' => true
        ]);
    }

    /**
     * Returns a newly constructed SOAP client
     *
     * @return SoapClient
     */
    private function soapClient()
    {
        $context =
            stream_context_create([
                'ssl' =>
                    [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
            ]);
        $client =
            new \SoapClient(
                    null,
                    [
                        'location' => self::SERVIVE_ADDRESS,
                        'uri' => self::NAMESPACE_URI,
                        'stream_context' => $context
                    ]
                );
        return $client;
    }
}
