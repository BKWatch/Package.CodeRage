<?php

/**
 * Defines the class CodeRage\Tool\Test\OfflineTool
 *
 * File:        CodeRage/Tool/Test/OfflineTool.php
 * Date:        Fri Feb 17 02:24:37 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

use CodeRage\Tool\Offline;

/**
 * Tool for testing the Offline.php
 */
final class OfflineTool extends \CodeRage\Tool\Tool {
    use Offline;

    /**
     * Constructs a \CodeRage\Tool\Test\OfflineTool
     *
     * @param array $options The options array
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    protected function doExecuteOnline($options, $path)
    {
        return '';
    }

    /**
     * Parses the given JSON file and returns the array.
     *
     * @param array $options
     * @param string $path
     * @throws CodeRage\Error
     */
    protected function doExecuteOffline($options, $path)
    {
        $result = json_decode(file_get_contents($path));
        if ($result === false)
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => 'Invalid json list: JSON-encoding error'
                ]);
        return $result;
    }

    /**
     * Retrurns the directory name by sorting options array by name and
     * concatenating the URL-encoded names and values as
     * NAME1=VALUE1;NAME2=VALUE2
     *
     * @param array $options
     * @return string A directory Name
     */
    protected function encodeOfflineOptions($options)
    {
        if (empty($options))
            return ';';
        ksort($options);
        $encodedOptions = [];
        foreach ($options as $k => $v) {
            $encodedOptions[] = urlencode($k) . '=' . urlencode(strtoupper($v));
        }
        return join(';', $encodedOptions);
    }

    protected function offlineFileName($options)
    {
        return 'students.xml';
    }
}
