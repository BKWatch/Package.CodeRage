<?php

/**
 * Defines the class CodeRage\Module
 *
 * File:        CodeRage/Module.php
 * Date:        Fri Dec 18 00:55:27 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage;

/**
 * CodeRage Module
 */
final class Module extends \CodeRage\Build\BasicModule {

    /**
     * Constructs an instance of CodeRage\Module
     *
     * @param array $options The options array
     */
    public function __construct(array $options)
    {
        parent::__construct([
            'title' => 'CodeRage',
            'description' => 'CodeRage Module',
            'dependencies' =>
                [ 'CodeRage.Access.Module', 'CodeRage.Db.Module',
                  'CodeRage.Error.Module', 'CodeRage.Log.Module',
                  'CodeRage.Log.Tool', 'CodeRage.Web.Tool' ]
        ]);
    }
}
