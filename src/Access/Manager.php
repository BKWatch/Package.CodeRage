<?php

/**
 * Defines the class CodeRage\Access\Manager
 *
 * File:        CodeRage/Access/Manager.php
 * Date:        Fri Jan  4 13:45:21 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\Args;


/**
 * Tool for interacting with the access control system
 */
final class Manager extends \CodeRage\Tool\Tool {

    /**
     * @var string
     */
    const MATCH_COMMAND = '/^([a-z][_a-z0-9]*)(-([a-z][_a-z0-9]*))*$/';

    /**
     * @var string
     */
    const MATCH_DESCRIPTOR =
        '/^([a-z][_a-z0-9]*)(-([0-9a-f]{8})|\[[^]]+\]|\((0|[1-9][0-9]*)\))$/';

    /**
     * Constructs a CodeRage\Access\Manager
     *
     * @param array $options The options array; supports all options supported
     *   by CodeRage\Tool\Tool
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    public function __call($method, $args)
    {
        throw new
            Error([
                'status' => 'UNSUPPORTED_OPERATION',
                'details' =>
                    "No such method: CodeRage\\Access\\Resource::$method()"
            ]);
    }

    /**
     * @param array $options The options array; supports the following options:
     *     command - The command name
     * @return mixed The result of executing the command
     * @throws CodeRage\Error
     */
    protected function doExecute(array $options)
    {
        Args::checkKey($options, 'command', 'string', [
            'required' => true
        ]);
        $command = $this->loadCommand($options['command']);
        $params = $options;
        unset($params['command']);
        return $command->execute($this, $params);
    }

    /**
     * Returns the user, group, permission, or resource with the given
     * descriptor
     *
     * @param string $descriptor A string with one other the following forms,
     *   where TYPE is a resource type name:
     *     TYPE-xxxxxxxx - The resource of type TYPE, with ID equal to the
     *       result of decoding the hexidecial value xxxxxxxx
     *     TYPE(I) - The resource of type TYPE having ID N
     *     TYPE(NAME) - The resource of type TYPE whose alternate primary key
     *       value is NAME
     *   If TYPE is "user", "group", or "perm", the returned value will have
     * @param string $expectedType The expected resource type; if it does not
     *   match the type of the loaded resource, an exception will be thrown
     *   (optional)
     * @return mixed An instance of CodeRage\Access\User, CodeRage\Access\Group,
     *   or CodeRage\Access\Permission, if TYPE is "user", "group", or "perm",
     *   and an instance of CodeRage\Access\Resource, otherwise
     */
    public function loadDescriptor($descriptor, $expectedType = null)
    {
        return Resource_::loadDescriptor($descriptor, $expectedType);
    }

    /**
     * Returns the named command
     *
     * @param string $name The command name
     * @return CodeRage\Access\Manager\Command
     */
    public final function loadCommand($name)
    {
        if (!preg_match(self::MATCH_COMMAND, $name))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid command name: $name"
                ]);
        $class = Text::toCamelCase($name);
        $qualified = 'CodeRage\\Access\\Manager\\Command\\' . $class;
        if (!\CodeRage\Util\Factory::classExists($qualified))
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'details' =>
                        "Failed loading command '$name': the class " .
                        "$qualified does not exist"
                ]);
        $command = new $qualified;
        if ($command->name() != $name)
            throw new
                Error([
                    'status' => 'INTERNAL_ERROR',
                    'message' =>
                        "Failed loading command: expected command with name " .
                        "'$name'; found command with name '" .
                        $command->name() . "'"
                ]);
        return $command;
    }
}
