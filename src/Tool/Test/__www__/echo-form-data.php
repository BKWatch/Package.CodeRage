<?php

/**
 * Outputs JSON-encoded information about form data submitted in GET and POST
 * requests
 *
 * File:        CodeRage/Tool/Test/__www__/echo-form-data.php
 * Date:        Thu Apr  5 11:02:49 UTC 2018
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2018 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

require __DIR__ . '/../../../../vendor/autoload.php';

use CodeRage\Text\Regex;
use CodeRage\Tool\Test\MockResponse;

/**
 * @var string
 */
const MATCH_MULTIPART = '/^multipart\/form-data\b/';

/**
 * @var string
 */
const MATCH_URL_ENCODED = '/^application\/x-www-form-urlencoded\b/';

/**
 * @var array
 */
const HIDDEN_QUERY_PARAMETERS = ['XDEBUG_SESSION_START' => 1];

function handleRequest()
{
    try {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == 'GET') {
            handleGetFormData();
        } elseif ($method == 'POST') {
            if (!isset($_SERVER['HTTP_CONTENT_TYPE']))
                throw new Exception('Missing Content-Type header');
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
            $body = file_get_contents('php://input');
            if (preg_match(MATCH_MULTIPART, $contentType)) {
                handleMultiPartFormData($body);
            } elseif (preg_match(MATCH_URL_ENCODED, $contentType)) {
                handleUrlEncodedFormData($body);
            } else {
                throw new Exception("Unsupported content type: $contentType");
            }
        } else {
            httpError(
                400, 'Bad Request',
                "Invalid request method: expected GET or POST; found '$method'"
            );
        }
    } catch (Throwable $e) {
        httpError(500, 'Internal Server Error', $e->getMessage());
    }
}

function handleGetFormData()
{
    $formData = responseMetadata();
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $formData['formData'] = parseQueryString($query);
    outputResponse($formData);
}

function handleUrlEncodedFormData($body)
{
    $formData = responseMetadata();
    $formData['formData'] = parseQueryString($body);
    outputResponse($formData);
}

function handleMultiPartFormData($body)
{
    $formData = responseMetadata();

    // Parse body
    $body = "Content-Type: {$_SERVER['HTTP_CONTENT_TYPE']}\n\n$body";
    $mailParser = new ZBateson\MailMimeParser\MailMimeParser();
    $message = $mailParser->parse($body);

    // Iterate over message parts
    foreach ($message->getChildParts() as $att) {
        $cdHeader = $att->getHeader('Content-Disposition');
        if ($cdHeader === null)
            httpError(
                400, 'Bad Request', "Missing 'Content-Disposition' header"
            );

        // Get content
        $content = $att->getContent();

        // Get name
        $name = $cdHeader->getValueFor('name');

        // Add parts to output
        if ($cdHeader->hasParameter('fileName')) {
            $formData['formData'][] =
                [
                    'name' => $name,
                    'contentType' => $att->getHeaderValue('Content-Type'),
                    'fileName' => $cdHeader->getValueFor('fileName'),
                    'size' => strlen($content),
                    'sha1' => sha1($content)
                ];
        } else {
            $formData['formData'][] =
                [
                    'name' => $name,
                    'value' => $content
                ];
        }
    }
    outputResponse($formData);
}

/**
 * Returns an array with keys among 'requestMethod', 'contentType', 'headers',
 * and 'cookies'
 */
function responseMetadata()
{
    $headers = $cookies = [];
    foreach (getallheaders() as $name => $value)
       $headers[$name] = $value;
    foreach ($_COOKIE as $name => $value)
        $cookies[] = ['name' => $name, 'value' => $value];
    $metadata =
        [
            'requestMethod' => $_SERVER['REQUEST_METHOD'],
            'headers' => $headers
        ];
    if ( isset($_SERVER['HTTP_CONTENT_TYPE']) &&
         $_SERVER['HTTP_CONTENT_TYPE'] != '' )
    {
        $metadata['contentType'] =
            trim(Regex::match('/^[^;]+/', $_SERVER['HTTP_CONTENT_TYPE'], 0));
    }
    if (!empty($cookies))
        $metadata['cookies'] = $cookies;
    return $metadata;
}

function parseQueryString($query)
{
    $params = [];
    $parts = $query != '' ? explode('&', $query) : [];
    foreach ($parts as $p) {
        $n = $v = null;
        if (($pos = strpos($p, '=')) !== false) {
            $n = unescapeUrl(substr($p, 0, $pos));
            $v = unescapeUrl(substr($p, $pos + 1));
        } else {
            $n = unescapeUrl($p);
            $v = '';
        }
        if (array_key_exists($n, HIDDEN_QUERY_PARAMETERS))
            continue;
        $params[] = ['name' => $n, 'value' => $v];
    }
    return $params;
}

function unescapeUrl($str)
{
    return preg_match(MATCH_URL_ENCODED, $_SERVER['HTTP_CONTENT_TYPE']) ?
        urldecode($str) :
        rawurldecode($str);
}

function outputResponse(array $formData)
{
    header('Content-Type: application/json');
    $output = json_encode($formData, JSON_PRETTY_PRINT);
    if ($output === false)
        throw new Execption("JSON encoding error");
    echo $output;
}

function httpError($status, $title, $message)
{
    header("HTTP/1.1 $status $title");
    header('Content-Type: ' . MockResponse::TEXT_HTML_UTF8);

    // Output response
    $title = htmlentities($title);
    $message = htmlentities($message);
    $html =
        "<!DOCTYPE html>
         <html>
           <head><title>$status $title</title></head>
           <body>
             <h1>$status $title</h1>
             <p>$message</p>
           </body>
         </html>";
    echo $html;
    exit;
}

handleRequest();
