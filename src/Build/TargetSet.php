<?php

/**
 * Defines the class CodeRage\Build\TargetSet
 *
 * File:        CodeRage/Build/TargetSet.php
 * Date:        Fri Jan 09 09:45:21 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use DOMElement;
use Throwable;
use CodeRage\Log;
use CodeRage\Util\Factory;
use CodeRage\Xml;

/**
 * Represents a collection of targets, parsed from project definition files,
 * organized by dependency.
 */
class TargetSet {

    /**
     * Indicates that no additional processing can be performed.
     *
     * @var int
     */
    const STATE_DONE = 0;

    /**
     * Indicates that new tool definitions have been encountered.
     *
     * @var int
     */
    const STATE_NEW_TOOLS = 1;

    /**
     * Indicates that new targets have been encountered.
     *
     * @var int
     */
    const STATE_NEW_TARGETS = 2;

    /**
     * Indicates that targets have been successfully built.
     *
     * @var int
     */
    const STATE_TARGETS_BUILT = 4;

    /**
     * The current run of the build system.
     *
     * @var CodeRage\Build\Run
     */
    private $run;

    /**
     * The collection of registered instances of CodeRage\Build\Tool.
     *
     * @var array
     */
    private $tools = [];

    /**
     * An associative array mapping names of required targets to themselves.
     *
     * @var array
     */
    private $requiredTargets = [];

    /**
     * A collection of targets indexed by ID.
     *
     * @var array
     */
    private $knownTargets = [];

    /**
     * The collection of unparsed targets.
     *
     * @var array A list of DOMElement/URI pairs
     */
    private $unparsedTargets = [];

    /**
     * An array whose keys are IDs of unparsed targets.
     *
     * @var array
     */
    private $unparsedTargetIds = [];

    /**
     * The collection of required targets waiting to be built, indexed by ID.
     *
     * @var array
     */
    private $pendingTargets = [];

    /**
     * The collection of built targets, indexed by ID.
     *
     * @var array
     */
    private $builtTargets = [];

    /**
     * The collection of failed targets, indexed by ID.
     *
     * @var array
     */
    private $failedTargets = [];

    /**
     * Set to true after the preoject definition file, system-wide configuration
     * file, and additional configuration files are processed; after that,
     * warnings will be issued when a project definition file with a
     * configuration section is processed.
     *
     * @var boolean
     */
    private $frozen = false;

    /**
     * A bitwise OR of zero or more STATE_NEW_XXX constants.
     *
     * @var int
     */
    private $state = self::STATE_DONE;

    /**
     * Constructs an instance of CodeRage\Build\TargetSet.
     *
     * @param CodeRage\Build\Run $run
     * @param array $targets A list of target names.
     */
    function __construct(Run $run, $targets)
    {
        $this->run = $run;
        foreach ($targets as $t)
            $this->requiredTargets[$t] = $t;
    }

    /**
     * Returns the tool, if any, whose type is the named class.
     *
     * @param string $class
     */
    function getTool($class)
    {
        foreach ($this->tools as $t)
            if (strcasecmp(get_class($t), $class) == 0)
                return $t;
        return null;
    }

    /**
     * The number of successfully processed targets.
     *
     * @return int
     */
    function successCount()
    {
        return sizeof($this->builtTargets);
    }

    /**
     * The number of failed or unparsed targets.
     *
     * @return int
     */
    function failureCount()
    {
        $count = sizeof($this->requiredTargets) + sizeof($this->failedTargets);
        foreach ($this->unparsedTargets as $target) {

            // Targets without 'id' attributes are required
            list($elt) = $target;
            if (!$elt->hasAttribute('id'))
                ++$count;
        }
        return $count;
    }

