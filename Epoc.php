<?php

/**
 * Defines the interface Captcha\Provider\Epoc
 *
 * File:        src/Captcha/Provider/Epoc.php
 * Date:        Tue Jun  8 18:21:01 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace Captcha\Provider;

use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\WebServices\HttpRequest;

/**
 * Implements a demonstration version of the CM/ECF ePOC CAPTCHA implementation
 */
final class Epoc implements \Captcha\Provider {

    public function name(): string
    {
        return 'epoc';
    }

    public function title(): string
    {
        return 'CM/ECF ePOC CAPTCHA';
    }

    public function generateHtml(array $options = []): array
    {
        $images = Epoc\Images::images();
        $image = $images[random_int(0, count($images) - 1)];
        $url = Epoc\Images::targetUrl() . '/' . $image['filename'];
        $expected = sha1($image['text']);
        $random = random_int(1, 10000000000);
        return [
            'scripts' => '',
            'widget' =>
                "<p><img src='$url'>" .
                "<input type='hidden' name='expected' value='$expected'>" .
                "<input type='hidden' name='myrandom' value='$random'>" .
                "<input type='text' size='20' name='verifytext'>" .
                "&nbsp;Enter Verification Code <font color='#FF0000'>(required)</font></p>"
        ];
    }

    public function verify(array $postData, array $options = []): void
    {
        $expected =
            Args::checkKey($postData, 'expected', 'string', [
                'required' => true
            ]);
        $actual =
            Args::checkKey($postData, 'verifytext', 'string', [
                'required' => true
            ]);
        if (sha1($actual) !== $expected) {
            foreach (Epoc\Images::images() as $image) {
                if (sha1($image['text']) == $expected) {
                    $expected = $image['text'];
                    break;
                }
            }
            throw new
                \Exception(
                    "Failed validating CM/ECF ePOC CAPTCHA: expected " .
                    "'$expected'; found '$actual'"
                );
        }
    }
}
