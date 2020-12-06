<?php

/**
 * Defines the class CodeRage\Util\Test\ValidateCase
 * 
 * File:        CodeRage/Util/Test/ValidateCase.php
 * Date:        Mon Aug 15 13:34:26 EDT 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use Exception;
use stdClass;
use Throwable;
use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test case that invokes validate() and verifies that the outcome is as
 * expected
 */
class ValidateCase extends \CodeRage\Test\Case_ {

    /**
     * Constructs an instance of CodeRage\Util\Test\ValidateCase
     *
     * @param mixed $value The value
     * @param string $type The type name
     * @param string $label The label
     * @param boolean $valid true if $value is valid with respect to type $type
     * @throws CodeRage\Error
     */
    public function __construct($value, $type, $label, $valid)
    {
        $name = "validate-case[$label]";
        $description =
            "Validate Case with value: '$value'; type: $type; label: $label; " .
            "valid: " . (int) $valid;
        parent::__construct($name, $description);
        $this->value = $value;
        $this->type = $type;
        $this->label = $label;
        $this->valid = $valid;
    }

    protected function doExecute($ignore)
    {
        $error = null;
        try {
            Args::check($this->value, $this->type, $this->label);
        } catch (Throwable $e) {
            if ( $this->valid ||
                 !$e instanceof Error ||
                 $e->status() != 'INVALID_PARAMETER' )
            {
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' => 'Caught unexpected exception',
                        'inner' => $e
                    ]);
            }
            $error = $e;
        }
        if ($error !== null) {
            echo "Caugh expected exception with status code " .
                 "'INVALID_PARAMETER' and details '" . $e->details() . "'";
        } elseif (!$this->valid) {
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "Expected exception with status code " .
                        "'INVALID_PARAMETER'; none caught"
                ]);
        }
    }

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $label;

    /**
     * @var boolean
     */
    private $valid;
}
