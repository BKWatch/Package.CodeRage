<?php

/**
 * Defines the class CodeRage\Test\Operations\Instance
 *
 * File:        CodeRage/Test/Operations/Instance.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use CodeRage\Error;
use CodeRage\Util\Factory;
use CodeRage\Xml;


/**
 * An object whose method is invoked, represented as input to
 * CodeRage\Util\loadComponent()
 */
final class Instance extends Base {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Instance
     *
     * @param string $class The class name, specified as a sequence of
         identifiers separated by dots
     * @param string $classPath The directory to be searched for class
     *   definitions, if any; the source file to be search for is formed from
     *   the value of the "class" by replacing dots with slashes and appending
     *   '.php' or '.pm'
     * @param array $params The associative array of constructor parameters,
     *   if any
     */
    public function
        __construct(
            $class,
            $classPath = null,
            $params = []
        )
    {
        $this->class = $class;
        $this->classPath = $classPath;
        $this->params = $params;
    }

    /**
     * Returns a class name, specified as a sequence of identifiers separated by
     * dots
     *
     * @return string
     */
    public function _class()
    {
        return $this->class;
    }

    /**
     * Returns the directory to be searched for class definitions, if any; the
     * source file to be search for is formed from the value of the "class"
     * property by replacing dots with slashes and appending '.php'
     *
     * @return string
     */
    public function classPath()
    {
        return $this->classPath;
    }

    /**
     * Returns the associative array of constructor parameters, if any
     *
     * @return array
     */
    public function params()
    {
        return $this->params;
    }

    /**
     * Returns the result of calling CodeRage\Util\loadComponent() with the
     * underlying class, class path, and parameters.
     *
     * @param CodeRage\Test\Operations\Operation $operation The operation that contains
     *   this instance
     * @return mixed
     */
    public function construct(Operation $operation)
    {
        $params = [];
        foreach ($this->params as $n => $v)
            $params[$n] = $operation->expandExpressions($v);
        return Factory::create([
                    'class' => $this->class,
                    'classPath' => $this->classPath,
                    'params' => $params
                ]);
    }

    /**
     * Returns a callable that invokes the named method on an
     * a newly constructed instance of the associated class, if the method is
     * non-static, and otherwise invokes method without an instance
     *
     * @param CodeRage\Test\Operations\Operation $operation The operation tha
     *   contains this instance
     * @return mixed
     */
    public function invokeMethod(Operation $operation, $method, ...$args)
    {
        $className = str_replace('.', '\\', $this->class);
        if ( method_exists($className, $method) &&
             (new \ReflectionMethod($className, $method))->isStatic() )
        {
            $func = [$className, $method];
            return $func(...$args);
        } else {
            $params = [];
            foreach ($this->params as $n => $v)
                $params[$n] = $operation->expandExpressions($v);
            return (new $className($params))->$method(...$args);
        }
    }

    public function __call($method, $arguments)
    {
        if ($method == 'class') {
            return call_user_func_array([$this, '_class'], $arguments);
        } else {
            throw new
                Error([
                    'status' => 'UNSUPPORTED_OPERATION',
                    'details' => "No such method: $method"
                ]);
        }
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\Instance newly constructed from
     * the given "instance" element
     *
     * @param DOMElement $elt An element with localName "instance"
     *   conforming to the schema "operation.xsd"
     * @return CodeRage\Test\Operations\Instance
     */
    public static function load(\DOMElement $elt)
    {
        $class = $elt->getAttribute('class');
        if (!$class)
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing class'
                ]);
        $classPath = Xml::getAttribute($elt, 'classPath');
        $params = [];
        foreach (Xml::childElements($elt, 'param') as $k)
            $params[$k->getAttribute('name')] = $k->getAttribute('value');
        return new self($class, $classPath, $params);
    }

    public function save(\DOMDocument $dom, ?AbstractOperation $parent)
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'instance');
        $elt->setAttribute('class', $this->class());
        if ($classPath = $this->classPath())
            $elt->setAttribute('classPath', $classPath);
        foreach ($this->params() as $n => $v) {
            $param = $dom->createElementNS(self::NAMESPACE_URI, 'param');
            $param->setAttribute('name', $n);
            $param->setAttribute('value', $v);
            $elt->appendChild($param);
        }
        return $elt;
    }

    /**
     * The class name, specified as a sequence of identifiers separated by dots
     *
     * @var string
     */
    private $class;

    /**
     * The directory to be searched for class definitions, if any; the source
     * file to be search for is formed from the value of the "class" by
     * replacing dots with slashes and appending '.php' or '.pm'
     *
     * @var string
     */
    private $classPath;

    /**
     * An associative array of constructor parameters
     *
     * @var array
     */
    private $params;
}
