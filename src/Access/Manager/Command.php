<?php

/**
 * Defines the class CodeRage\Access\Manager\Command
 *
 * File:        CodeRage/Access/Manager/Command.php
 * Date:        Sun Jan  6 15:37:39 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Manager;

use CodeRage\Access\Manager;
use CodeRage\Access\ResourceType;
use CodeRage\Error;
use CodeRage\Text\Regex;
use CodeRage\Util\Args;


/**
 * Implements a command for CodeRage\Access\Manager
 */
abstract class Command {

    /**
     * @var string
     */
    const MATCH_BOOLEAN = '/^(1|0|true|false)$/';

    /**
     * @var string
     */
    const MATCH_INT = '/^-?(0|[1-9][0-9]*)$/';

    /**
     * @var string
     */
    const MATCH_PARAM_TYPE =
        '/^(boolean|int|string|descriptor)(?:\[([a-z][_a-z0-9]*)\])?$/';

    /**
     * @param $options The options array; supports the following options:
     *     name - The command name
     *     title - A descriptive label for the command (optional)
     *     description - The command description
     *     params - A list of associative arrays with the following keys:
     *       name - The parameter name
     *       title - A descriptive label for the parameter (optional)
     *       description - The parameter description (optional)
     *       type - One of "boolean", "int", "string", or "descriptor";
     *         strings are automatically coerced to the appropriate type
     *       required - true if the parameter is required; defaults to false
     */
    protected function __construct(array $options)
    {
        Args::checkKey($options, 'name', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'title', 'string', [
            'default' => $options['name']
        ]);
        Args::checkKey($options, 'description', 'string', [
            'required' => true
        ]);
        Args::checkKey($options, 'params', 'list', [
            'required' => true
        ]);
        $params = [];
        foreach ($options['params'] as $i => $param) {
            Args::check($param, 'map', "parameter at position $i");
            $name =
                Args::checkKey($param, 'name', 'string', [
                    'label' => "name of param at position $i",
                    'required' => true
                ]);
            Args::checkKey($param, 'title', 'string', [
                'label' => "title of '$name' param",
                'default' => $name
            ]);
            Args::checkKey($param, 'description', 'string', [
                'label' => "description of '$name' param",
                'required' => true
            ]);
            $type =
                Args::checkKey($param, 'type', 'string', [
                    'label' => "type of '$name' param",
                    'required' => true
                ]);
            if (!preg_match(self::MATCH_PARAM_TYPE, $type))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "type of '$name' param"
                    ]);
            Args::checkKey($param, 'required', 'boolean', [
                'label' => "required flag of '$name' param",
                'default' => false
            ]);
            if (isset($params[$name]))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Duplicate '$name' param"
                    ]);
            $params[$name] = $param;
        }
        $this->name = $options['name'];
        $this->title = $options['title'];
        $this->description = $options['description'];
        $this->params = $params;
    }

    /**
     * Returns the command name
     *
     * @return string
     */
    public final function name()
    {
        return $this->name;
    }

    /**
     * Returns a descriptive label of this command
     *
     * @return string
     */
    public final function title()
    {
        return $this->title;
    }

    /**
     * Returns the command description
     *
     * @return string
     */
    public final function description()
    {
        return $this->description;
    }

    /**
     * Returns the list of parameters
     *
     * @return array
     */
    public final function params()
    {
        return $this->params;
    }

    /**
     * Executes this command
     *
     * @param CodeRage\Access\Manager $manager
     * @param array $params An associative array of parameter values
     * @return array An associative array, or null
     * @throws CodeRage\Error
     */
    final function execute(Manager $manager, array &$params)
    {
        $this->processParams($manager, $params);
        return $this->doExecute($manager, $params);
    }

    /**
     * Implements execute()
     *
     * @param CodeRage\Access Manager $manager
     * @param array $params An associative array of parameter values
     * @throws CodeRage\Error
     */
    protected function doExecute(Manager $manager, array $params)
    {
        return null;
    }

    /**
     * Validates and processes params for execute()
     *
     * @param CodeRage\Access\Manager $manager
     * @param array $params An associative array of parameter values
     * @throws CodeRage\Error
     */
    private function processParams(Manager $manager, array &$params)
    {
        foreach ($params as $n => &$v) {
            if (!isset($this->params[$n]))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Unsupported param: $n"
                    ]);
            $def = $this->params[$n];
            $match = Regex::match(self::MATCH_PARAM_TYPE, $def['type'], [1, 2]);
            $type = $match[0];
            $rType = isset($match[1]) ? $match[1] : null;
            switch ($type) {
            case 'boolean':
                if ( !is_boolean($v) &&
                     (!is_string($v) || !preg_match(self::MATCH_BOOLEAN, $v)) )
                {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' =>
                                "Invalid '$name' param: expected boolean; " .
                                "found " . Error::formatValue($v)
                        ]);
                }
                if (is_string($v))
                    $v = ($v == 'true' || $v == '1');
                break;
            case 'int':
                if ( !is_int($v) &&
                     (!is_string($v) || !preg_match(self::MATCH_INT, $v)) )
                {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' =>
                                "Invalid '$name' param: expected integer; " .
                                "found " . Error::formatValue($v)
                        ]);
                }
                if (is_string($v))
                    $v = (int) $v;
                break;
            case 'string':
                if (!is_string($v))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' =>
                                "Invalid '$name' param: expected string; " .
                                "found " . Error::formatValue($v)
                        ]);
                break;
            case 'descriptor':
                if ( !is_string($v) ||
                     !preg_match(Manager::MATCH_DESCRIPTOR, $v) )
                {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' =>
                                "Invalid '$n' param: expected resource " .
                                "descrptor; found " . Error::formatValue($v)
                        ]);
                }
                $v = $manager->loadDescriptor($v);
                if (isset($match[1])) {
                    $resource = $v instanceof Resource_ ?
                        $resource :
                        $v->resource();
                    if ($resource->type()->name() != $rType) {
                        $rType = ResourceType::load(['name' => $rType]);
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Invalid '$n' param: expected resource " .
                                    "of type " . strtolower($rType->title()) .
                                    "; found resource of type " .
                                    strtolower($resource->type()->title())
                            ]);
                    }
                }
                break;
            default:
                break;
            }
        }
        foreach ($this->params as $n => $def)
            if ($def['required'] && !isset($params[$n]))
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => "Missing '$n' param"
                    ]);
    }

    /**
     * The command name
     *
     * @var string
     */
    private $name;

    /**
     * The descriptive label for the command
     *
     * @var string
     */
    private $title;

    /**
     * The command description
     *
     * @var string
     */
    private $description;

    /**
     * The collection of parameters, indexed by name
     *
     * @var array
     */
    private $params;
}
