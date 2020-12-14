<?php

/**
 * Defines the class CodeRage\Build\TaskList.
 *
 * File:        CodeRage/Build/TaskList.php
 * Date:        Sat Jan 10 11:41:19 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * @ignore
 */

/**
 * Represents a collection tasks that must be performed in a fixed order.
 */
class TaskList implements Task {

    /**
     * The underlying list of instances of CodeRage\Build\Task.
     *
     * @var array
     */
    private $tasks;

    function __construct($tasks = [])
    {
        $this->tasks = $tasks;
    }

    /**
     * Adds a task to the underlying list of tasks.
     *
     * @param CodeRage\Build\Task $task
     */
    function addTask(Task $task)
    {
        $this->tasks[] = $task;
    }

    /**
     * Executes each task in the underlying list, in order.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     */
    function execute(Engine $engine)
    {
        foreach ($this->tasks as $t)
            $t->execute($engine);
    }
}