    /**
     * Builds the underlying list of targets.
     */
    function execute()
    {
        // Add default target
        if ($this->run->projectConfig()) {
            $default = new Target\Default_($this->run->projectConfig());
            $default->execute($this->run);
        }

        // Process config files
        $config = $this->run->buildConfig();
        $this->processConfigFile(dirname(__FILE__) . '/../project.xml');
        if ($config->systemConfigFile())
            $this->processConfigFile($config->systemConfigFile()->path());
        foreach ($config->additionalConfigFiles() as $file)
            $this->processConfigFile($file->path());
        if ($config->projectConfigFile())
            $this->processConfigFile($config->projectConfigFile()->path());
        $this->frozen = true;

        // Loop until no further progress can be made
        while ($this->state != self::STATE_DONE) {
            $this->state = self::STATE_DONE;
            $this->parseTargets();
            $this->buildTargets();
        }

        // Summarize failures
        $failures =
            sizeof($this->requiredTargets) + sizeof($this->failedTargets);
        foreach ($this->requiredTargets as $label) {
            $message =
                "Failed building '$label': " .
                ( isset($this->unparsedTargetIds[$label]) ?
                      "can't parse target definition" :
                      "target definition missing" );
            $this->run->log()->logError($message);
        }
        foreach ($this->unparsedTargets as $target) {
            list($elt, $baseUri) = $target;
            if (!$elt->hasAttribute('id')) {
                $this->run->log()->logError(
                    "Failed building target '$elt->localName' at '$baseUri': " .
                    "can't parse target definition"
                );
                ++$failures;
            }
        }
        if ($str = $this->run->getStream(Log::INFO)) {
            $message = "Built " . sizeof($this->builtTargets) . " target(s)";
            if ($failures)
                $message .= "; failed building $failures target(s)";
            $str->write($message);
        }
        return $failures == 0;
    }

    /**
     * Processes the given project definition file.
     *
     * @param string $path The absolute pathname.
     */
    public function processConfigFile($path)
    {
        if (pathinfo($path, PATHINFO_EXTENSION) != 'xml')
            return;
        if (file_exists($path))
            $path = realpath($path);
        if ($str = $this->run->getStream(Log::VERBOSE))
            $str->write("Processing configuration file '$path'");
        $dom = Xml::loadDocument($path);
        $elt = $dom->documentElement;
        $namespace = NAMESPACE_URI;
        if ($elt->localName == 'config' && $elt->namespaceURI == $namespace) {
            if ( $this->frozen &&
                 ($str = $this->run->getStream(Log::WARNING)) )
            {
                $str->write(
                    "Project configuration already generated; ignoring " .
                    "configuration at '$path'"
                );
            }
        } elseif ( $elt->localName == 'project' &&
                   $elt->namespaceURI == $namespace )
        {
            foreach (Xml::childElements($elt) as $k) {
                if ($k->namespaceURI != $namespace) {
                    if ($str = $this->run->getStream(Log::ERROR))
                        $str->write(
                            "Unexpected element in XML configuration file: " .
                            "$elt->namespaceURI:$elt->localName"
                        );
                } elseif ($k->localName == 'include') {
                    $src = $inc->getAttribute('src');
                    $file = \CodeRage\File\find($src, dirname($path), false);
                    if (!$file) {
                        if ($str = $this->run->getStream(Log::WARNING)) {
                            $str->write(
                                "Missing file '$src' referenced in by " .
                                "'include' element in '$path'"
                            );
                        }
                    } else {
                        $this->processConfigFile($file);
                    }
                } elseif ($k->localName == 'config') {
                    if ($this->frozen &&
                        ($str = $this->run->getStream(Log::WARNING)))
                    {
                        $str->write(
                            "Project configuration already generated; " .
                            "ignoring configuration at '$path'"
                        );
                    }
                } elseif ($k->localName == 'tool') {
                    $this->loadTool($k, $path);
                } elseif ($k->localName == 'targets') {
                    foreach (Xml::childElements($k) as $tgt) {
                        $id = Xml::getAttribute($tgt, 'id');
                        $skip = false;
                        if ($id !== null) {
                            if ( isset($this->unparsedTargetIds[$id]) ||
                                 isset($this->knownTargets[$id]) ||
                                 isset($this->pendingTargets[$id]) ||
                                 isset($this->builtTargets[$id]) ||
                                 isset($this->failedTargets[$id]) )
                            {
                                $skip = true;
                                $this->run->log()->logError(
                                    "Duplicate target '$id' at '$path'; " .
                                    "ignoring target definition"
                                );
                            }
                        }
                        if (!$skip) {
                            $this->unparsedTargets[] = [$tgt, $path];
                            if ($id !== null)
                                $this->unparsedTargetIds[$id] = 1;
                            $this->state |= self::STATE_NEW_TARGETS;
                        }
                    }
                }
            }
        } else {
            if ($str = $this->run->getStream(Log::ERROR))
                $str->write(
                    "Invalid XML configuration file '$path': expected " .
                    "'$namespace:config' or '$namespace:project'; found " .
                    "'$elt->namespaceURI:$elt->localName'"
                );
        }
        if ($str = $this->run->getStream(Log::DEBUG))
            $str->write("Done processing configuration file '$path'");
    }

