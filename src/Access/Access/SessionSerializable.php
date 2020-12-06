<?php

/**
 * Defines the trait CodeRage\Util\SessionSerializable
 *
 * File:        CodeRage/Access/SessionSerializable.php
 * Date:        Thu Apr 20 15:35:40 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Access;

use CodeRage\Util\Args;
use CodeRage\Util\NativeDataEncoder;


/**
 * Provides a jsonSerialize() implementation for classes that support native
 * data encoding
 */
trait SessionSerializable {

    /**
     * Implements JsonSerializable::jsonSerialize()
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return self::encoder()->encode($this);
    }

    /**
     * Returns the instance of the exhibiting class stored in the given session
     * object, if any; requires that the exhibiting class define a static method
     * nativeDataDecode() taking a native data structure and returning an
     * instance of the exhibiting class
     *
     * @param CodeRage\Access\Session $session
     * @return object
     */
    public static function sessionGet(Session $session)
    {
        $data = $session->data();
        $key = self::sessionKey();
        if (isset($data->$key)) {
            $value = $data->$key;
            if ( $value instanceof \stdClass ||
                 is_array($value) ||
                 is_scalar($value) ||
                 is_null($value) )
            {
                $data->$key = static::nativeDataDecode($value);
            }
            return $data->$key;
        } else {
            return null;
        }
    }

    /**
     * Stores the given instance of the exhibiting class in the given session
     * object
     *
     * @param CodeRage\Access\Session $session
     * @param object $instance An instance of the exhibiting class
     */
    public static function sessionSet(Session $session, $instance)
    {
        Args::check($instance, static::class, 'instance');
        $key = self::sessionKey();
        $session->data()->$key = $instance;
    }

    private static function encoder()
    {
        static $encoder;
        if ($encoder === null)
            $encoder = new NativeDataEncoder(['coderage.access.session' => true]);
        return $encoder;
    }

    /**
     * Returns the key under which instances of the exhibiting class are stored
     * in session objects
     *
     * @return string
     */
    private static function sessionKey()
    {
        return 'CodeRage.Access.SessionSerializable.' .
               str_replace('\\', '.', static::class);
    }
}
