<?php

/**
 * Defines the class CodeRage\Tool\Test\HarryPotterLister
 *
 * File:        CodeRage/Tool/Test/HarryPotterLister.php
 * Date:        Fri Feb 17 02:24:37 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

use CodeRage\Error;
use CodeRage\Tool\Offline;
use CodeRage\Util\Args;


/**
 * Tool for testing CodeRage\Tool\Offline
 */
final class HarryPotterLister extends \CodeRage\Tool\Tool {
    use Offline;

    /**
     * Regular expression matching dates of the form yyyy-mm-dd
     *
     * @var string
     */
    const MATCH_DATE = '/^\d{4}-\d{2}-\d{2}$/';

    /**
     * Harry potter novels, indexed by publication date
     *
     * @var array
     */
    const HARRY_POTTER_LIST =
        [
            '1997-06-26' => "Harry Potter and the Sorcerer's Stone",
            '1998-07-02' => "Harry Potter and the Chamber of Secrets",
            '1999-07-08' => "Harry Potter and the Prisoner of Azkaban",
            '2000-07-08' => "Harry Potter and the Goblet of Fire",
            '2003-06-21' => "Harry Potter and the Order of the Phoenix",
            '2005-07-16' => "Harry Potter and the Half-Blood Prince",
            '2007-07-21' => "Harry Potter and the Deathly Hallows"
        ];

    /**
     * Constructs a CodeRage\Tool\Test\HarryPotterLister
     *
     * @param array $options The options array
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Returns the list of Harry Potter novels published at the time of
     * execution, based on CodeRage\Util\Time
     *
     * @param array $options The options array; supports the following options:
     *     from - The begin date
     *     to - The end date
     * @param string $path The path of the file to which offline data should
     *   be written, in 'record' mode, and null otherwise
     * @return array
     */
    protected function doExecuteOnline($options, $path)
    {
        $from = $to = null;
        $novelList = self::HARRY_POTTER_LIST;
        if (isset($options['from'])) {
            $from = $options['from'];
            Args::check($from, 'string', 'beginning date');
            if (!preg_match(self::MATCH_DATE, $from))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid 'from' date"
                    ]);
            $from = strtotime($from);
        }
        if (isset($options['to'])) {
            $to = $options['to'];
            Args::check($to, 'string', 'ending date');
            if (!preg_match(self::MATCH_DATE, $to))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid 'to' date"
                    ]);
            $to = strtotime($to);
        }
        if ($from !== null && $to !== null && $from > $to)
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' => 'Invalid date range'
                ]);
        $time = \CodeRage\Util\Time::get();
        foreach ($novelList as $k => $v) {
            if (strtotime($k) > $time)
                unset($novelList[$k]);
        }

        if ($from !== null) {
            foreach ($novelList as $k => $v) {
                if (strtotime($k) < $from)
                    unset($novelList[$k]);
            }
        }
        if ($to !== null) {
            foreach ($novelList as $k => $v) {
                if (strtotime($k) > $to)
                    unset($novelList[$k]);
            }
        }
        $novelList = array_values($novelList);
        if ($path !== null)
            file_put_contents($path, json_encode($novelList));
        return $novelList;
    }

    /**
     * Parses the given JSON document and returns the result
     *
     * @param array $options The options array passed to execute()
     * @param string $path The path to the offline data file
     * @return array
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
        return 'data.json';
    }
}
