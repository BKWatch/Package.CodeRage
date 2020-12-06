<?php

/**
 * Defines the class CodeRage\WebService\Search\Field
 *
 * File:        CodeRage/WebService/Search/Field.php
 * Date:        Tue Nov 13 21:25:55 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService\Search;

use CodeRage\Util\Args;


/**
 * Used by CodeRage\WebService\Search to store information about a field
 */
final class Field {

    /**
     * Constructs an instance of CodeRage\WebService\Search
     *
     * @param CodeRage\WebService\Search\Type $type The field type
     * @param string The field definition, as a SQL expression
     */
    public function __construct(Type $type, $definition)
    {
        Args::check($definition, 'string', 'definition');
        $this->type = $type;
        $this->definition = $definition;
    }

    /**
     * Returns the field type
     *
     * @return CodeRage\WebService\Search\Type
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns the field definition, as a SQL expression
     *
     * @return string
     */
    public function definition()
    {
        return $this->definition;
    }

    /**
     * @var CodeRage\WebService\Search\Type
     */
    private $type;

    /**
     * @var string
     */
    private $definition;
}
