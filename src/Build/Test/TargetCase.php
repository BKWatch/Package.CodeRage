<?php

/**
 * Defines the class CodeRage\Build\Test\TargetCase
 * 
 * File:        CodeRage/Build/Test/TargetCase.php
 * Date:        Tue Mar 17 11:19:04 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Test;

use CodeRage\Build\BuildConfigFile;
use CodeRage\Error;
use function CodeRage\Xml\firstChildElement;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');
require_once('CodeRage/Build/Tool/Packages.php');
require_once('CodeRage/File/rm.php');
require_once('CodeRage/Text/split.php');
require_once('CodeRage/Xml/firstChildElement.php');
require_once('CodeRage/Xml/getBooleanAttribute.php');
require_once('CodeRage/Xml/loadDom.php');

/**
 * Test case that parses and builds a project definition file.
 */
class TargetCase extends \CodeRage\Test\Case_ {

    /**
     * Path to a project definition file.
     *
     * @var string
     */
    private $path;

    /**
     * The list of IDs of targets to be built.
     *
     * @var string
     */
    private $targets;

    /**
     * true if the project defined by $path is expected to build
     * successfully.
     *
     * @var string
     */
    private $status;

    /**
     * Constructs a CodeRage\Build\Test\TargetCase
     *
     * @param string $path
     */
    function __construct($path)
    {
        $this->path = $path;
        $schema = dirname(__FILE__) . '/project.xsd';
        $dom = \CodeRage\Xml\loadDom($path, $schema);
        $doc = $dom->documentElement;
        $elt = firstChildElement($doc, 'info');
        if (!$elt)
            throw new Error(['message' => "Missing 'info' element in '$path'"]);
        $info = \CodeRage\Build\Info::fromXml($elt);
        parent::__construct($info->label(), $info->description());
        $targets = firstChildElement($doc, 'targets');
        $config = firstChildElement($targets, 'testConfiguration');
        $this->targets = $config->hasAttribute('targets') ?
            \CodeRage\Text\split($config->getAttribute('targets')) :
            [];
        $this->status = \CodeRage\Xml\getBooleanAttribute($config, 'status');
    }

    /**
     * Executes this test case.
     *
     * @return boolean
     */
    protected function doExecute($params)
    {
        \CodeRage\File\rm($this->projectRoot() . '/.coderage');
        $success = true;
        for ($z = 0; $z < 1; ++$z) {
            $run = $this->newRun();
            if ($this->status) {
                $success = $run->targets()->execute() && $success;
            } else {
                try {
                    $success = !$run->targets()->execute() && $success;
                } catch (\Throwable $e) {
                    echo "Caught exception $e";
                }
            }
        }
        return $success;
    }

    /**
     * Returns a newly constructed instance of CodeRage\Build\Run.
     *
     * @param CodeRage\Build\Run $run
     */
    private function newRun()
    {
        // Create dummy system configuration file
        $ns = \CodeRage\Build\NAMESPACE_URI;
        $sysConfig = \CodeRage\File\temp('', 'xml');
        \CodeRage\File\generate($sysConfig, "<project xmlns='$ns'/>", 'xml');

        // Create command line
        $cmd = new \CodeRage\Build\CommandLine;
        $cmd->parse(false, ['program', '--log-level-console', 'DEBUG']);

        // Create log
        $log = $cmd->createLog($this->projectRoot());

        // Create build configuration
        $config = \CodeRage\Config::current();
        $buildConfig =
            new \CodeRage\Build\BuildConfig(
                    time(), 'build', null, null, null,
                    $config->getRequiredProperty('tools_root'), null,
                    new BuildConfigFile($sysConfig),
                    new BuildConfigFile($this->path),
                    [], null, null, null, null, null,
                    [], [], null
                );

        // Create run
        $run =
            new \CodeRage\Build\Run(
                    $this->projectRoot(), $cmd, $log,
                    new \CodeRage\Build\NullDialog, $buildConfig,
                    new \CodeRage\Build\Config\Basic([]), $this->targets
                );

        // Configure test package manager
        \CodeRage\Build\Packages\Test\Manager::initialize($run, $this->path);

        return $run;
    }

    /**
     * Returns the project root used by the test run.
     *
     * @return string
     */
    private static function projectRoot()
    {
        return dirname(__FILE__) . '/Project';
    }
}
