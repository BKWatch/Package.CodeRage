<?php

/**
 * Contains the definition of the function CodeRage\Util\NativeDataEncoder
 *
 * File:        CodeRage/Util/NativeDataEncoder.php
 * Date:        Thu May  3 15:59:49 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use stdClass;
use CodeRage\Util\Array_;

/**
 * @ignore
 */

/**
 * Converts objects to native data structures, i.e., values composed
 * from scalars using indexed arrays and instances of stdClass
 */
class NativeDataEncoder extends BasicProperties {

    /**
     * Constructs an instance of CodeRage\Util\NativeDataEncoder
     *
     * @param $options An associative array of options used to customize the
     *   encoding.
     */
    public function __construct($options = [])
    {
        $this->options = $options;
    }

    /**
     * Returns a copy of the underlying options array
     *
     * @return array
     */
    public function options() { return $this->options; }

    /**
     * Returns the result of encoding the given value as a native data
     * structure, i.e., a value composed from scalars using indexed arrays and
     * instances of stdClass. Scalars are encoded as themselves; indexed arrays
     * are encoded by encoding each array element and creating a new indexed
     * array containing the encoded elements; instances of stdClass are encoded
     * by creating a new instance of stdClass with property names equal to the
     * property names of the first instance and property values equal to the
     * result of encoding the property values of the first instance.
     *
     * Objects of type other than stdClass are encoded as the result of calling
     * their nativeDataEncode() method, if it exists. Otherwise, the object must
     * define a nativeDataProperties() method returning either a list of
     * property names or an associative array mapping property names to method
     * names or callable objects. In the first case, the object is encoded as an
     * instance of stdClass with property names equal to the elements of the
     * returned array and values equal to the result of encoding the return
     * value alling the like-named methods of the object being encoded, which
     * must exist, passing this encoder as argument. In the second case, the
     * object is encoded as an instance of stdClass with a property for each key
     * of the returned array. The value of the property is the result of
     * encoding the return value of the method or the result of invoking the
     * callable object. In the latter case, the callable object is passed the
     * object being encoded and this encoder as arguments.
     *
     * The behavior on associative arrays and resources is undefined.
     *
     * Each time nativeDataEncode() or nativeDataProperties() is called it is
     * passed this encoder as its argument.
     *
     * @param mixed $value A scalar, indexed array, or object
     * @return mixed
     * @exception CodeRage\Error if during the encoding process an object of class
     *   other than stdClass is encountered that doesn't implement
     *   nativeDataEncode() or nativeDataProperties()
     */
    public function encode($value)
    {
        $result = null;
        if (is_scalar($value)) {
            $result = $value;
        } elseif (is_array($value) && Array_::isIndexed($value)) {
            $result = [];
            foreach ($value as $i)
                $result[] = $this->encode($i);
        } elseif (is_array($value) || $value instanceof stdClass) {
            $result = new stdClass;
            foreach ($value as $n => $v)
                $result->$n = $this->encode($v);
        } elseif (is_object($value)) {
            if (method_exists($value, 'nativeDataEncode')) {
                $result = $this->encode($value->nativeDataEncode($this));
            } elseif (method_exists($value, 'nativeDataProperties')) {
                $result = new stdClass;
                $properties = $value->nativeDataProperties($this);
                if (isset($properties[0])) {
                    foreach ($properties as $n)
                        $result->$n = $this->encode($value->$n());
                } else {
                    foreach ($properties as $n => $f) {
                        $result->$n = is_string($f) ?
                            $this->encode($value->$f()) :
                            $f($value, $this);
                    }
                }
            } else {
                $result = (string) $value;
            }
        }
        return $result;
    }

    /**
     * The associative array of options
     *
     * @var array
     */
    private $options;
}
