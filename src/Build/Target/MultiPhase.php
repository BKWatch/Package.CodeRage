<?php

/**
 * Defines the class CodeRage\Build\Target\MultiPhase.
 *
 * File:        CodeRage/Build/Target/MultiPhase.php
 * Date:        Tue Jan 20 18:02:50 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Target;

use CodeRage\Build\Run;

/**
 * @ignore
 */

/**
 * Allows the author to define a sequence of member functions phase1(),
 * phase2(), ..., which will be called by successive invocations of execute(),
 * each but the last throwing an exception of type CodeRage\Build\TryAgain.
 */
abstract class MultiPhase extends Basic {

    /**
     * The current phase.
     *
     * @var int
     */
    private $phase = 1;

    /**
     * Constructs a CodeRage\Build\Target\MultiPhase.
     *
     * @param string $id The string, if any, identifying the target under
     * construction.
     * @param array $dependencies The list of IDs of dependent targets, if any.
     * @param CodeRage\Build\Info $info An instance of CodeRage\Build\Info describing the
     * target under construction.
     */
    function __construct($id = null, $dependencies = [], $info = null)
    {
        parent::__construct($id, $dependencies, $info);
    }

    /**
     * Calls the member function phaseN, whereN is the current phase, and throws
     * an exception of type CodeRage\Build\TryAgain unless the current phase is the
     * last.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @throws CodeRage\Error
     */
    function execute(Run $run)
    {
        $current = 'phase' . $this->phase;
        $next = 'phase' . ++$this->phase;
        $this->$current($run);
        if (method_exists($this, $next))
            throw new \CodeRage\Build\TryAgain;
    }

    /**
     * Implements the first phase of execution.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     * @throws CodeRage\Error
     */
    protected abstract function phase1(Run $run);
}
