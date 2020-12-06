<?php

/**
 * Defines the trait CodeRage\Tool\Offline
 *
 * File:        CodeRage/Tool/Offline.php
 * Date:        Wed Feb  8 23:07:26 UTC 2017
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;

use DOMDocument;
use DateTime;
use DateTimeZone;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Args;
use CodeRage\Util\Time;
use CodeRage\Xml;
use CodeRage\Xml\ElementCreator;


/**
 * Trait providing an implementation of doExecute() for use by subclasses of
 * CodeRage\Tool\Tool to support offline execution
 */
trait Offline {
    use ElementCreator;

    protected final function doExecute(array $options)
    {
        if ($this->offlineMode() == 'offline') {
            $path = $this->offlineDataFile($options);
            return $this->processOfflineData($options, $path);
        } elseif ($this->offlineMode() == 'online') {
            return $this->doExecuteOnline($options, null);
        } else {

            // Create directory
            list ($root, $tool, $opts) = $this->offlineFilesDirectory($options);
            $dir = "$root/$tool/$opts";
            File::mkdir($dir);

            // Create file to write data to
            $timestamp =
                (new DateTime(null, new DateTimeZone('UTC')))
                    ->setTimestamp(
                          $this->config()->getProperty(
                              'coderage.tool.offline.timestamp',
                              Time::get()
                          )
                      )
                    ->format('Y-m-d-H.i.s');
            $file = $timestamp . '-' . $this->offlineFileName($options);

            // Run tool in record mode
            try {
                $result = $this->doExecuteOnline($options, "$dir/$file");
                if (!file_exists("$dir/$file"))
                    throw new
                        Error([
                            'status' => 'OBJECT_DOES_NOT_EXIST',
                            'details' =>
                                "No offline data recorded at $tool/$opts/$file"
                        ]);
                return $result;
            } catch (\Throwable $e){
                if (!is_writable($dir))
                    throw new
                        Error([
                            'status' => 'FILESYSTEM_ERROR',
                            'details' =>
                                "Failed creating offline error document: " .
                                "can't write to directory '$dir'"
                        ]);
                $wrapped = Error::wrap($e);
                $dom = new DOMDocument('1.0', 'UTF-8');
                $error = $this->createElement($dom, 'error');
                $this->appendElement($error, 'status', $wrapped->status());
                $this->appendElement($error, 'message', $wrapped->message());
                if ($wrapped->details() != $wrapped->message())
                    $this->appendElement($error, 'details', $wrapped->details());
                $dom->appendChild($error);
                $dom->save("$dir/$timestamp-error.xml");
                throw $e;
            }
        }
    }

    /**
     * Returns the result of executing in online mode with the given options
     *
     * @param array $options The options array passed to doExecute()
     * @param string $path The pathname of the file to which the data needed to
     *   execute in offline mode should be written
     * @return mixed
     */
    protected abstract function doExecuteOnline($options, $path);

    /**
     * Returns the result of executing in offline mode with the given options
     * and the given data
     *
     * @param array $options The options array passed to doExecute()
     * @param string $path The pathname of the file containing the data needed
     *   to execute in offline mode
     * @return mixed
     */
    protected abstract function doExecuteOffline($options, $path);

    /**
     * Returns the file name of the directory containing the data needed to
     * execute in offline mode with the specified options
     *
     * @param array $options The options array passed to doExecute()
     * @return string The simple file name, without directory separators
     */
    protected abstract function encodeOfflineOptions($options);

    /**
     * Returns the name of the file to which data should be written in 'record'
     * mode
     *
     * @param $options The options array passed to doExecute()
     * @return string The file name
     */
    protected abstract function offlineFileName($options);

    /**
     * Returns the result of escaping the characters in the given string
     * using the encoding schema for URIs for all chacters not matching the
     * the regular expression character class '[^A-Za-z0-9\-\._;,=]'
     *
     * @param string $value
     * @return string
     */
    protected final function urlEncode($value)
    {
        return preg_replace_callback(
                    '/[^A-Za-z0-9\-\._;,=\[\] ]/',
                    function($c) { return '%' . sprintf('%02x', ord($c[0])); },
                    $value
               );
    }

