<?php

/**
 * Defines the class CodeRage\WebService\UrlChecker
 *
 * File:        CodeRage/WebService/UrlChecker.php
 * Date:        Fri Sep 9 19:01:10 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use stdClass;

/**
 * Makes a GET request and outputs information about the response
 */
class UrlChecker {

    /*
     * Takes the URL as argument and returning in instance of stdClass,
     * containing the following properties:
     *   status - The HTTP status code
     *   contentType - The content type of the respose, if available
     *   size - The length in the response body
     *   sha1 - A SHA1 hash of the response body
     *
     * @param string $URL
     * @return stdClass $urlInfo
     */
    function execute(array $input)
    {
        $urlInfo = new stdClass;
        $request = new HttpRequest($input['url']);
        $response = $request->submit(['throwOnError' => false]);
        $urlInfo->status = $response->status();
        $contentType = $response->contentType();
        if ($contentType !== null)
            $urlInfo->contentType = $contentType;
        $body = $response->body();
        $urlInfo->size = strlen($body);
        $urlInfo->sha1 = sha1($body);
        return $urlInfo;
    }
}
