<?php

/**
 * Defines the class CodeRage\Build\Config\Compound
 *
 * File:        CodeRage/Build/Config/Compound.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     CodeRage rights reserved
 */

namespace CodeRage\Build\Config;

use Exception;
use CodeRage\Util\Args;

/**
 * Implementation of CodeRage\Build\ProjectConfig that delegates to a contained
 * list of property bundles.
 */
final class Compound extends Basic {

    /**
     * Constructs a CodeRage\Build\Config\Compound.
     *
     * @param array $configs A list of instances of CodeRage\Build\BuildConfig
     *   used to fulfill requests for properties; the configurations are
     *   searched configurations with the bundle, if any, at offset 0
     * @throws Exception if the property bundles are inconsistent or if
     *   no value is provided for a required property.
     */
    public function __construct($configs = [])
    {
        parent::__construct([]);
        $this->constructProperties($configs);
    }

    /**
     * Initializes the collection array of properties
     *
     * @param array $configs
     */
    private function constructProperties(array $configs): void
    {
        $names = [];
        foreach ($configs as $i => $c) {
            Args::check(
                $c,
                'CodeRage\Build\BuildConfig',
                "configuration at position $i"
            );
            $names[] = $c->propertyNames();
        }
        $names = array_unique(array_merge(...$names));
        $count = sizeof($configs);
        foreach ($names as $name) {
            $type = $value = $setAt = null;
            for ($z = $count - 1; $z != -1; --$z) {
                if ($p = $configs[$z]->lookupProperty($name)) {
                    $type = $p->type();
                    $value = $p->value();
                    $setAt = $p->setAt();
                }
            }
            $this->addProperty(new Property([
                'name' => $name,
                'type' => $type,
                'value' => $value,
                'setAt' => $setAt
            ]));
        }
    }
}
