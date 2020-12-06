<?php

/**
 * Defines the class CodeRage\Tool\Robot\BrowserKitClient
 *
 * File:        CodeRage/Tool/Robot/BrowserKitClient.php
 * Date:        Sun Jan 14 20:43:12 UTC 2018
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 *
 * Adapted from Goutte (http://bit.ly/2mHYsNn) by Fabien Potencier, Michael
 * Dowling, and Charles Sarrazin, whicbh is distributed under the MIT license.
 * The original copyright notice and license is as follows:
 *
 * Copyright (c) 2010-2016 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CodeRage\Tool\Robot;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\BrowserKit\Client as BaseClient;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

/**
 * Subclass of Symfony\Component\BrowserKit\Client based on Goutte\Client
 */
class BrowserKitClient extends BaseClient
{
    protected $client;

    private $requestOptions = array();
    private $multipart = false;
    private $psrRequest;
    private $psrResponse;

    public function setClient(GuzzleClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        if (!$this->client) {
            $this->client = new GuzzleClient(array('allow_redirects' => false, 'cookies' => true));
        }

        return $this->client;
    }

    public function setRequestOptions(array $requestOptions)
    {
        $this->requestOptions = $requestOptions;
    }

    public function setMultipart($multipart)
    {
        $this->multipart = $multipart;
    }

    public function getPsrRequest()
    {
        return $this->psrRequest;
    }

    public function getPsrResponse()
    {
        return $this->psrResponse;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    protected function doRequest($request)
    {
        $this->psrResponse = null;
        $headers = array();
        foreach ($request->getServer() as $key => $val) {
            $key = strtolower(str_replace('_', '-', $key));
            $contentHeaders = array('content-length' => true, 'content-md5' => true, 'content-type' => true);
            if (0 === strpos($key, 'http-')) {
                $headers[substr($key, 5)] = $val;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (isset($contentHeaders[$key])) {
                $headers[$key] = $val;
            }
        }

        $cookies = CookieJar::fromArray(
            $this->getCookieJar()->allRawValues($request->getUri()),
            parse_url($request->getUri(), PHP_URL_HOST)
        );

        $requestOptions = array(
            'cookies' => $cookies,
            'allow_redirects' => false,
            'headers' => $headers,
            'connect_timeout' => 5
        );

        if (!in_array($request->getMethod(), array('GET', 'HEAD'))) {
            if (null !== $content = $request->getContent()) {
                $requestOptions['body'] = $content;
            } else {
                $files = $request->getFiles();
                if (!empty($files) || $this->multipart) {
                    $this->multipart = false;

                    ////////////////////////////////////////////////////////////
                    // Original Goutte code
                    ////////////////////////////////////////////////////////////

                    //$requestOptions['multipart'] = [];
                    //$this->addPostFields($request->getParameters(), $requestOptions['multipart']);
                    //$this->addPostFiles($files, $requestOptions['multipart']);

                    ////////////////////////////////////////////////////////////
                    // Replacement
                    ////////////////////////////////////////////////////////////

                    $multipart = [];
                    $this->addPostFields($request->getParameters(), $multipart);
                    $this->addPostFiles($files, $multipart);
                    $requestOptions['body'] =  new MultipartStream($multipart);
                    if (!isset($requestOptions['headers']))
                        $requestOptions['headers'] = [];
                    $requestOptions['headers']['Content-Type'] =
                        'multipart/form-data; boundary=' .
                        $requestOptions['body']->getBoundary();
                } else {
                    $requestOptions['form_params'] = $request->getParameters();
                }
            }
        }

        $method = $request->getMethod();
        $uri = $request->getUri();

        $options = $this->requestOptions;
        unset($options['headers']);
        $requestOptions += $options;
        if (isset($this->requestOptions['headers'])) {
            foreach ($this->requestOptions['headers'] as $n => $v) {
                $requestOptions['headers'][$n] = $v;
            }
        }


        // Let BrowserKit handle redirects
        try {
            $uri = new Uri($uri);
            $this->psrRequest =
                new PsrRequest(
                        $method,
                        $uri,
                        $headers,
                        isset($requestOptions['body']) ?
                            $requestOptions['body'] :
                            null,
                        '1.1'
                    );
            $this->psrResponse = $this->getClient()->request($method, $uri, $requestOptions);
        } catch (RequestException $e) {
            $this->psrResponse = $e->getResponse();
            if (null === $this->psrResponse) {
                throw $e;
            }
        }

        return $this->createResponse($this->psrResponse);
    }

    protected function addPostFiles(array $files, array &$multipart, $arrayName = '')
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $name => $info) {
            if (!empty($arrayName)) {
                $name = $arrayName.'['.$name.']';
            }

            $file = [
                'name' => $name,
            ];

            if (is_array($info)) {
                if (isset($info['tmp_name'])) {
                    if ('' !== $info['tmp_name']) {
                        $file['contents'] = fopen($info['tmp_name'], 'r');
                        if (isset($info['name'])) {
                            $file['filename'] = $info['name'];
                        }
                        if (isset($info['type'])) {
                            $file['headers'] =
                                ['Content-Type' => $info['type']];
                        }
                    } else {
                        continue;
                    }
                } else {
                    $this->addPostFiles($info, $multipart, $name);
                    continue;
                }
            } else {
                $file['contents'] = fopen($info, 'r');
            }

            $multipart[] = $file;
        }
    }

    public function addPostFields(array $formParams, array &$multipart, $arrayName = '')
    {
        foreach ($formParams as $name => $value) {
            if (!empty($arrayName)) {

                ////////////////////////////////////////////////////////////////
                // Original Goutte code
                ////////////////////////////////////////////////////////////////

                // $name = $arrayName.'['.$name.']';

                ////////////////////////////////////////////////////////////////
                // Replacement
                ////////////////////////////////////////////////////////////////

                $name = $arrayName;
            }

            if (is_array($value)) {
                $this->addPostFields($value, $multipart, $name);
            } else {
                $multipart[] = [
                    'name' => $name,
                    'contents' => $value,
                ];
            }
        }
    }

    protected function createResponse(ResponseInterface $response)
    {
        $body =
            !isset($this->requestOptions['sink']) ||
            preg_match('#^text/html\b#', $response->getHeaderLine('Content-Type')) ?
                (string) $response->getBody() :
                '';
        return new Response($body, $response->getStatusCode(), $response->getHeaders());
    }
}