    /**
     * Returns the current offline mode
     *
     * @param array $options The options array passed to doExecute()
     * @return string One of the values 'online', 'offline', and 'record'
     */
    private function offlineMode($options = [])
    {
        $mode =
            $this->config()->getProperty(
                'coderage.tool.offline.mode', 'online'
            );
        if ($mode !== 'online' && $mode !== 'offline' && $mode !== 'record')
            throw new
                Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'details' => "Invalid offline mode: $mode"
                ]);
        return $mode;
    }

    private function processOfflineData($options, $path)
    {
        $errorFile = '/(\d{4}-\d{2}-\d{2}-\d{2}\.\d{2}\.\d{2})-error\.xml/';
        if (preg_match($errorFile, $path)) {
            $dom = Xml::loadDocument($path);
            $elt = $dom->documentElement;
            if ($elt === null || $elt->localName != 'error')
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' => "Failed parsing $path"
                    ]);
            $status = Xml::firstChildElement($elt, 'status');
            if ($status === null)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' =>
                            "Failed parsing $path; Missing error status"
                    ]);
            $message = Xml::firstChildElement($elt, 'message');
            if ($message === null)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' =>
                            "Failed parsing $path; Missing error message"
                    ]);
            $details = Xml::firstChildElement($elt, 'details');
            throw new
                Error([
                    'status' => Xml::textContent($status),
                    'message' => Xml::textContent($message),
                    'details' => $details !== null ?
                        Xml::textContent($details) :
                        null
                ]);

        } else {
            return $this->doExecuteOffline($options, $path);
        }
    }

    /**
     * Returns the pathname of the file containing the data needed to
     * execute in offline mode with the specified options at the current time
     *
     * @param array $options The options array passed to doExecute()
     * @throws Error
     */
    private function offlineDataFile($options)
    {
        // Locate directory containing offline data files
        list ($root, $tool, $opts) = $this->offlineFilesDirectory($options);
        $dir = "$root/$tool/$opts";
        if (!file_exists($dir))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'message' => 'No data available',
                    'details' =>
                        "No offline data available at $tool/$opts: " .
                        "directory does not exist"
                ]);
        if (!is_dir($dir))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'message' => 'No data available',
                    'details' =>
                        "No offline data available at $tool/$opts: " .
                        "file is not a directory"
                ]);


        // Calculate current timestamp
        $date = new DateTime(null, new DateTimeZone('UTC'));
        $date->setTimestamp(Time::get());
        $now = $date->format('Y-m-d-H.i.s');

        // Search for offline data file
        $it = new \FilesystemIterator($dir);
        $files = [];
        $matchTimestamp = '/^(\d{4}-\d{2}-\d{2}-\d{2}\.\d{2}\.\d{2})-/';
        foreach ($it as $path => $info) {
            $filename = $info->getFilename();
            $match = null;
            if ( preg_match($matchTimestamp, $filename, $match) &&
                 $match[1] <= $now )
            {
                $files[$match[1]] = $path;
            }
        }
        if (empty($files))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'message' => 'No data available',
                    'details' =>
                        "No offline data available at $tool/$opts for " .
                        "timestamp $now"
                ]);

        // Return file with most recent timestamp
        ksort($files);
        return end($files);
    }

    /**
     * Returns a triple [$root, $tool, $opts] consisting of the components of
     * the path to the directory containing offline data files. Here $root is
     * the offline data directory, $tool is formed from the components of the
     * tool's class name, and $opts is the name of the directory containing
     * containing offline data files.
     *
     * @param array $options
     * @throws CodeRage\Error
     * @return array
     */
    private function offlineFilesDirectory($options)
    {
        $root =
            $this->config()->getRequiredProperty(
                'coderage.tool.offline.data_directory'
            );
        if (!file_exists($root))
            throw new
                Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'details' => "No such directory: $root"
                ]);
        if (!is_dir($root))
            throw new
                Error([
                    'status' => 'CONFIGURATION_ERROR',
                    'details' => "The file '$root' is not a directory"
                ]);
        $tool = str_replace('\\', '/', get_class($this));
        $opts = $this->encodeOfflineOptions($options);
        Args::check($opts, 'string', 'encoded options');
        if ($opts == '')
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'details' => 'Expected non empty string of encoded options'
                ]);
        return [$root, $tool, $opts];
    }
}
