<?php

/**
 * Defines the class CodeRage\Xml\Test\XsltProcessorSuite
 *
 * File:        CodeRage/Xml/Test/XsltProcessorSuite.php
 * Date:        Sat Aug 6 02:43:17 UTC 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2016 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Xml\Test;

use CodeRage\Error;
use CodeRage\File;
use CodeRage\Test\Assert;
use CodeRage\Xml;
use CodeRage\Xml\XsltProcessor;


/**
 * Test suite for the class CodeRage\Xml\Test\XsltProcessor
 */
class XsltProcessorSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Xml\Test\XsltProcessor
     */
    public function __construct()
    {
        parent::__construct(
            "XsltProcessor Test Suite",
            "Tests the class CodeRage\Xml\XsltProcessor"
        );
        $this->initializeTransformtions();
    }

    public function testStylesheetFromString()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $xsl = file_get_contents($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->loadStylesheetFromString($xsl);
        $processor->setParameter('testparam', 'TestTransformation');
        $xml = $processor->transformToString();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testStylesheetFromDoc()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $dom = Xml::loadDocument($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadStylesheetFromDoc($dom);
        $xml = $processor->transformToString();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testStylesheetFromFile()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadSourceFromFile($test['xml']);
        $processor->loadStylesheetFromFile($test['xsl']);
        $xml = $processor->transformToString();
        $transformed =
            file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testTransformToFile()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadSourceFromFile($test['xml']);

        // Create temporary output file
        $outputFile = File::temp();
        $processor->transformToFile($outputFile);
        $xml = file_get_contents($outputFile);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testTransformToDoc()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $dom = $processor->transformToDoc();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($dom->saveXml(), $transformed);
    }

    public function testTransformationVersion2()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test2'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $xml = $processor->transformToString();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testTransformXsltVersion2WithLibxsltFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('INTERNAL_ERROR');
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test2'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation', 'http://www.test.com');
        $xml = $processor->transformToString();
    }

    public function testRegisteredPhpFunction()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test3'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->registerPHPFunctions('preg_match');
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $xml = $processor->transformToString();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testRegisterPhpFunctionWithSaxonFailure1()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('UNSUPPORTED_OPERATION');
        $processor = new XsltProcessor;
        $processor->enableLibxslt(false);
        $processor->registerPHPFunctions('preg_match');
    }

    public function testNamespaceParameter()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation', 'http://www.test.com');
        $xml = $processor->transformToString();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testNamespaceParameterSaxonFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('UNSUPPORTED_OPERATION');
        $processor = new XsltProcessor;
        $processor->enableLibxslt(false);
        $processor->setParameter('testparam', 'TestTransformation', 'http://www.test.com');
    }

    public function testInvalidParameterNameWithSaxonFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('INTERNAL_ERROR');
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('', 'TestTransformation');
        $processor->transformToString();
    }

    public function testClearParameterWithLibxslt()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test4'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation', 'http://www.test.com');
        $processor->clearParameters();
        $transformed = file_get_contents($test['transformedXml']);
        $xml = $processor->transformToString();
        Assert::equivalentXmlData($xml, $transformed);
    }

    public function testClearParameterWithSaxon()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test4'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->clearParameters();
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
    }

    public function testLibararyNotFoundFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('UNSUPPORTED_OPERATION');
        $processor = new XsltProcessor;
        $processor->enableLibxslt(false);
        $processor->enableSaxon(false);
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $xml = $processor->transformToString();
    }

    public function testMissingOutputFileFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $outputFile = File::temp();
        unlink($outputFile);
        $processor->transformToFile($outputFile);
    }

    public function testOutputFileFormatFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $processor->setParameter('testparam', 'TestTransformation');
        $outputFile = File::tempDir();
        $processor->transformToFile($outputFile);
        unlink($outputFile);
    }

    public function testMissingSourceFailure()
    {
        $this->setExpectedException();
        $this->setExpectedStatusCode('STATE_ERROR');
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->transformToString();
    }

    public function testMultipleTransforamtionsLibxslt()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation', 'http://www.test.com');
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
        $test3 = $this->testTransformations['test3'];
        $processor->registerPHPFunctions('preg_match');
        $processor->loadSourceFromFile($test3['xml']);
        $processor->loadStylesheetFromFile($test3['xsl']);
        $transformed = file_get_contents($test3['transformedXml']);
        Assert::equivalentXmlData($processor->transformToDoc()->saveXml(), $transformed);
    }

    public function testMultipleSourceFileLibxslt()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation', 'http://www.test.com');
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
        $test5 = $this->testTransformations['test5'];
        $processor->loadSourceFromFile($test5['xml']);
        $transformed = file_get_contents($test5['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
    }

    public function testMultipleTransforamtionsToDocSaxon()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($processor->transformToDoc()->saveXml(), $transformed);
        $test2 = $this->testTransformations['test2'];
        $processor->loadSourceFromFile($test2['xml']);
        $processor->loadStylesheetFromFile($test2['xsl']);
        $transformed = file_get_contents($test2['transformedXml']);
        Assert::equivalentXmlData($processor->transformToDoc()->saveXml(), $transformed);
    }

    public function testMultipleTransforamtionsToStringSaxon()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
        $test2 = $this->testTransformations['test2'];
        $processor->loadSourceFromFile($test2['xml']);
        $processor->loadStylesheetFromFile($test2['xsl']);
        $transformed = file_get_contents($test2['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
    }

    public function testMultipleTransforamtionsToFileSaxon()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        $outputFile1 = File::temp();
        $processor->transformToFile($outputFile1);
        $xml1 = file_get_contents($outputFile1);
        Assert::equivalentXmlData($xml1, $transformed);
        $test2 = $this->testTransformations['test2'];
        $processor->loadSourceFromFile($test2['xml']);
        $processor->loadStylesheetFromFile($test2['xsl']);
        $transformed = file_get_contents($test2['transformedXml']);
        $outputFile2 = File::temp();
        $processor->transformToFile($outputFile2);
        $xml2 = file_get_contents($outputFile2);
        Assert::equivalentXmlData($xml2, $transformed);
    }

    public function testMultipleSourceFileSaxon()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test1'];
        $processor->setParameter('testparam', 'TestTransformation');
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
        $test5 = $this->testTransformations['test5'];
        $processor->loadSourceFromFile($test5['xml']);
        $transformed = file_get_contents($test5['transformedXml']);
        Assert::equivalentXmlData($processor->transformToString(), $transformed);
    }

    public function testXhtmlLibxslt()
    {
        $processor = new XsltProcessor;
        XsltProcessor::enableSaxon(false);
        $this->setExpectedException();
        $this->setExpectedStatusCode('INTERNAL_ERROR');
        $test = $this->testTransformations['test6'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        $processor->transformToDoc();
    }

    public function testXhtmlSaxon()
    {
        $processor = new XsltProcessor;
        $test = $this->testTransformations['test6'];
        $processor->loadStylesheetFromFile($test['xsl']);
        $processor->loadSourceFromFile($test['xml']);
        $transformed = file_get_contents($test['transformedXml']);
        Assert::equivalentHtmlData($processor->transformToDoc()->saveXml(), $transformed);
    }

    protected function componentInitialize($component)
    {
        XsltProcessor::enableLibxslt(true);
        XsltProcessor::enableSaxon(true);
    }

    /**
     * Initialize the array containing the different test xml, xsl and
     * transformed xml
     *
     * @throws Error
     */
    public function initializeTransformtions()
    {
        $this->testTransformations =
            [
                'test1' =>
                    [
                        'xsl' => __DIR__ . '/XsltProcessorSuite/test1.xsl',
                        'xml' => __DIR__ . '/XsltProcessorSuite/test1.xml',
                        'transformedXml' =>
                            __DIR__ . '/XsltProcessorSuite/transformedXml1.xml'
                    ],
                'test2' =>
                    [
                        'xsl' => __DIR__ . '/XsltProcessorSuite/test2.xsl',
                        'xml' => __DIR__ . '/XsltProcessorSuite/test2.xml',
                        'transformedXml' =>
                            __DIR__ . '/XsltProcessorSuite/transformedXml2.xml'
                    ],
               'test3' =>
                    [
                        'xsl' => __DIR__ . '/XsltProcessorSuite/test3.xsl',
                        'xml' => __DIR__ . '/XsltProcessorSuite/test3.xml',
                        'transformedXml' =>
                            __DIR__ . '/XsltProcessorSuite/transformedXml3.xml'
                    ],
               'test4' =>
                    [
                        'xsl' => __DIR__ . '/XsltProcessorSuite/test1.xsl',
                        'xml' => __DIR__ . '/XsltProcessorSuite/test1.xml',
                        'transformedXml' =>
                            __DIR__ . '/XsltProcessorSuite/transformedXml4.xml'
                    ],
               'test5' =>
                    [
                        'xsl' => __DIR__ . '/XsltProcessorSuite/test1.xsl',
                        'xml' => __DIR__ . '/XsltProcessorSuite/test2.xml',
                        'transformedXml' =>
                            __DIR__ . '/XsltProcessorSuite/transformedXml5.xml'
                    ],
               'test6' =>
                    [
                        'xsl' => __DIR__ . '/XsltProcessorSuite/testHtml1.xsl',
                        'xml' => __DIR__ . '/XsltProcessorSuite/testHtml1.xml',
                        'transformedXml' =>
                            __DIR__ . '/XsltProcessorSuite/transformedHtml1.xml'
                    ]
            ];
        foreach ($this->testTransformations as $test)
            foreach ($test as $path) {
                if (!file_exists($path)) {
                    throw new
                        Error([
                            'status' => 'OBJECT_DOES_NOT_EXIST',
                            'details' => "No such file: $path for testing"
                        ]);
                }
            }
    }

    /**
     *  List of paths to the xml file, its stylesheet and the transformed xml
     */
    private $testTransformations = [];
}
