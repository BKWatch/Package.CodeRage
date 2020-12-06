<?php

/**
 * Defines functions for translating between database record IDs and opaque
 * hexadecimal strings
 *
 * File:        CodeRage/Util/ObjectId.php
 * Date:        Sun Mar 20 13:25:04 MDT 2011
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

/**
 * @ignore
 */

/**
 * Container for static methods and constants
 */
class ObjectId {
    const OFFSET = 0x218af649;
    const XOR_ = 0x00000003;
    const XOR0 = 0x09f21260;
    const XOR1 = 0x0ef61950;
    const XOR2 = 0x2bc2dc00;
    const XOR3 = 0xa2ae1ed0;
    const XOR4 = 0x73c5a470;
    const XOR5 = 0x80981190;
    const XOR6 = 0x4625cc00;
    const XOR7 = 0x8c3da520;
    const XOR8 = 0x4f30b800;
    const XOR9 = 0x28bdd350;
    const XORA = 0xba33f000;
    const XORB = 0xbe626cf0;
    const XORC = 0x0e52d080;
    const XORD = 0x152136a0;
    const XORE = 0xf157a3b0;
    const XORF = 0x889bb540;

    /**
     * Takes a 32-bit integer and returns an object identifier consisting of
     * an 8-digit hexadecimal string, if $domain is null, and $domain followed
     * by a hyphen and an 8-digit hexadecimal string, otherwise
     *
     * @param int $int
     * @param string $domain
     * @return string
     */
    static function encode($int, $domain = null)
    {
        $int += self::OFFSET;
        if ($int > 0xffffffff)
           $int -= 0xffffffff;
        switch ($int & 0x0000000f) {
        case 0x00000000:
            $int ^= self::XOR0;
            break;
        case 0x00000001:
            $int ^= self::XOR1;
            break;
        case 0x00000002:
            $int ^= self::XOR2;
            break;
        case 0x00000003:
            $int ^= self::XOR3;
            break;
        case 0x00000004:
            $int ^= self::XOR4;
            break;
        case 0x00000005:
            $int ^= self::XOR5;
            break;
        case 0x00000006:
            $int ^= self::XOR6;
            break;
        case 0x00000007:
            $int ^= self::XOR7;
            break;
        case 0x00000008:
            $int ^= self::XOR8;
            break;
        case 0x00000009:
            $int ^= self::XOR9;
            break;
        case 0x0000000a:
            $int ^= self::XORA;
            break;
        case 0x0000000b:
            $int ^= self::XORB;
            break;
        case 0x0000000c:
            $int ^= self::XORC;
            break;
        case 0x0000000d:
            $int ^= self::XORD;
            break;
        case 0x0000000e:
            $int ^= self::XORE;
            break;
        case 0x0000000f:
        default:
            $int ^= self::XORF;
            break;
        }
        $id = sprintf('%08x', $int ^ self::XOR_);
        return $domain !== null ? "$domain-$id" : $id;
    }

    /**
     * Takes an object ID and returns a 32-bit integer
     *
     * @param string $id An object identifier consisting of an 8-digit hexadecimal
     *   string, if $domain is null, and $domain followed by a hyphen and an 8-digit
     *   hexadecimal string, otherwise
     * @param string $domain
     * @param string $label Text to use in error messages
     * @return int
     * @throws CodeRage\Error if $domain is non-null and $id is invalid
     */
    static function decode($id, $domain = null, $label = null)
    {
        if ($domain !== null) {
            $match = null;
            if (!preg_match("/^$domain-([[:xdigit:]]{8})$/", $id, $match))
                throw new
                    \CodeRage\Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => $label ?
                            "Invalid $label: $id" :
                            "Invalid object ID: $id"
                    ]);
            $id = $match[1];
        }
        $int = hexdec($id) ^ self::XOR_;
        switch ($int & 0x0000000f) {
        case 0x00000000:
            $int ^= self::XOR0;
            break;
        case 0x00000001:
            $int ^= self::XOR1;
            break;
        case 0x00000002:
            $int ^= self::XOR2;
            break;
        case 0x00000003:
            $int ^= self::XOR3;
            break;
        case 0x00000004:
            $int ^= self::XOR4;
            break;
        case 0x00000005:
            $int ^= self::XOR5;
            break;
        case 0x00000006:
            $int ^= self::XOR6;
            break;
        case 0x00000007:
            $int ^= self::XOR7;
            break;
        case 0x00000008:
            $int ^= self::XOR8;
            break;
        case 0x00000009:
            $int ^= self::XOR9;
            break;
        case 0x0000000a:
            $int ^= self::XORA;
            break;
        case 0x0000000b:
            $int ^= self::XORB;
            break;
        case 0x0000000c:
            $int ^= self::XORC;
            break;
        case 0x0000000d:
            $int ^= self::XORD;
            break;
        case 0x0000000e:
            $int ^= self::XORE;
            break;
        case 0x0000000f:
        default:
            $int ^= self::XORF;
            break;
        }
        $int -= self::OFFSET;
        if ($int < -0xffffffff)
            $int += 0xffffffff;
        return $int;
    }
}
