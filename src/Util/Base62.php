<?php

/**
 * Defines the class CodeRage\Util\Base62
 *
 * File:        CodeRage/Util/Base62.php
 * Date:        Tue May 16 12:44:47 UTC 2017
 *
 * @author      Fallak Asad
 *
 * Adapted from Mika Tuupola's Base62 library (http://bit.ly/2pAh2KY),
 * Copyright 2016-2017 Mika Tuupola, distributed under the folloing license:
 *
 * > MIT License (MIT)
 *
 * > Permission is hereby granted, free of charge, to any person obtaining a
 * > copy of this software and associated documentation files (the
 * > "Software"), to deal in the Software without restriction, including
 * > without limitation the rights to use, copy, modify, merge, publish,
 * > distribute, sublicense, and/or sell copies of the Software, and to
 * > permit persons to whom the Software is furnished to do so, subject to
 * > the following conditions:
 *
 * > The above copyright notice and this permission notice shall be included
 * > in all copies or substantial portions of the Software.
 *
 * > THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * > OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * > MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * > IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * > CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT
 * > OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
 * > THE USE OR OTHER DEALINGS IN THE SOFTWARE
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Util\Args;


final class Base62 {

    /**
     * Encodes the given string or integer using base62
     *
     * @param mixed $data The string or int to be encoded
     * @return string The encoded value
     */
    static function encode($data)
    {
        if (is_integer($data)) {
            $hex = dechex($data);
        } elseif (is_string($data)) {
            $hex = bin2hex($data);
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Expected string or int; found ' . Error::formatValue($data)
                ]);
        }
        return gmp_strval(gmp_init($hex, 16), 62);
    }

    /**
     * Decodes the given base62-encoded string or integer
     *
     * @param string $data A base62-encoded value
     * @param boolean $asInt true if $data should be interpreted as an
     *   encoded integer; defaults to false
     * @return int The decoded valuue
     */
    static function decode($data, $asInt = false)
    {
        Args::check($data, 'string', 'data');
        Args::check($asInt, 'boolean', 'as integer flag');
        $hex = gmp_strval(gmp_init($data, 62), 16);
        if (strlen($hex) % 2)
            $hex = '0' . $hex;
        return $asInt ? hexdec($hex) : hex2bin($hex);
    }
}
