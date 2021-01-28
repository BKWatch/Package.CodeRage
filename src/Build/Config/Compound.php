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
     * @param array $bundles A list of instances of CodeRage\Build\BuildConfig
     *   used to fulfill requests for properties; the bundles are searched
     *   starting with the bundle, if any, at offset 0
     * @throws Exception if the property bundles are inconsistent or if
     *   no value is provided for a required property.
     */
    function __construct($bundles = [])
    {
        parent::__construct([]);
        $this->constructProperties($bundles);
    }

    /**
     * Initializes the underlying associative array of properties.
     *
     * @param array $bundle
     */
    private function constructProperties(array $bundles): void
    {
        $merge = [];
        foreach ($bundles as $i => $b) {
            Args::check($b, 'CodeRage\Build\BuildConfig', "bundle at position $i");
            $merge[] = $b->propertyNames();
        }
        $names = array_unique(array_merge(...$merge));
        foreach ($names as $n) {
            $flags = 0;
            $setAt = $value = null;
            for ($z = sizeof($bundles) - 1; $z != -1; --$z) {
                $b = $bundles[$z];
                $p = $b->lookupProperty($n);
                if (!$p)
                    continue;
                if ($p->isSet()) {
                    $flags |= \CodeRage\Build\ISSET_;
                    $setAt = $p->setAt();
                    $value = $p->value();
                }
            }
            $this->addProperty(
                new Property(
                        $n, $flags, $value, 0, $setAt
                    )
            );
        }
    }
}
