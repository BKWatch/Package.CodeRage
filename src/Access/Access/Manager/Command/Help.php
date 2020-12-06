<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\Help
 *
 * File:        CodeRage/Access/Manager/Command/Help.php
 * Date:        Fri Jan  4 13:45:21 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Manager\Command;

use CodeRage\Access\Manager;

/**
 * Implements the command 'help'
 */
final class Help extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'help',
            'description' => 'Lists supported commands',
            'params' =>
                [
                    [
                        'name' => 'prefix',
                        'description' => 'A prefix of the command name',
                        'type' => 'string'
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $prefix = isset($params['prefix']) ? $params['prefix'] : null;
        $iterator = new \FileSystemIterator(__DIR__);
        $commands = [];
        foreach ($iterator as $path => $info) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $name = strtolower(preg_replace('/[A-Z]/', '-$0', lcfirst($name)));
            if ( $prefix === null ||
                 strncmp($prefix, $name, strlen($prefix)) == 0 )
            {
                $cmd = $manager->loadCommand($name);
                $commands[] =
                    [
                        'name' => $cmd->name(),
                        'description' => $cmd->description(),
                    ];
            }
        }
        usort(
            $commands,
            function($a, $b) { return strcmp($a['name'], $b['name']); }
        );
        return ['commands' => $commands];
    }
}
