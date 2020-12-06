<?php

/**
 * Defines the class CodeRage\Build\TargetSetWrapper
 * 
 * File:        CodeRage/Build/TargetSetWrapper.php
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
use function CodeRage\Xml\childElements;
use function CodeRage\Xml\firstChildElement;
use function CodeRage\Xml\getAttribute;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/File/isAbsolute.php');
require_once('CodeRage/Util/loadComponent.php');
require_once('CodeRage/Util/preorderSort.php');
require_once('CodeRage/Text/split.php');
require_once('CodeRage/Xml/childElements.php');
require_once('CodeRage/Xml/getAttribute.php');
require_once('CodeRage/Xml/firstChildElement.php');
require_once('CodeRage/Xml/loadDom.php');

/**
 * Wraps a target to ensure it has a unique ID.
 *
 */
class TargetSetWrapper implements Target {

    /**
     * The wrapped target.
     *
     * @var CodeRage\Build\Target
     */
    private $target;

    /**
     * The target ID.
     *
     * @var string
     */
    private $id;

    /**
     * Constructs a CodeRage\Build\TargetSetWrapper.
     *
     * @param CodeRage\Build\Target $target The target to be wrapped.
     */
    function __construct(Target $target)
    {
        $this->target = $target;
        $id = $target->id();
        $this->id = $id !== null ? $id : self::nextId();
    }

    /**
     * Returns the string identifying this target uniquely within its
     * containing project.
     *
     * @return string
     */
    function id()
    {
        return $this->id;
    }

    /**
     * Specifies the string, if any, identifying this target.
     *
     * @param string $id
     */
    function setId($id) { $this->id = $id; }

    /**
     * Returns the list of ids dependent targets, if any.
     *
     * @return array
     */
    function dependencies()
    {
        return $this->target->dependencies();
    }

    /**
     * Specifies the list of ids of dependent targets, if any.
     *
     * @param array $dependencies
     */
    function setDependencies($dependencies)
    {
        $this->target->setDependencies($dependencies);
    }

    /**
     * Returns an instance of CodeRage\Build\Info.
     *
     * @return CodeRage\Build\Info
     */
    function info()
    {
        return $this->target->info();
    }

    /**
     * Specifies an instance of CodeRage\Build\Info describing this target.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return CodeRage\Build\Info.
     */
    function setInfo(Info $info)
    {
        $this->target->setInfo($info);
    }

    /**
     * Returns the instance of DOMElement, if any, containing the definition of
     * this target.
     *
     * @return DOMElement
     */
    function definition()
    {
        return $this->target->definition();
    }

    /**
     * Specifies the instance of DOMElement containing the definition of
     * this target.
     *
     * @param DOMElement $definition
     */
    function setDefinition(DOMElement $definition)
    {
        $this->target->setDefinition($definition);
    }

    /**
     * Returns the path of the project definition file, if any, containing the
     * definition of this target.
     *
     * @return DOMElement
     */
    function source()
    {
        return $this->target->source();
    }

    /**
     * Specifies the instance of the project definition file containing the
     * definition of this target.
     *
     * @param string $src
     */
    function setSource($src)
    {
        $this->target->setSource($src);
    }

    /**
     * Executes the underlying target.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        $this->target->execute($run);
    }

    function __toString()
    {
        return $target->id() !== null ? $target->id() : get_class($target);
    }

    /**
     * Generates a unique ID.
     *
     * @return int
     */
    private static function nextId()
    {
        static $next = 0;
        return '__' . ++$next;
    }
}
