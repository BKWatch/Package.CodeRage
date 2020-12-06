<?php

/**
 * Defines the class CodeRage\Access\Manager\Command\ResourceEnable
 *
 * File:        CodeRage/Access/Manager/Command/ResourceEnable.php
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
 * Implements the command 'resource-enable'
 */
final class ResourceEnable extends \CodeRage\Access\Manager\Command {
    public function __construct()
    {
        parent::__construct([
            'name' => 'resource-enable',
            'description' => 'Enables a resource',
            'params' =>
                [
                    [
                        'name' => 'resource',
                        'description' => 'The resource to enable',
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
        $resource->setDisabled(false);
    }
}
