<?php

/**
 * Contains the definition of the class CodeRage\Test\Diff
 *
 * File:        CodeRage/Test/Diff.php
 * Date:        Wed Mar 14 06:38:17 MDT 2007
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Args;
use CodeRage\Util\Os;
use CodeRage\Util\XmlEncoder;


/**
 * Formats a diff between two native data structures
 */
final class Diff {

    /**
     * constructs a CodeRage\Test\Diff
     *
     * @param mixed $lhs A native data structure, i.e., a value composed from
     *   scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param mixed $rhs A native data structure, i.e., a value composed
     *   from scalars using indexed arrays, associative arrays, and instances of
     *   stdClass
     * @param array $options The options array; supports the following options:
     *     xmlEncoder - An instance of CodeRage\Util\NativeDataEncoder
     *     localName - The top-level element name in the XML encoding
     */
    public function __construct($lhs, $rhs, array $options = [])
    {
        $xmlEncoder =
            Args::checkKey($options, 'xmlEncoder', 'CodeRage\Util\XmlEncoder', [
                'label' => 'XML encoder'
            ]);
        if ($xmlEncoder === null)
            $xmlEncoder = new XmlEncoder(['listElements' => ['*' => 'item']]);
        $localName =
            Args::checkKey($options, 'localName', 'string', [
                'label' => 'local name',
                'default' => 'object'
            ]);
        $this->lhs = $lhs;
        $this->rhs = $rhs;
        $this->xmlEncoder = $xmlEncoder;
        $this->localName = $localName;
    }

    /**
     * Formats this instance as a string
     *
     * @param array $options The options array; supports the following options
     *     throwOnError - throw an exception instead of returning null if an
     *       error occurs; defaults to true
     * @return string
     * @throws CodeRage\Error
     */
    public function format(array $options = [])
    {
        $throwOnError =
            Args::checkKey($options, 'throwOnError', 'boolean', [
                'label' => 'throwOnError flag',
                'default' => true
            ]);
        try {

            // Serialize $lhs
            $lTemp = File::temp();
            $lDom = new \DOMDocument;
            $lDom->formatOutput = true;
            $lDom->appendChild($this->xmlEncoder->encode($this->localName, $this->lhs, $lDom));
            $lDom->save($lTemp);

            // Serialize $rhs
            $rTemp = File::temp();
            $rDom = new \DOMDocument;
            $rDom->formatOutput = true;
            $rDom->appendChild($this->xmlEncoder->encode($this->localName, $this->rhs, $rDom));
            $rDom->save($rTemp);

            // Run diff
            $status = null;
            $output =
                Os::run('diff -u ' . escapeshellarg($lTemp) . ' ' .
                    escapeshellarg($rTemp) . ' 2>&1', $status);
            if ($status != 0 && $status != 1)
                throw new
                    Error([
                        'status' => 'INTERNAL_ERROR',
                        'details' =>
                            "Failed executing diff (exit status $status) " .
                            "(output '$output')"
                    ]);

             return $output;
        } catch (Throwable $e) {
            if ($throwOnError)
                throw $e;
            return null;
        }
    }

    /**
     * @var mixed
     */
    private $lhs;

    /**
     * @var mixed
     */
    private $rhs;

    /**
     * @var CodeRage\Util\XmlEncoder
     */
    private $xmlEncoder;

    /**
     * @var string
     */
    private $localName;
}