    /**
     * Adds the given target to the queue of targets awaiting processing.
     *
     * @param CodeRage\Build\Target $target
     */
    public function addTarget($target)
    {
        if ($str = $this->run->getStream(Log::VERBOSE))
            $str->write('Processing ' . self::printTarget($target, true));
        $wrapper = new TargetSetWrapper($target);
        $id = $wrapper->id();
        if ( isset($this->pendingTargets[$id]) ||
             isset($this->builtTargets[$id]) ||
             isset($this->failedTargets[$id]) )
        {
            return;
        }

        // Process dependenciess
        $failed = false;
        foreach ($wrapper->dependencies() as $dep) {
            if ( isset($this->pendingTargets[$dep]) ||
                 isset($this->builtTargets[$dep]) )
            {
                continue;
            } elseif (isset($this->failedTargets[$dep])) {
                $failed = true;
                $this->run->log()->logError(
                    "Failed building " . self::printTarget($target, true) .
                    ": failed dependency '$dep'"
                );
            } elseif (isset($this->knownTargets[$dep])) {
                $this->addTarget($this->knownTargets[$dep]);
            } else {
            if ($str = $this->run->getStream(Log::VERBOSE))
                $str->write("Adding dependency '$dep'");
                $this->requiredTargets[$dep] = $dep;
            }
        }

        if ($failed) {
            $this->failedTargets[$id] = $wrapper;
        } else {
            if ($str = $this->run->getStream(Log::VERBOSE))
                $str->write('Queuing ' . self::printTarget($target, true));
            unset($this->requiredTargets[$id]);
            $this->pendingTargets[$id] = $wrapper;
            $this->state |= self::STATE_NEW_TARGETS;
        }
    }

    /**
     * If no tool of the given type currently exists, adds the given tool to the
     * underlying list of build tools.
     *
     * @param CodeRage\Build\Tool $tool
     */
    public function addTool(Tool $tool)
    {
        foreach ($this->tools as $t)
            if (get_class($t) == get_class($tool))
                return;
        if ($str = $this->run->getStream(Log::VERBOSE))
            $str->write('Adding tool ' . get_class($tool));
        $this->tools[] = $tool;
        $this->state |= self::STATE_NEW_TOOLS;
    }

    /**
     * Parses the given tool definition.
     *
     * @param DOMElement $elt
     * @param string $baseUri The URI for resolving relative paths referenced by
     * $elt
     */
    private function loadTool(DOMElement $elt, $baseUri)
    {
        $class = $elt->getAttribute('class');
        $info = ($i = Xml::firstChildElement($elt, 'info')) ?
            Info::fromXml($i) :
            new Info;
        if ($str = $this->run->getStream(Log::VERBOSE))
            $str->write(
                "Parsing tool definition '$class' at '$baseUri'"
            );
        try {
            $options =
                [
                    'class' => $class,
                    'php' => $this->run->binaryPath()
                ];
            $tool = Factory::create($options);
            $tool->setInfo($info);
            $this->addTool($tool);
        } catch (Throwable $e) {
            if ($str = $this->run->getStream(Log::ERROR)) {
                $str->write(
                    "Failed loading tool '$class': $e"
                );
            }
        }
    }

    /**
     * Uses the underlying collection of build tools to generate targets
     * from XML elements.
     */
    private function parseTargets()
    {
        if ($str = $this->run->getStream(Log::VERBOSE))
            $str->write('Parsing targets');
        for ($z = sizeof($this->unparsedTargets) - 1; $z != -1; --$z) {
            list($elt, $uri) = $this->unparsedTargets[$z];
            $localName = $elt->localName;
            $namespace = $elt->namespaceURI;
            foreach ($this->tools as $tool) {
                if ($tool->canParse($localName, $namespace)) {
                    try {
                        $target = $this->parseTarget($tool, $elt, $uri);
                        $wrapper = new TargetSetWrapper($target);
                        if ($target->id() !== null)
                            unset($this->unparsedTargetIds[$target->id()]);
                        if ( $target->id() === null ||
                             isset($this->requiredTargets[$target->id()]) )
                        {
                            // Targets without 'id' attributes are required
                            $this->addTarget($wrapper);
                        } else {
                            if ($verb = $this->run->getStream(Log::VERBOSE))
                                    $verb->write(
                                        "Adding target '" . $target->id() .
                                        "' to list of parsed targets"
                                    );
                            $this->knownTargets[$target->id()] = $wrapper;
                        }
                        array_splice($this->unparsedTargets, $z, 1);
                    } catch (Throwable $e) {
                        $message =
                            "Failed parsing target '$namespace:" .
                            "$localName' at '$uri': $e";
                        $this->run->log()->logError($message);
                    }
                }
            }
        }
    }

