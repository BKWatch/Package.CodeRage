<?php

/**
 * Defines the class CodeRage\Sys\Config\Compound
 *
 * File:        CodeRage/Sys/Config/Compound.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     CodeRage rights reserved
 */

namespace CodeRage\Sys\Config;

use Exception;
use CodeRage\Sys\Property;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Sys\ProjectConfig that delegates to a contained
 * list of property bundles.
 */
final class Compound extends Basic {

    /**
     * Constructs a CodeRage\Sys\Config\Compound
     *
     * @param array $configs A list of instances of CodeRage\Sys\ProjectConfig
     *   used to fulfill requests for properties; the configurations are
     *   searched configurations with the bundle, if any, at offset 0
     * @throws Exception if the property bundles are inconsistent or if
     *   no value is provided for a required property.
     */
    public function __construct($configs = [])
    {
        $properties = $names = [];
        foreach ($configs as $i => $c) {
            Args::check(
                $c,
                'CodeRage\Sys\ProjectConfig',
                "configuration at position $i"
            );
            $names[] = $c->propertyNames();
        }
        $names = array_unique(array_merge(...$names));
        $count = sizeof($configs);
        foreach ($names as $name) {
            $storage = $value = $setAt = null;
            for ($z = $count - 1; $z != -1; --$z) {
                if ($p = $configs[$z]->lookupProperty($name)) {
                    $storage = $p->storage();
                    $value = $p->value();
                    $setAt = $p->setAt();
                }
            }
            $properties[$name] =
                new Property([
                        'storage' => $storage,
                        'value' => $value,
                        'setAt' => $setAt
                    ]);
        }
        parent::__construct($properties);
    }
}
