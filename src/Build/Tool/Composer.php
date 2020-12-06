<?php

/**
 * Defines the class CodeRage\Build\Tool\Error.
 *
 * File:        CodeRage/Build/Tool/Composer.php
 * Date:        Sun Dec 31 20:54:50 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Tool;

use CodeRage\Build\Info;
use CodeRage\Build\Run;
use CodeRage\Error;
use function CodeRage\File\checkReadable;
use function CodeRage\File\rm;
use function CodeRage\Xml\childElements;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');
require_once('CodeRage/File/find.php');
require_once('CodeRage/File/rm.php');
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/system.php');
require_once('CodeRage/Xml/childElements.php');
require_once('CodeRage/Xml/loadDom.php');

class Composer extends Basic {

    /**
     * @var string
     */
    const LABEL = 'Composer Dependency Manager';

    /**
     * @var string
     */
    const DESCRIPTION = 'Generates Composer configuration and runs Composer';

    /**
     * @var string
     */
    const COMPOSER_JSON = '.coderage.composer.json';

    /**
     * @var string
     */
    const COMPOSER_LOCK = '.coderage.composer.lock';

    /**
     * Constructs a CodeRage\Build\Tool\Composer
     */
    function __construct()
    {
        $info =
           new Info([
                   'label' => self::LABEL,
                   'description' => self::DESCRIPTION
               ]);
        parent::__construct($info);
    }

    /**
     * Returns true if $localName is 'composer' and $namespace is the
     * CodeRage.Build project namespace
     *
     * @param string $localName
     * @param string $namespace
     * @return boolean
     */
    function canParse($localName, $namespace)
    {
        return $localName == 'composer' &&
               $namespace = \CodeRage\Build\NAMESPACE_URI;
    }

    /**
     * Returns a target that when executed generates a Composer installation
     * and runs "composer install"
     *
     * @param CodeRage\Build\Run $run The current run of the build system
     * @param DOMElement $element
     * @param string $baseUri The URI for resolving relative paths referenced by
     *   $elt
     * @return CodeRage\Build\Target
     * @throws CodeRage\Error
     */
    function parseTarget(Run $run, \DOMElement $elt, $baseUri)
    {
        $cache = $this->cache($run);
        if (!isset($cache->require))
            $cache->require = $cache->repositiories = [];
        foreach (childElements($elt) as $child) {
            if ($child->localName == 'require') {
                foreach (childElements($child, 'package') as $package)
                    $cache->require[] =
                        $this->parsePackage($package, $baseUri);
            } elseif ($child->localName == 'repositories') {
                foreach (childElements($child, 'repository') as $repository)
                    $cache->repositories[] =
                        $this->parseRepository($repository, $baseUri);
            } else {
                throw new
                    Error([
                        'status' => 'CONFIGURATION_ERROR',
                        'message' =>
                            "Unrecognized child element '$child->localName' " .
                            "of 'composer' targets"
                    ]);
            }
        }
        return new
            \CodeRage\Build\Target\Callback(
                function() use($run) { return $this->generate($run); },
                null, [],
                new Info([
                       'label' => self::LABEL,
                       'description' => self::DESCRIPTION
                    ])
            );
    }

    /**
     * Runs composer install
     *
     * @param CodeRage\Build\Run $run The current run of the build system
     * @throws CodeRage\Error
     */
    function generate(Run $run)
    {
        $cache = $this->cache($run);
        if (isset($cache->done))
            return;
        $cache->done = true;

        // Generate composer.json configuration
        $config = $this->generateConfiguration($run);
        $configFilePath = $this->configFilePath($run);
        if (file_exists($configFilePath))
            checkReadable($configFilePath);
        if ( !file_exists($configFilePath) ||
             $config != file_get_contents($configFilePath) )
        {
            file_put_contents($configFilePath, $config);
        }

        // Replace lock file with public lock file
        $private = $this->lockFilePath($run);
        $public = $this->publicLockFilePath($run);
        $hasLock = file_exists($public);
        if (file_exists($private)) {
            checkReadable($private);
            rm($private);
        }
        if ($hasLock) {
            checkReadable($public);
            copy($public, $private);
        }

        // Run "composer install"
        $command =
            'COMPOSER=' . \CodeRage\Util\escapeShellArg($configFilePath) .
            ' composer install';
        \CodeRage\Util\system($command);

        // Move lock file to public location
        if ($hasLock) {
            rm($private);
        } else {
            rename($private, $public);
        }
    }

    /**
     * Parses the given "package" element
     *
     * @param DOMElement $elt An XML element with local name 'package'
     * @param string $baseUri The URI for resolving relative paths
     * @return stdClass An object with properties 'name', 'version', and
     *   'baseUri'
     * @throws CodeRage\Error
     */
    private function parsePackage($elt, $baseUri)
    {
        foreach (['name', 'version'] as $attr)
            if (!$elt->hasAttribute($attr))
                throw new
                    Error([
                        'status' => 'CONFIGURATION_ERROR',
                        'message' =>
                            "Missing '$attr' element on 'package' element in " .
                            "'composer' at $baseUri"
                    ]);
        return (object)
            [
                'name' => $elt->getAttribute('name'),
                'version' => $elt->getAttribute('version'),
                'baseUri' => $baseUri
            ];
    }

    /**
     * Parses the given "repository" element
     *
     * @param DOMElement $elt An XML element with local name 'repository'
     * @param string $baseUri The URI for resolving relative paths
     * @return stdClass An object with properties 'type', 'url', and 'baseUri'
      */
    private function parseRepository($elt, $baseUri)
    {

        foreach (['type', 'url'] as $attr)
            if (!$elt->hasAttribute($attr))
                throw new
                    Error([
                        'status' => 'CONFIGURATION_ERROR',
                        'message' =>
                            "Missing '$attr' element on 'repository' element " .
                            "in 'composer' target at $baseUri"
                    ]);
        return (object)
            [
                'type' => $elt->getAttribute('type'),
                'url' => $elt->getAttribute('url'),
                'baseUri' => $baseUri
            ];
    }

    /**
     * Generates a composer.json file
     *
     * @param CodeRage\Build\Run $run The current run of the build system
     * @return string The composer.json file, as a string containing JSON data
     */
    private function generateConfiguration(Run $run)
    {
        $cache = $this->cache($run);
        $require = [];
        foreach ($cache->require as $package) {
            if ( isset($require[$package->name]) &&
                 $package->version != $require[$package->name]->version )
            {
                $prev = $require[$package->name];
                throw new
                    Error([
                        'status' => 'CONFIGURATION_ERROR',
                        'message' =>
                            "Inconsistent version constraints for Composer " .
                            "package '$package->name': found " .
                            "'$package->version' at $package->baseUri but " .
                            "'$prev->version' at '$prev->baseUri'"
                    ]);
            }
            $require[$package->name] = $package;
        }
        foreach ($require as $name => $package)
            $require[$name] = $package->version;
        ksort($require);
        $config = (object)
            [
                'require' => $require,
                'repositories' => $cache->repositiories,
                'config' => (object)
                    [
                        'vendor-dir' => '.coderage/composer'
                    ]
            ];
        return json_encode($config, JSON_PRETTY_PRINT);
    }

    /**
     * Returns the path to composer.json
     *
     * @param CodeRage\Build\Run $run The current run of the build system
     * @return string
     */
    private function configFilePath(Run $run)
    {
        return $run->projectRoot() . '/.coderage/' . self::COMPOSER_JSON;
    }

    /**
     * Returns the path to composer.lock
     *
     * @param CodeRage\Build\Run $run The current run of the build system
     * @return string
     */
    private function lockFilePath(Run $run)
    {
        return $run->projectRoot() . '/.coderage/' . self::COMPOSER_LOCK;
    }

    /**
     * Returns the path to the public copy of composer.lock
     *
     * @param CodeRage\Build\Run $run The current run of the build system
     * @return string
     */
    private function publicLockFilePath(Run $run)
    {
        return $run->projectRoot() . '/' . self::COMPOSER_LOCK;
    }
}
