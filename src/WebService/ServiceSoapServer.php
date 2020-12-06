<?php

/**
 * Defines the class CodeRage\WebService\ServiceSoapServer
 * 
 * File:        CodeRage/WebService/ServiceSoapServer.php
 * Date:        Sun Mar 20 13:25:04 MDT 2011
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use Exception;
use Throwable;
use CodeRage\Access\Session;
use CodeRage\Access\User;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Util\ConfigToken;
use CodeRage\Xml\XsltProcessor;

/**
 * @ignore
 */

/**
 * Implements the SOAP protocol for the class CodeRage\WebService\Service.
 */
class ServiceSoapServer {

    /**
     * An instance of CodeRage\WebService\Service
     *
     * @var CodeRage\WebService\Service
     */
    private $service;

    /**
     * The path to the service description
     *
     * @var string
     */
    private $wsdl;

    /**
     * The associative array of options to be passed to the SoapServer
     * constructor
     *
     * @var array
     */
    private $soapOptions;

    /**
     * Constructs an instance of CodeRage\WebService\ServiceSoapServer
     *
     * @param CodeRage\WebService\Service $service The underlying instance of
     *   CodeRage\WebService\Service
     * @param string $wsdl The path to the service description
     * @param array $soapOptions The associative array of options to be passed
     *   to the SoapServer constructor
     */
    public function __construct(
        Service $service, $wsdl, $soapOptions)
    {
        if (!isset($soapOptions['features']))
            $soapOptions['features'] = 0;
        $soapOptions['features'] |= SOAP_SINGLE_ELEMENT_ARRAYS;
        $this->service = $service;
        $this->wsdl = $wsdl;
        $this->soapOptions = $soapOptions;
    }

    /**
     * Returns the SOAP response, as a string.
     *
     * @param string $request The SOAP request
     * @return string The SOAP response
     */
    public final function handle($request)
    {
        ob_start();
        try {
            $server = new \SoapServer($this->wsdl, $this->soapOptions);
            $server->setObject($this);
            $server->handle($request);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $response = ob_get_contents();
        ob_end_clean();
        return $response;
    }

    /**
     * Implements a webservice operation XXX($input) by invoking
     * $service->execute('XXX', $input)
     */
    public function __call($method, $args)
    {
        if (!sizeof($args))
            throw new Error(['details' => "Missing input to operation '$method'"]);
        if (sizeof($args) > 1)
            throw new
                Error([
                    'message' =>
                        "Too many parameters to operation '$method'",
                    'details' => Error::formatValue($args)
                ]);
         $arg = $this->service->xmlEncoder($method)->fixSoapEncoding($args[0]);
         return $this->service->execute($method, $arg);
    }
}