    /**
     * Uses the specified build tool to parse the given target definition.
     *
     * @param CodeRage\Build\Tool $tool
     * @param DOMElement $elt
     * @param string $uri The URI for resolving relative path references.
     */
    private function parseTarget(Tool $tool, DOMElement $elt, $baseUri)
    {
        if ($str = $this->run->getStream(Log::VERBOSE)) {
            $str->write("Parsing target '$elt->localName' at '$baseUri'");
        } elseif ($str = $this->run->getStream(Log::INFO)) {
            $str->write("Parsing target '$elt->localName'");
        }
        $target = $tool->parseTarget($this->run, $elt, $baseUri);
        $target->setDefinition($elt);
        $target->setSource($baseUri);
        if ($info = Xml::firstChildElement($elt, 'info'))
            $target->setInfo(Info::fromXml($info));
        if ($id = $elt->getAttribute('id'))
            $target->setId($id);
        if ($deps = $elt->getAttribute('dependsOn'))
            $target->setDependencies(Text::split($deps));
        return $target;
    }

    /**
     * Attempts to build targets in the list of pending targets.
     */
    private function buildTargets()
    {
        // Create list of pending targets, sorted by dependency
        $pending = [];
        foreach ($this->pendingTargets as $t)
            $pending[] = $t;
        $callback = ['CodeRage\Build\TargetSet', 'compareTargets'];
        \CodeRage\Util\strictPreorderSort($pending, $callback);
        if (sizeof($pending))
            if ($str = $this->run->getStream(Log::VERBOSE))
                $str->write("Analyzing targets");

        // Build targets
        for ($z = 0, $n = sizeof($pending); $z < $n; ++$z) {
            $target = $pending[$z];
            $id = $target->id();
            $skip = false;
            foreach ($target->dependencies() as $dep) {
                if (isset($this->failedTargets[$dep])) {
                    if ($str = $this->run->getStream(Log::INFO))
                        $str->write(
                            "Failed building " .
                            self::printTarget($target, true) . ": missing " .
                            "dependency '$dep'"
                        );
                    unset($this->pendingTargets[$id]);
                    $this->failedTargets[$id] = $target;
                    $skip = true;
                    break;
                } elseif (!isset($this->builtTargets[$dep])) {
                    $skip = true;
                    break;
                }
            }
            if ($skip)
                continue;
            try {
                if ($str = $this->run->getStream(Log::INFO))
                    $str->write("Building " . self::printTarget($target, false));
                $target->execute($this->run);
                $this->builtTargets[$id] = $target;
                unset($this->pendingTargets[$id]);
                $this->state |= self::STATE_TARGETS_BUILT;
            } catch (TryAgain $e) {
                if ($str = $this->run->getStream(Log::VERBOSE)) {
                    $info = $target->info();
                    $label = $info ?
                        $info->label() :
                        get_class($target);
                    $str->write("Scheduling $label to execute again");
                }
            } catch (Throwable $e) {
                $this->failedTargets[$id] = $target;
                unset($this->pendingTargets[$id]);
                $label = self::printTarget($target, true);
                $message =
                    "Failed building $label: " .
                    ( $e instanceof \CodeRage\Error ?
                          $e->details() :
                          $e->getMessage() );

                $this->run->log()->logError($message);
            }
        }
    }

    /**
     * Comparison callback for use with CodeRage\Util\strictPreorderSort; orders
     * targets by dependency.
     *
     * @param CodeRage\Build\Target $lhs
     * @param CodeRage\Build\Target $rhs
     * @return int
     */
    static function compareTargets($lhs, $rhs)
    {
        $rid = $rhs->id();
        foreach ($lhs->dependencies() as $dep)
            if ($dep == $rid)
                return 1;
        $lid = $lhs->id();
        foreach ($rhs->dependencies() as $dep)
            if ($dep == $lid)
                return -1;
        return 0;
    }

    /**
     * Returns a human-redable description of the given target,
     *
     * @param CodeRage\Build\Target $target
     * @param boolean $verbose truse to include the location of the target
     *   definition
     */
    static function printTarget($target, $verbose)
    {
        $label = null;
        if (($info = $target->info()) && $info->label()) {
            $label = "target '" . $info->label() . "'";
        } elseif (($id = $target->id()) && strncmp($id, '__', 2) != 0) {
            $label = "target '" . $target->id() . "'";
        } elseif ($def = $target->definition()) {
            $label = "target of type '$def->localName'";
        } else {
            $label = 'unnamed target';
        }
        if ($verbose && $target->source())
            $label .= " at '" . $target->source() . "'";
        return $label;
    }
}
