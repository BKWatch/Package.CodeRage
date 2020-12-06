<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\ResourceDisable
 *
 * File:        CodeRage/Access/Manager/Command/ResourceDisable.php
 * Date:        Fri Jan  4 13:45:21 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access\Manager\Command;

use CodeRage\Access\Managed;
use CodeRage\Access\Manager;

/**
 * Implements the command 'resource-disable'
 */
final class ResourceDisable extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'resource-disable',
            'description' => 'Disables a resource',
            'params' =>
                [
                    [
                        'name' => 'resource',
                        'description' => 'The resource to disable',
                        'type' => 'descriptor',
                        'required' => true
                    ]
                ]
        ]);
    }

    protected function doExecute(Manager $manager, array $params)
    {
        $resource = $params['resource'];
        if ($resource instanceof Managed)
            $resource = $resource->resource();
        $resource->setDisabled(true);
    }
}
