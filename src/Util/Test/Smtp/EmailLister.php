<?php

/**
 * Defines the class CodeRage\Util\EmailLister
 *
 * File:        CodeRage/Util/Test/Smtp/EmailLister.php
 * Date:        Thu Aug 24 14:32:53 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CodeRage
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test\Smtp;

use CodeRage\Error;

/**
 * Checks an email acccount and lists the new messages, using the PHP IMAP
 * extension
 */
class EmailLister extends \CodeRage\Tool\Tool {

    /**
     * Fetches email messages from the specified account and returns an array of
     * array with keys 'text', 'html', 'attachments' and 'inlines'; Where 'text'
     * and 'html' contains text and html content repectively and 'attachments'
     * and 'inlines' are list of array with keys 'type', 'filename' and
     * 'content'
     *
     * @param array $options The options array; supports the following options:
     *   host - The host
     *   port - The port
     *   email - The email address
     *   password - The password
     * @return array
     */
    public function doExecute(array $options)
    {
        $this->conn =
            imap_open(
                "{{$options['host']}:{$options['port']}/novalidate-cert/pop3/ssl}",
                $options['username'],
                $options['password'],
                OP_SILENT
            );
        if (!$this->conn)
            throw new
                Error([
                    'status' => 'SMTP_ERROR',
                    'details' => imap_last_error()
                ]);
        $messageCount = imap_num_msg($this->conn);
        if ($messageCount == 0)
            return [];
        $messages = [];
        for ($i = 1; $i <= $messageCount; $i++) {
            $messages[$i]['headers'] = imap_fetchbody($this->conn, $i, '0');
            $structure = imap_fetchstructure($this->conn, $i);
            if ($structure->type == TYPETEXT) {
                if ($structure->subtype == 'HTML')
                    $messages[$i]['html'] =
                        imap_fetchbody($this->conn, $i, '1');
                if ($structure->subtype == 'PLAIN')
                    $messages[$i]['text'] =
                        imap_fetchbody($this->conn, $i, '1');
            }
            $data = [];
            if (isset($structure->parts)) {
                $this->parse($structure->parts, $i, $data, '');
                if (isset($data['inlines']))
                    $messages[$i]['inlines'] = $data['inlines'];
                if (isset($data['attachments']))
                    $messages[$i]['attachments'] = $data['attachments'];
                if (isset($data['html']))
                    $messages[$i]['html'] = $data['html'];
                if (isset($data['text']))
                    $messages[$i]['text'] = $data['text'];
            }
        }
        return array_values($messages);
    }

    /**
     * Parse array of email parts and returns an array with keys 'text',
     * 'html', 'attachments' and 'inlines'; Where 'text' and 'html' contains
     * text and html content repectively and 'attachments' and 'inlines' are
     * list of array with keys 'type', 'filename' and 'content'
     *
     * @param stdClass $messageParts
     * @param int $index A number of email
     * @param array $data An array containing of email parts
     * @param string $section A dot seperated numbers indicating the position of
     *   email part in hierarchy
     */
    private function parse($messageParts, $index, &$data, $section)
    {
        $partNumber = 1;
        foreach ($messageParts as $part) {
            $type = $this->contentType($part->type, $part->subtype);
            $subSection = $section === '' ?
                $partNumber :
                "$section.$partNumber";
            if (isset($part->parts))
                $this->parse($part->parts, $index, $data, $subSection, 0);
            if (isset($part->disposition)) {
                $content = imap_fetchbody($this->conn, $index, "$subSection");
                if (!preg_match('/audio|image|video/', $type))
                    $content = $this->decodeContent($part->encoding, $content);
                $filename = null;
                if (isset($part->dparameters)) {
                    foreach ($part->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $filename = $object->value;
                            break;
                        }
                    }
                }
                $properties =
                    [
                        'type' => $type,
                        'filename' => $filename,
                        'content' => $content
                    ];
                if ($part->disposition == 'ATTACHMENT') {
                   $data['attachments'][] = $properties;
                } elseif($part->disposition == 'INLINE') {
                    $data['inlines'][] = $properties;
                }
            } else {
                $content = imap_fetchbody($this->conn, $index, "$subSection");
                $content = $this->decodeContent($part->encoding, $content);
                if ($part->subtype == 'HTML') {
                   $data['html']= $content;
                } elseif($part->subtype == 'PLAIN') {
                    $data['text'] = $content;
                }
            }
            $partNumber++;
        }
    }

    /**
     * Returns a MIME media type
     *
     * @param int $primary The primary type, represented as the value of one of
     *   the TYPEXXX constants defined in the IMAP extension
     * @param string $sub The subtype, in all caps
     */
    private function contentType($primary, $sub)
    {
        $primary = $this->translatePrimaryType($primary);
        $type = "$primary/" . strtolower($sub);
        return $type;
    }

    /**
     * Returns a primary media type
     *
     * @param int $primary The primary type, represented as the value of one of
     *   the TYPEXXX constants defined by the IMAP extension
     * @return string
     * @throws CodeRage\Error
     */
    private function translatePrimaryType($primary)
    {
        switch ($primary) {
        case TYPETEXT:
            return 'text';
        case TYPEMULTIPART:
            return 'multipart';
        case TYPEMESSAGE:
            return 'message';
        case TYPEAPPLICATION:
            return 'application';
        case TYPEAUDIO:
            return 'audio';
        case TYPEIMAGE:
            return 'image';
        case TYPEVIDEO:
            return 'video';
        case TYPEMODEL:
            return 'model';
        case TYPEOTHER:
            return 'other';
        default:
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' => "Invalid primary media type code: $primary"
                ]);
        }
    }

    /**
     * Returns the decoded content
     *
     * @param int $encoding The encoding of the content
     * @param string $content The encoded string
     * @return string The decoded string
     * @throws CodeRage\Error
     */
    private function decodeContent($encoding, $content)
    {
        switch ($encoding) {
        case ENC7BIT:
        case ENC8BIT:
        case ENCBINARY:
            return quoted_printable_decode($content);
        case ENCBASE64:
            return base64_decode($content);
        case ENCQUOTEDPRINTABLE:
            return quoted_printable_decode($content);
        case ENCOTHER:
        default:
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' => "Unsupported data encoding"
                ]);
        }
    }

    /**
     * The connection
     *
     * @var string
     */
    private $conn;
}
