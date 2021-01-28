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

use const CodeRage\Build\ISSET_;
use const CodeRage\Build\LIST_;
use const CodeRage\Build\TYPE_MASK;
use CodeRage\Text;
use Exception;

/**
 * Implementation of CodeRage\Build\ProjectConfig that delegates to a contained
 * list of property bundles.
 */
class Compound extends Basic {
    use Converter;

    /**
     * Constructs a CodeRage\Build\Config\Compound.
     *
     * @param array $bundles A list of instances of CodeRage\Build\ProjectConfig
     * used to fulfill requests for properties; the bundles are searched
     * starting with the bundle, if any, at offset 0.
     * @throws Exception if the property bundles are inconsistent or if
     * no value is provided for a required property.
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
    private function constructProperties($bundles)
    {
        $merge = [];
        foreach ($bundles as $b)
            $merge[] = $b->propertyNames();
        $names = array_unique(call_user_func_array('array_merge', $merge));
        foreach ($names as $n) {
            $flags = 0;
            $specifiedAt = $setAt = $value = null;
            $count = sizeof($bundles);
            for ($z = $count - 1; $z != -1; --$z) {
                $b = $bundles[$z];
                $p = $b->lookupProperty($n);
                if (!$p)
                    continue;

                // Check CodeRage\Build\LIST_
                if ( $z < $count - 1 &&
                     ( ( ($flags & LIST_) == 0 &&
                         $p->isList() &&
                         is_string($specifiedAt) ) ||
                       ( ($flags & LIST_) != 0 &&
                         !$p->isList() &&
                         is_string($p->specifiedAt()) ) ) )
                {
                    $loc1 =
                        Property::translateLocation(
                            $specifiedAt
                        );
                    $loc2 =
                        Property::translateLocation(
                            $p->specifiedAt()
                        );
                    $list = $p->isList() ? $loc2 : $loc1;
                    $scalar = $p->isList() ? $loc1 : $loc2;
                    throw new
                        Exception(
                            "Inconsistent specification for property '$n':" .
                            " property is a list at '$list' but a scalar at " .
                            "'$scalar'"
                        );
                }
                if ($p->isList())
                    $flags |= LIST_;

                // Process type
                if ($p->type()) {
                    $type = $flags & TYPE_MASK;
                    if ( $z < $count - 1 &&
                         $type != 0 &&
                         $type != $p->type() &&
                         is_string($specifiedAt) &&
                         is_string($p->specifiedAt()) )
                    {
                        $type1 = Property::translateType($type);
                        $type2 =
                            Property::translateType($p->type());
                        $loc1 =
                            Property::translateLocation(
                                $specifiedAt
                            );
                        $loc2 =
                            Property::translateLocation(
                                $p->specifiedAt()
                            );
                        throw new
                            Exception(
                                "Inconsistent specification for property " .
                                "'$n': property is $type1 at '$loc1' but " .
                                "$type2 at '$loc2'"
                            );
                    }
                    $flags |= $p->type();
                    $specifiedAt = $p->specifiedAt();
                }

                // Process value
                if ($p->isSet()) {
                    $flags |= ISSET_;
                    $setAt = $p->setAt();
                    $value = $p->value();
                }
                if ($flags & ISSET_) {
                    $type = $flags & TYPE_MASK;

                    // Split list values specified as strings (this can only
                    // happen on the command-line or in the environment)
                    if ( ($flags & LIST_) != 0 &&
                         is_string($value) )
                    {
                        $value = Text::split($value);
                    }

                    // Convert values whose type is known but not set by $p
                    if ($flags & LIST_) {
                        for ($w = 0, $m = sizeof($value); $w < $m; ++$w)
                            $value[$w] =
                                $this->convert($value[$w], $type);
                    } else {
                        $value = $this->convert($value, $type);
                    }
                }
            }

            if (($flags & TYPE_MASK) == 0)
                $flags |= \CodeRage\Build\STRING;

            // Add property
            $this->addProperty(
                new Property(
                        $n, $flags, $value, $specifiedAt, $setAt
                    )
            );
        }
    }
}
