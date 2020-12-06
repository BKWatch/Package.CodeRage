<?php

/**
 * Defines the class CodeRage\Access\ResourceId
 *
 * File:        CodeRage/Access/ResourceId.php
 * Date:        Sun Jun 10 12:45:50 EDT 2012
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use CodeRage\Error;

/**
 * Container for static methods and constants for managing resource ID's
 */
final class ResourceId {
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
     * Takes a 32-bit integer and returns a resource identifier consisting of
     * an 8-digit hexadecimal string, if $domain is null, and $domain followed
     * by a hyphen and an 8-digit hexadecimal string, otherwise
     *
     * @param int $int
     * @param mixed $type The resource type, if any, specified by name or as an
     *   instance of CodeRage\Access\ResourceType
     * @return string
     */
    public static function encode($int, $type = null)
    {
        if ($type && !is_scalar($type))
            $type = $type->name();
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
        return $type !== null ? "$type-$id" : $id;
    }

    /**
     * Takes an object ID and returns a 32-bit integer
     *
     * @param string $id A resource identifier consisting of an 8-digit
     *   hexadecimal string, if $domain is null, and $domain followed by a
     *   hyphen and an 8-digit hexadecimal string, otherwise
     * @param mixed $type The resource type, if any, specified by name or as an
     *   instance of CodeRage\Access\ResourceType
     * @param string $label Text to use in error messages
     * @return int
     * @throws CodeRage\Error if $domain is non-null and $id is invalid
     */
    public static function decode($id, $type = null, $label = null)
    {
        if ($type && !is_scalar($type))
            $type = $type->name();
        if ($type !== null) {
            $match = null;
            if (!preg_match("/^$type-([[:xdigit:]]{8})$/", $id, $match))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => $label ?
                            "Invalid $label: $id" :
                            "Invalid resource ID: $id"
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

    /**
     * Parses the given resource ID or the form "name-xxxxxxxx" and returns
     * a pair ($type, $value), where $type is a resource name and $int is the
     * result of decoding the sequence of 8 hexidecimal digits
     *
     * @param string $id A resource identifier consisting of domain followed by
     *     a hyphen and an 8-digit hexadecimal string
     * @param string $label Text to use in error messages
     * @return array
     * @throws CodeRage\Error if $id is malformed
     */
    public static function parse($id, $label = null)
    {
        $match = null;
        if (!preg_match('/^([a-z][-a-z0-9]*)-([[:xdigit:]]{8})$/', $id, $match))
        {
            if (!$label)
                $label = 'resource ID';
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid $label: $id"
                ]);
        }
        return [$match[1], self::decode($match[2])];
    }
}
