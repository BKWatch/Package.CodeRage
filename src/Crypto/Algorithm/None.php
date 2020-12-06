<?php

/**
 * Defines the class CodeRage\Crypto\Algorithm\None
 *
 * NEVER USE THIS - NEVER USE THIS - NEVER USE THIS - NEVER USE THIS
 *
 * File:        CodeRage/Crypto/Algorithm/None.php
 * Date:        Mon Nov  7 20:22:02 UTC 2016
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2016 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Crypto\Algorithm;

/**
 * Provides no enryption
 */
final class None implements \CodeRage\Crypto\Algorithm {

    public function encrypt($data, $key) { return $data; }

    public function decrypt($data, $key) { return $data; }
}
