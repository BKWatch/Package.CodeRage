<?php

/**
 * Defines the class CodeRage\Test\Operations\Operation
 *
 * File:        CodeRage/Test/Operations/Operation.php
 * Date:        Mon Apr 30 22:48:17 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Operations;

use DOMDocument;
use DOMElement;
use DateTime;
use Exception;
use stdClass;
use Throwable;
use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Test\PathExpr;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\NativeDataEncoder;
use CodeRage\Util\XmlEncoder;
use CodeRage\Xml;


/**
 * Represents an operation invocation
 */
final class Operation extends OperationBase implements Schedulable {

    /**
     * Constructs an instance of CodeRage\Test\Operations\Operation
     *
     * @param string $description The operation description
     * @param CodeRage\Util\Properties $properties The collection of properties
     * @param array $configProperties An associative array of configuration
     *   variables
     * @param CodeRage\Util\NativeDataEncoder $nativeDataEncoder The native data
     *   encoder, if any
     * @param CodeRage\Util\XmlEncoder $xmlEncoder The XML encoder
     * @param CodeRage\Test\Operations\DataMatcher $dataMatcher The data
     *   matcher
     * @param CodeRage\Test\Operations\Termintor $terminator The terminator
     *   matcher
     * @param CodeRage\Test\Operations\Schedule $schedule The schedule of the
     *   operation. It should be null if operation is not the part of scheduled
     *   operation list
     * @param CodeRage\Test\Operations\Instance $instance The object whose
     *   method is invoked, if any, represented as input to
     *   CodeRage\Util\Factorr::create()
     * @param array $input The array of arguments, if any, represented as native
     *   data structures, i.e., values composed from scalars using indexed
     *   arrays and instances of stdClass
     * @param mixed $output The return value, if any, represented as a native
     *   data structure, i.e., a value composed from scalars using indexed
     *   arrays and instances of stdClass
     * @param CodeRage\Test\Operations\Exception $exception The exception,
     *   if any,
     *   that the operation throws
     * @param CodeRage\Test\Operations\OperationList $parent The containing
     *   operation list, if any
     * @param int $index he position of the operation within its parent's list
     *   of operations, if any
     * @param string $path The path to the XML description of this operation, if
     *   any
     */
    private function
        __construct( $description, $properties, $configProperties, $nativeDataEncoder,
                     $xmlEncoder, $dataMatcher, $terminator, $schedule, $name,
                     $instance, $input, $output, $exception, $parent, $index,
                     $path )
    {
        $this->description = $description;
        $this->properties = $properties;
        $this->configProperties = $configProperties;
        $this->nativeDataEncoder = $nativeDataEncoder;
        $this->xmlEncoder = $xmlEncoder;
        $this->dataMatcher = $dataMatcher;
        $this->terminator = $terminator;
        $this->schedule = $schedule;
        $this->name = $name;
        $this->instance = $instance;
        $this->input = $input;
        $this->output = $output;
        $this->exception = $exception;
        $this->parent = $parent;
        $this->index = $index;
        $this->path = $path;
    }

    public function __destruct()
    {
        $this->clearContext();
    }

        /*
         * Accessor methods
         */

    /**
     * Returns the description
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Returns the collection of properties
     *
     * @return CodeRage\Util\Properties
     */
    public function properties()
    {
        return $this->properties;
    }

    /**
     * Returns the associative array of configuration variables, if any
     *
     * @return array
     */
    public function configProperties()
    {
        return $this->configProperties;
    }

    /**
     * Returns the native data encoder
     *
     * @return CodeRage\Util\NativeDataEncoder
     */
    public function nativeDataEncoder()
    {
        return $this->nativeDataEncoder;
    }

    /**
     * Returns the XML encoder
     *
     * @return CodeRage\Util\XmlEncoder
     */
    public function xmlEncoder()
    {
        return $this->xmlEncoder;
    }

    /**
     * Returns the data matcher
     *
     * @return CodeRage\Test\Operations\DataMatcher
     */
    public function dataMatcher()
    {
        return $this->dataMatcher;
    }

    /**
     * Returns the terminator, if any
     *
     * @return CodeRage\Test\Operations\Terminator
     */
    public function terminator()
    {
        return $this->terminator;
    }

    /**
     * Returns the schedule
     *
     * @return CodeRage\Test\Operations\Schedule
     */
    public function schedule()
    {
        return $this->schedule;
    }

    /**
     * Returns the operation name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returns the object whose method is invoked, if any, represented as input
     * to CodeRage\Util\Factorr::create()
     *
     * @return CodeRage\Test\Operations\Instance
     */
    public function instance()
    {
        return $this->instance;
    }

    /**
     * Returns the array of arguments, if any, represented as native data
     * structures, i.e., values composed from scalars using indexed arrays and
     * instances of stdClass
     *
     * @return array
     */
    public function input()
    {
        return $this->input;
    }

    /**
     * Returns the return value, if any, represented as a native data structure,
     * i.e., a value composed from scalars using indexed arrays and instances of
     * stdClass
     *
     * @return mixed
     */
    public function output()
    {
        return $this->output;
    }

    /**
     * Returns the exception, if any, that the operation throws
     *
     * @return CodeRage\Test\Operations\Exception
     */
    public function exception()
    {
        return $this->exception;
    }

    /**
     * Returns the containing operation list, if any
     *
     * @return CodeRage\Test\Operations\OperationList
     */
    public function parent()
    {
        return $this->parent;
    }

    /**
     * Returns the position of this operation within the operation list of its
     * parent, if any
     *
     * @return int
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * Returns the path to the XML description of this operation, if any
     *
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Sets the path property of operation
     *
     * @param string $path - The path to the XML description of this operation
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

        /*
         * Operation execution methods
         */

    /**
     * Executes this operation by invoking the named function or method on
     * the underlying instance, if any, with the arguments specified by the
     * 'input' property
     *
     * @return mixed
     */
    public function execute()
    {
        $result = null;
        $prev = null;
        try {
            if ($this->parent === null || $this->index == 0)
                $this->clearContext();
            $input = $this->input;
            $context = $this->localContext();
            $context->input =
                Array_::map([$this, 'expandExpressions'], $this->input);
            $prev = $this->installConfig();
            $name = $this->name;
            if ($this->instance) {
                $result =
                    $this->instance->invokeMethod($this, $name, ...$context->input);
            } else {
                $result = $name(...$context->input);
            }
            return $context->output = $this->nativeDataEncoder->encode($result);
        } catch (Throwable $e) {
            $context->exception = $e instanceof Error ?
                (object) [
                    'status' => $e->status(),
                    'message' => $e->message()
                ] :
                (object) [
                    'message' => $e->getMessage()
                ];
            throw $e;
        } finally {
            if ($prev !== null)
                Config::setCurrent($prev);
            $this->clearConfig();
            $this->input = $input;
        }
    }

    /**
     * Invokes the underlying operation and throws an exception if the output
     * or exception does not match the expected output or exception.
     *
     * @throws CodeRage\Error
     */
    public function test()
    {
        $message = "Failed testing $this";

        // Construct excpected results
        $expected = (object) ['input' => $this->localContext()->input];
        if ($this->output !== null) {
            $expected->output = $this->output;
        } elseif ($this->exception !== null) {

            // Don't include 'class' property, since class comparison must be
            // sensitive to inheritance (see below)
            $exception = $this->exception;
            $expected->exception = $exception->class() == 'CodeRage\\Error' ?
                (object) [
                    'status' => $exception->status(),
                    'message' => $exception->message()
                ] :
                (object) [
                    'message' => $exception->message()
                ];
        } else {
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' =>
                        "$message: missing 'output' or 'exception' property"
                ]);
        }

        // Construct actual results
        $exception = null;
        try {
            $this->execute();
        } catch (Throwable $e) {
            $exception = $e;
        }
        $actual = $this->localContext();

        // Check for early termination
        $path =
            PathExpr::parse(
                $this->parent !== null ?
                    "/operation[{$this->index}]" :
                    "/"
            );
        if ($this->terminator !== null)
            $this->terminator->check(
                $actual,
                $path
            );

        // Check for output/exception mismatch
        if ($exception != null) {
            if (!isset($expected->exception)) {
                $at = $this->parent !== null ?
                    " at /operation[{$this->index}]" :
                    "";
                throw new
                    Error([
                        'details' =>
                            "$message: failed testing operation: " .
                            "expected output$at; found exception $exception"
                    ]);
            }
            $class = $this->exception->class();
            if ($class !== null && !($exception instanceof $class)) {
                $at = $this->parent !== null ?
                    " at /operation[{$this->index}]" :
                    "";
                throw new
                    Error([
                        'details' =>
                            "$message: expected exception of type " .
                            "$class$at; found exception $exception"
                    ]);
            }
        } elseif (isset($expected->exception)) {
            $at = $this->parent !== null ?
                " at /operation[{$this->index}]" :
                "";
            throw new
                Error([
                    'details' =>
                        "$message: expected exception$at; found output"
                ]);
        }

        // Compare actual and expected results
        $this->dataMatcher->assertMatch(
            $actual,
            $expected,
            $message,
            $path
        );
    }

    /**
     * Sets the output or exception property of this operation set to reflect
     * the result of invoking the underlying operation
     */
    public function generate()
    {
        $this->output = null;
        $this->exception = null;
        try {
            $this->output = $this->execute();
            if ($this->parent === null)
                $this->normalize();
        } catch (Throwable $e) {
            $this->exception =
                new \CodeRage\Test\Operations\Exception(
                        get_class($e),
                        ($e instanceof Error ? $e->status() : null),
                        $e->getMessage(),
                        ($e instanceof Error ? $e->details() : null)
                    );
        }
    }

    /**
     * Creates XML operation or operation list descriptions in the specified
     * target directory by parsing the XML documents in the given source
     * directory, invoking generate() on the resulting objects, and then saving
     * them.
     *
     * @param array $options The options array; supports the following options:
     *   source - The source directory
     *   target - The target directory
     *   extensions - A list of file extensions to process; if not supplied,
     *     only files with the extension 'xml' will be processed
     *   initialize - A callable to invoke before processing each operation or
     *     operation list; accepts the operation or operation list as argument
     *   cleanup - A callable to invoke before processing each operation or
     *     operation list; accepts the result of invoking generate() as argument
     */
    public static function generateAll($options)
    {
        $initialize = isset($options['initialize']) ?
            $options['initialize'] :
            null;
        $cleanup = isset($options['cleanup']) ?
            $options['cleanup'] :
            null;
        $options['callback'] =
            function($source, $target, $callbacks)
            {
                self::generateAllimpl($source, $target, $callbacks);
            };
        $options['data'] = [$initialize, $cleanup];
        self::processDirectories($options);
    }

    /**
     * Callback passed to processDirectories() by generateAll()
     *
     * @param string $source The path of an XML operation or operation list
     *   definition
     * @param string $target The path of the file to which the resulting
     *   XML operation or operation list definition will be written
     * @param array $callbacks A pair ($initialize, $cleanup) of callables be
     *   invoked before and after executing the operation or operation list
     */
    private static function generateAllImpl($source, $target, $callbacks)
    {
        list($initialize, $cleanup) = $callbacks;
        $op = self::load($source);
        if ($initialize) {
            try {
                $initialize($op);
            } catch (Throwable $e) {
                throw new
                    Error([
                        'details' =>
                            "Failed initializing operation '$source'",
                        'inner' => $e
                    ]);
            }
        }
        $op->generate();
        if ($cleanup) {
            try {
                $cleanup($op);
            } catch (Throwable $e) {
                throw new
                    Error([
                        'details' =>
                            "Failed cleaning up operation '$source'",
                        'inner' => $e
                    ]);
            }
        }
        $op->save($target);
    }

        /*
         * Utility methods
         */

    /**
     * Executes the given callback once for each file in the specified source
     * directory that meets the specified criterion. The callback is executed
     * with three arguments: the path of a file in the source directory, the
     * path of the corresponding file in the target directory (which need not
     * exist), and the specified data.
     *
     * @param array $options The options array; supports the following options:
     *   source - The source directory
     *   target - The target directory
     *   extensions - A list of file extensions to process; if not supplied,
     *     only files with the extension 'xml' will be processed
     *   callback - A callable
     *   data - A value to be passed as the third argument to the callback
     */
    public static function processDirectories($options)
    {
        // Validate $source
        if (!isset($options['source']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing source directory'
                ]);
        $source = $options['source'];
        if (!file_exists($source))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "No such directory: $source"
                ]);
        if (!is_dir($source))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The file '$source' is not a directory"
                ]);

        // Validate $target
        if (!isset($options['target']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing target directory'
                ]);
        $target = $options['target'];
        if (!file_exists($target))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "No such directory: $target"
                ]);
        if (!is_dir($target))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The file '$target' is not a directory"
                ]);

        // Validate callback
        if (!isset($options['callback']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'details' => 'Missing callback'
                ]);
        $callback = $options['callback'];

        // Process extensions and data
        $ext = isset($options['extensions']) ?
            $options['extensions'] :
            ['xml'];
        $data = isset($options['data']) ?
            $options['data'] :
            null;

        // Construct file name filter
        $isXml = '#\.(' . join('|', array_map('preg_quote', $ext)) . ')$#';

        // Iterate over directory
        $dir = null;
        if (!($dir = @opendir($source)))
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'details' => "Failed reading directory '$source'"
                ]);
        while (false !== ($file = @readdir($dir))) {
            $path = "$source/$file";
            if (is_file("$source/$file") && preg_match($isXml, $file))
                $callback($path, "$target/$file", $data);
        }
        @closedir($dir);
    }

    /**
     * Returns a newly constructed object based on the underling instance of
     * CodeRage\Test\Operations\Instance
     *
     * @return object
     */
    public function constructInstance()
    {
        if (!$this->instance)
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'details' =>
                        'Missing instance of CodeRage\Test\Operations\Instance'
                ]);
         return $this->instance->construct($this);
    }

    /**
     * Normalizes this operation's output using its data matcher's patterns
     *
     * @return object
     */
    public function normalize()
    {
        $context = (object) ['output' => &$this->output];
        $path =
            PathExpr::parse(
                $this->parent !== null ?
                    "/operation[{$this->index}]" :
                    "/"
            );
        $this->dataMatcher->normalize($context, $path);
    }

    public function __toString()
    {
        $name = $this->name;
        $path = $this->path;
        return "operation '$name'" . ($path ? " in '$path'" : '');
    }

        /*
         * Load and save methods
         */

    /**
     * Returns an instance of CodeRage\Test\Operations\Operation or
     * CodeRage\Test\Operations\OpertationList newly constructed from the specified file
     *
     * @param string $path The path to an XML document conforming to the schema
     *   "operation.xsd" and having document element "operation" or
     *   "operationList"
     * @param array $options The options array; supports the following options:
     *     includeXpath - An XPath expression or list of XPath expressions
     *       evaluating to 0 or 1 when applied to the parsed XML document; if
     *       any expression evaluates to 0, the return value will be null;
     *       the prefix "x" can be used to reference the operations namespace
     *       (optional)
     *     excludeXpath - An XPath expression or list of XPath expressions
     *       evaluating evaluating to 0 or 1 when applied to the parsed XML
     *       document; if any expression evaluates to 1, the return value will
     *       be null; the prefix "x" can be used to reference the operations
     *       namespace (optional)
     *     includePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML document; if any
     *       expression fails to match, the return value will be null
     *       (optional)
     *     excludePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML document; if any
     *       expression matches, the return value will be null (optional)
     *    baseUri - The URI for resolving relative paths referenced by $elt
     * @return CodeRage\Test\Operations\Operation
     */
    public static function load($path, $options = [])
    {
        try {
            $dom = Xml::loadDocument($path);
        } catch (Throwable $e) {
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' => "Failed parsing '$path'",
                    'inner' => $e
                ]);
        }
        $dom->xinclude();
        $dom->schemaValidate(self::SCHEMA_PATH);
        $elt = $dom->documentElement;

        // Evaluates xpath expression
        $baseUri = isset($options['baseUri']) ? $options['baseUri'] : $path;
        if (!self::filter($dom, $options))
            return null;
        if ($elt->localName == 'operation') {
            $operation = self::loadXml($elt, $baseUri);
            if ($operation->schedule() !== null)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'message' =>
                            'Only operations in scheduled operation ' .
                            'lists can have schedules'
                    ]);
             return $operation;
        } elseif ($elt->localName == 'operationList') {
            return OperationList::loadXml($elt, $baseUri);
        } elseif ($elt->localName == 'scheduledOperationList') {
            return ScheduledOperationList::loadXml($elt, $baseUri);
        } else {
            throw new
                Error([
                    'status' => 'UNEXPECTED_CONTENT',
                    'message' =>
                        "Failed parsing '$path': expected 'operation' or " .
                        "'operationList' element; found '" . $elt->localName .
                        "'"
                ]);
        }
    }

    /**
     * Returns a list of instances of CodeRage\Test\Operations\Operation and
     * CodeRage\Test\Operations\OperationList newly constructed from XML
     * documents in the specified directory
     *
     * @param string $path The directory
     * @param array $ext A list of file extensions to process; if not supplied,
     *   only files with the extension 'xml' will be processed
     * @param array $options The options array; supports the following options:
     *     includeXpath - An XPath expression or list of XPath expressions
     *       evaluating to 0 or 1 when applied to the parsed XML documents; if
     *       any expression evaluates to 0, the the operation will be excluded
     *       from the list; the prefix "x" can be used to reference the
     *       operations namespace (optional)
     *     excludeXpath - An XPath expression or list of XPath expressions
     *       evaluating evaluating to 0 or 1 when applied to the parsed XML
     *       documents; if any expression evaluates to 1, the the operation will
     *       be excluded from the list; the prefix "x" can be used to reference
     *       the operations namespace (optional)
     *     includePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML documents; if any
     *       expression fails to match, the the operation will be excluded
     *       from the list (optional)
     *     excludePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML documents; if any
     *       expression matches, the the operation will be excluded  from the
     *       list (optional)
     * @return array
     */
    public static function loadAll($path, $ext = null, array $options = [])
    {
        // Construct file name filter
        if ($ext === null)
            $ext = ['xml'];
        $isXml = '#\.(' . join('|', array_map('preg_quote', $ext)) . ')$#';

        // Validate $path
        if (!file_exists($path))
            throw new
                Error([
                    'status' => 'OBJECT_DOES_NOT_EXIST',
                    'details' => "No such directory: $path"
                ]);
        if (!is_dir($path))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "The file '$path' is not a directory"
                ]);

        // Iterate over directory
        $operations = [];
        $dir = null;
        if (!($dir = @opendir($path)))
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'details' => "Failed reading directory '$path'"
                ]);
        while (false !== ($file = @readdir($dir))) {
            if (!is_file("$path/$file") || !preg_match($isXml, $file))
                continue;
            $result = self::load("$path/$file", $options);
            if ($result !== null)
                $operations[] = $result;
        }
        @closedir($dir);

        return $operations;
    }

    /**
     * Creates an XML document from this operation and saves it to the given
     * path
     *
     * @param string $path The path
     */
    public function save($path)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $dom->appendChild(
            $dom->createComment(
                "AUTOMATICALLY GENERATED BY CODERAGE TOOLS - DO NOT EDIT"
            )
        );
        $dom->appendChild($dom->createComment('Copyright CodeRage'));
        $dom->appendChild($this->saveXml($dom));
        $dom->loadXml($dom->saveXml(), LIBXML_NSCLEAN);  // Tidy namespaces
        $dom->save($path);
    }

    /**
     * Returns an instance of CodeRage\Test\Operations\Operation newly
     * constructed from the specified XML element
     *
     * @param string $elt An element with localName "operation" conforming
     *   to the schema "operation.xsd"
     * @param string $baseUri The URI for resolving relative paths referenced by
     *   $elt
     * @param CodeRage\Test\Operations\OperationList $parent The containing
     *   operation list, if any
     * @return CodeRage\Test\Operations\Operation
     */
    public static function loadXml(DOMElement $elt, $baseUri, $parent = null, $index = null)
    {
        $description = $properties = $config = $nativeDataEncoder = $xmlEncoder =
            $dataMatcher = $terminator = $schedule = $instance = $name =
                $input = $output = $exception = null;
        if ($index === null) {
            $index =
                $parent !== null ?
                    count($parent->operations()) :
                    null;
        }
        $hasNativeDataEncoding = $hasXmlEncoding = $hasDataMatching = false;
        foreach (Xml::childElements($elt) as $k) {
            switch ($k->localName) {
                case 'description':
                    $description = Xml::textContent($k);
                    break;
                case 'properties':
                    $properties = new \CodeRage\Util\BasicProperties;
                    foreach (Xml::childElements($k, 'property') as $property)
                        $properties->setProperty(
                            $property->getAttribute('name'),
                            $property->getAttribute('value')
                        );
                    break;
                case 'config':
                        $config = self::loadConfig($k);
                    break;
                case 'nativeDataEncoding':
                    $nativeDataEncoder =
                        self::loadNativeDataEncoder($k);
                    $hasNativeDataEncoding = true;
                    break;
                case 'xmlEncoding':
                    $xmlEncoder = self::loadXmlEncoder($k);
                    $hasXmlEncoding = true;
                    break;
                case 'dataMatching':
                    if (!$xmlEncoder)
                        $xmlEncoder = self::createXmlEncoder();
                    if ($index < 0)
                        throw new
                            Error([
                                'status' => 'UNEXPECTED_CONTENT',
                                'message' =>
                                    "Repeating operations may not have " .
                                    "'dataMatching' elements"
                            ]);
                    $dataMatcher =
                        DataMatcher::load(
                            $k,
                            $xmlEncoder,
                            $index
                        );
                    $hasDataMatching = true;
                    break;
                case 'termination':
                    if (!$xmlEncoder)
                        $xmlEncoder = self::createXmlEncoder();
                    if ($parent == null)
                        throw new
                            Error([
                                'status' => 'UNEXPECTED_CONTENT',
                                'message' =>
                                    "Only operations in operation lists may " .
                                    "have 'termination' elements"
                            ]);
                    if ($index < 0)
                        throw new
                            Error([
                                'status' => 'UNEXPECTED_CONTENT',
                                'message' =>
                                    "Repeating operations may not have " .
                                    "'termination' elements"
                            ]);
                    $terminator =
                        Terminator::load(
                            $k,
                            $xmlEncoder,
                            $index
                        );
                    break;
                case 'schedule':
                    $schedule = Schedule::load($k);
                    break;
                case 'name':
                    $name = Xml::textContent($k);
                    break;
                case 'instance':
                    $instance = Instance::load($k);
                    break;
                case 'input':
                    if (!$xmlEncoder)
                        $xmlEncoder = self::createXmlEncoder();
                    $input = [];
                    foreach (Xml::childElements($k, 'arg') as $arg)
                        $input[] = $xmlEncoder->decode($arg, false, true);
                    break;
                case 'output':
                    if (!$xmlEncoder)
                        $xmlEncoder = self::createXmlEncoder();
                    $output = $xmlEncoder->decode($k);
                    break;
                case 'exception':
                    $exception = \CodeRage\Test\Operations\Exception::load($k);
                    break;
            }
        }
        if (!$nativeDataEncoder)
            $nativeDataEncoder = new NativeDataEncoder;
        if (!$xmlEncoder)
            $xmlEncoder = new XmlEncoder;
        if (!$dataMatcher)
            $dataMatcher = new DataMatcher($xmlEncoder);
        if (!$input)
            $input = [];
        if (!$name)
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing name'
                ]);
        if ($output && $exception)
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "Properties 'output' and 'exception' both present"
                ]);
        $path = $baseUri !== null ?
            $baseUri :
            Xml::documentPath($elt->ownerDocument);
        $op =
            new Operation(
                    $description,
                    $properties,
                    $config,
                    $nativeDataEncoder,
                    $xmlEncoder,
                    $dataMatcher,
                    $terminator,
                    $schedule,
                    $name,
                    $instance,
                    $input,
                    $output,
                    $exception,
                    $parent,
                    $index,
                    $path
                );
        $op->hasNativeDataEncoding = $hasNativeDataEncoding;
        $op->hasXmlEncoding = $hasXmlEncoding;
        $op->hasDataMatching = $hasDataMatching;
        return $op;
    }

    /**
     * Returns an XML element representing this instance of
     * CodeRage\Test\Operations\Operation
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @return DOMElement
     */
    public function saveXml(DOMDocument $dom)
    {
        $elt = XmlEncoder::createElement($dom, 'operation', self::NAMESPACE_URI);

        // Handle description
        $this->appendElement($dom, $elt, 'description', $this->description);

        // Handle properties
        if ($this->properties !== null) {
            if (!empty($this->properties->propertyNames())) {
                $elt->appendChild(
                    $this->saveProperties($dom)
                );
            }
        }

        // Handle configuration
        if ($this->configProperties !== null) {
            $elt->appendChild(
                $this->saveConfig($dom, $this->configProperties)
            );
        }

        // Handle encoders
        if ($this->hasNativeDataEncoding)
            $elt->appendChild(
                $this->saveNativeDataEncoder($dom, $this->nativeDataEncoder)
            );
        if ($this->hasXmlEncoding)
            $elt->appendChild($this->saveXmlEncoder($dom, $this->xmlEncoder));

        // Handle data matcher, set dataMatcher only in case of non-repeating
        // operations
        if ($this->hasDataMatching)
            if ($this->index >= 0)
                $elt->appendChild(
                    $this->dataMatcher->save($dom, $this->parent)
                );

        // Handle terminator
        if ($this->terminator !== null)
            $elt->appendChild($this->terminator->save($dom, $this->parent));

        // Handle schedule
        if ($this->schedule !== null)
            $elt->appendChild($this->schedule->save($dom, $this->parent));

        // Handle name
        $this->appendElement($dom, $elt, 'name', $this->name);

        // Handle instance
        if ($instance = $this->instance)
            $elt->appendChild($this->instance->save($dom, $this->parent));

        // Handle input
        $input = $dom->createElementNS(self::NAMESPACE_URI, 'input');
        $elt->appendChild($input);
        foreach ($this->input as $arg) {
            $input->appendChild(
                $this->xmlEncoder->encode('arg', $arg, $dom)
            );
        }

        // Handle output
        if ($this->output !== null) {
            $xmlEncoder =
                new XmlEncoder([
                        'namespace' => $this->xmlEncoder->namespace(),
                        'listElements' => $this->xmlEncoder->listElements(),
                        'xsiNilAttribute' => false,
                        'xsiTypeAttribute' => false
                    ]);
            $elt->appendChild(
                $xmlEncoder->encode('output', $this->output, $dom)
            );
        }

        // Handle exception
        if ($this->exception !== null)
            $elt->appendChild($this->exception->save($dom, $this->parent));

        return $elt;
    }

    /**
     * Returns true if the given parsed XML document satsifies the conditions
     * specified in the given options array
     *
     * @param DOMDocument $dom
     * @param array $options The options array; supports the following options:
     *     includeXpath - An XPath expression or list of XPath expressions
     *       evaluating to 0 or 1 when applied to the parsed XML document; if
     *       any expression evaluates to 0, the return value will be false;
     *       the prefix "x" can be used to reference the operations namespace
     *       (optional)
     *     excludeXpath - An XPath expression or list of XPath expressions
     *       evaluating evaluating to 0 or 1 when applied to the parsed XML
     *       document; if any expression evaluates to 1, the return value will
     *       be false; the prefix "x" can be used to reference the operations
     *       namespace (optional)
     *     includePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML document; if any
     *       expression fails to match, the return value will be false
     *       (optional)
     *     excludePattern - A regular expression or list of regular expressions
     *       to be matched against the path of the XML document; if any
     *       expression matches, the return value will be false (optional)
     * @return boolean
     */
    public static function filter(DOMDocument $dom, array $options)
    {
        // Apply XPath expressions
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('x', self::NAMESPACE_URI);
        $handler = new \CodeRage\Util\ErrorHandler;
        if (isset($options['includeXpath'])) {
            if (!is_array($options['includeXpath']))
                $options['includeXpath'] = [$options['includeXpath']];
            foreach ($options['includeXpath'] as $x)
                Args::check($x, 'string', 'xpath include expression');
            foreach ($options['includeXpath'] as $x) {
                $result = $handler->_evaluate($xpath, $x, $dom);
                if ($handler->errno())
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                'Failed evaluating xpath include expression'
                        ]);
                if (!$result)
                    return false;
            }
        }
        if (isset($options['excludeXpath'])) {
            if (!is_array($options['excludeXpath']))
                $options['excludeXpath'] = [$options['excludeXpath']];
            foreach ($options['excludeXpath'] as $x)
                Args::check($x, 'string', 'xpath exclude expression');
            foreach ($options['excludeXpath'] as $x) {
                $result = $handler->_evaluate($xpath, $x, $dom);
                if ($handler->errno())
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                'Failed evaluating xpath exclude expression'
                        ]);
                if ($result)
                    return false;
            }
        }

        // Apply regular expressions
        $parts = preg_split('#[/\\\]#', Xml::documentPath($dom));
        $path = join('/', array_slice($parts, -2));
        if (isset($options['includePattern'])) {
            if (!is_array($options['includePattern']))
                $options['includePattern'] = [$options['includePattern']];
            foreach ($options['includePattern'] as $p)
                Args::check($p, 'regex', 'include pattern');
            foreach ($options['includePattern'] as $p) {
                if (!preg_match($p, $path))
                    return false;
            }
        }
        if (isset($options['excludePattern'])) {
            if (!is_array($options['excludePattern']))
                $options['excludePattern'] = [$options['excludePattern']];
            foreach ($options['excludePattern'] as $p)
                Args::check($p, 'regex', 'exclude pattern');
            foreach ($options['excludePattern'] as $p)
                if (preg_match($p, $path))
                    return false;
        }

        return true;
    }

    /**
     * Returns a callable that expands the placeholder __FILE__ and evaluates
     * path expressions
     *
     * @return callable
     */
    protected function expressionEvaluator()
    {
        return
            function($expr)
            {
                if (strpos($expr, '__FILE__') !== false)
                    return str_replace(
                               '__FILE__', dirname($this->path), $expr
                           );
                if (strpos($expr, '__DIR__') !== false)
                    return str_replace(
                               '__DIR__', dirname($this->path), $expr
                           );
                $expr = PathExpr::parse($expr);
                $context = $expr->isAbsolute() ?
                    $this->globalContext() :
                    $this->localContext();
                $parentName = $this->parent !== null && $expr->isAbsolute() ?
                    'operations' :
                    null;
                return $expr->evaluate($context, $this->xmlEncoder, $parentName);
            };
    }

    /**
     * Returns an instance of CodeRage\Util\Properties newly
     * constructed from the given "properties" element
     *
     * @param DOMElement $elt An element with localName "properties"
     *   conforming to the schema "operation.xsd"
     * @return CodeRage\Util\Properties
     */
    private static function loadProperties(DOMDocument $dom)
    {
        $properties = new \CodeRage\Util\Properties;
        foreach (Xml::childElements($k, 'property') as $property)
            $properties->setProperty(
                $property->getAttribute('name'),
                $property->getAttribute('value')
            );
        return $properties;
    }

    /**
     * Returns an XML element representing the given properties list
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @return DOMElement
     */
    private function saveProperties(DOMDocument $dom)
    {
        $properties =
            $dom->createElementNS(self::NAMESPACE_URI, 'properties');
        foreach ($this->properties->propertyNames() as $name) {
            $property = $dom->createElementNS(self::NAMESPACE_URI, 'property');
            $property->setAttribute('name', $name);
            $property->setAttribute(
                'value',
                $this->properties->getProperty($name)
            );
            $properties->appendChild($property);
        }
        return $properties;
    }

    /**
     * Returns an associative array of configuration variables newly
     * constructed from the given "config" element
     *
     * @param DOMElement $elt An element with localName "config"
     *   conforming to the schema "operation.xsd"
     * @return array $config
     */
    private static function loadConfig(DOMElement $elt)
    {
        $config = [];
        foreach (Xml::childElements($elt, 'property') as $k)
            $config[$k->getAttribute('name')] = $k->getAttribute('value');
        return $config;
    }

    /**
     * Returns an XML element representing the given associative array of
     * configuration variables
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @param array $config An associative array of configuration variables
     * @return DOMElement
     */
    private function saveConfig(DOMDocument $dom, $config)
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'config');
        foreach ($config as $n => $v) {
            $property = $dom->createElementNS(self::NAMESPACE_URI, 'property');
            $property->setAttribute('name', $n);
            $property->setAttribute('value', $v);
            $elt->appendChild($property);
        }
        return $elt;
    }

    /**
     * Returns an instance of CodeRage\Util\NativeDataEncoder newly
     * constructed from the given "nativeDataEncoding" element
     *
     * @param DOMElement $elt An element with localName "nativeDataEncoding"
     *   conforming to the schema "operation.xsd"
     * @return CodeRage\Util\NativeDataEncoder
     */
    private static function loadNativeDataEncoder(DOMElement $elt)
    {
        $options = [];
        foreach (Xml::childElements($elt, 'option') as $k)
            $options[$k->getAttribute('name')] = $k->getAttribute('value');
        return new NativeDataEncoder($options);
    }

    /**
     * Returns an XML element representing the given native data encoder
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @param CodeRage\Util\NativeDataEncoder $encoder
     * @return DOMElement
     */
    private function
        saveNativeDataEncoder(
            DOMDocument $dom,
            NativeDataEncoder $encoder
        )
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'nativeDataEncoding');
        foreach ($encoder->options() as $n => $v) {
            $option = $dom->createElementNS(self::NAMESPACE_URI, 'option');
            $option->setAttribute('name', $n);
            $option->setAttribute('value', $v);
            $elt->appendChild($option);
        }
        return $elt;
    }

    /**
     * Returns an instance of CodeRage\Util\XmlEncoder newly
     * constructed from the given "xmlEncoding" element
     *
     * @param DOMElement $elt An element with localName "xmlEncoding"
     *   conforming to the schema "operation.xsd"
     * @return CodeRage\Util\XmlEncoder
     */
    private static function loadXmlEncoder(DOMElement $elt)
    {
        $namespace = Xml::getAttribute($elt, 'namespace');
        $listElements = [];
        foreach (Xml::childElements($elt, 'listElement') as $k)
            $listElements[$k->getAttribute('name')] =
                $k->getAttribute('itemName');
        return new
            XmlEncoder([
                'namespace' => $namespace,
                'listElements' => $listElements,
                'xsiNilAttribute' => true,
                'xsiTypeAttribute' => true
            ]);
    }

    /**
     * Returns a newly constructed instance of CodeRage\Util\XmlEncoder that
     * respects "nil" and "type" attributes in the XML Schema-instance namespace
     */
    private static function createXmlEncoder()
    {
        return new
            XmlEncoder([
                'xsiNilAttribute' => true,
                'xsiTypeAttribute' => true
            ]);
    }

    /**
     * Returns an XML element representing the given XML encoder
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @param CodeRage\Util\XmlEncoder $encoder
     * @return DOMElement
     */
    private function
        saveXmlEncoder(
            DOMDocument $dom,
            XmlEncoder $encoder
        )
    {
        $elt = $dom->createElementNS(self::NAMESPACE_URI, 'xmlEncoding');
        if ($namespace = $encoder->namespace())
            $elt->setAttribute('namespace', $namespace);
        $listElements = $encoder->listElements();
        ksort($listElements);
        foreach ($listElements as $n => $i) {
            $list = $dom->createElementNS(self::NAMESPACE_URI, 'listElement');
            $list->setAttribute('name', $n);
            $list->setAttribute('itemName', $i);
            $elt->appendChild($list);
        }
        return $elt;
    }

    /**
     * Returns an XML element representing the given schedule
     *
     * @param DOMDocument $dom An instance of DOMDocument used for constructing
     *   XML elements
     * @param CodeRage\Test\Operations\Schedule $schedule
     * @return DOMElement
     */
    private function saveSchedule(DOMDocument $dom, schedule $schedule)
    {
        return $schedule->save($dom, $this->parent);
    }

    /**
     * Returns a native data structure built incrementatlly during the execution
     * of execute(), test(), or generate(), suitable for evaluating path
     * expressions embedded in operation input relative to the outermost
     * operation or operation list
     *
     * @return mixed
     */
    private function globalContext()
    {
        if (self::$contexts === null)
            self::$contexts = new \SplObjectStorage;
        if ($this->parent === null) {
            if (!isset(self::$contexts[$this]))
                self::$contexts[$this] = (object) ['input' => &$this->input];
            return self::$contexts[$this];
        } else {
            if (!isset(self::$contexts[$this->parent])) {
                $ops = [];
                foreach ($this->parent->operations() as $op)
                    $ops[$op->index] = (object) ['input' => &$op->input];
                self::$contexts[$this->parent] = (object) ['value' => &$ops];
            }
            return self::$contexts[$this->parent]->value;
        }
    }

    /**
     * Returns a native data structure built incrementatlly during the execution
     * of execute(), test(), or generate(), suitable for evaluating path
     * expressions embedded in operation input relative to the operation itself
     *
     * @return mixed
     */
    private function localContext()
    {
        $context = $this->globalContext();
        return $this->parent === null ?
            $context :
            $context[$this->index];
    }

    /**
     * Clears this operation's state and that of its parent, if any
     */
    private function clearContext()
    {
        if ($this->parent === null) {
            unset(self::$contexts[$this]);
        } else {
            unset(self::$contexts[$this->parent]);
        }
    }

    /**
     * Returns the same result as the built-in function gettype(), except that
     * the type is reported as 'scalar' for scalars and null values
     *
     * @param mixed $value
     */
    private static function getType($value)
    {
        return is_scalar($value) || is_null($value) ?
            'scalar' :
            \gettype($value);
    }

    /**
     * Container associating operations and operation lists with native data
     * structures built incrementatlly during the execution of execute(),
     * test(), or generate(), suitable for evaluating path expressions embedded
     * in operation input
     *
     * @var SplObjectStorage
     */
    private static $contexts;

    /**
     * The operation description
     *
     * @var string
     */
    private $description;

    /**
     * The collection of properties
     *
     * @var CodeRage\Util\Properties
     */
    private $properties;

    /**
     * The associative array of configuration variables, if any
     *
     * @var array
     */
    private $configProperties;

    /**
     * @var CodeRage\Util\NativeDataEncoder
     */
    private $nativeDataEncoder;

    /**
     * @var CodeRage\Util\XmlEncoder
     */
    private $xmlEncoder;

    /**
     * @var CodeRage\Test\Operations\DataMatcher
     */
    private $dataMatcher;

    /**
     * @var CodeRage\Test\Operations\Terminator
     */
    private $terminator;

    /**
     * The schedule
     *
     * @var CodeRage\Test\Operations\Schedule
     */
    private $schedule;

    /**
     * The operation name
     *
     * @var string
     */
    private $name;

    /**
     * The object whose method is invoked, if any, represented as input to
     * CodeRage\Util\Factorr::create()
     *
     * @var CodeRage\Test\Operations\Instance
     */
    private $instance;

    /**
     * The array of arguments, if any, represented as native data structures,
     * i.e., values composed from scalars using indexed arrays and instances of
     * stdClass
     *
     * @var array
     */
    private $input;

    /**
     * The return value, if any, represented as a native data structure,
     * i.e., a value composed from scalars using indexed arrays and instances of
     * stdClass
     *
     * @var mixed
     */
    private $output;

    /**
     * The exception, if any, that the operation throws
     *
     * @var CodeRage\Test\Operations\Exception
     */
    private $exception;

    /**
     * The containing operation list, if any
     *
     * @var CodeRage\Test\Operations\OperationList
     */
    private $parent;

    /**
     * The position of this operation within its parent's list of operations,
     * if any
     *
     * @var int
     */
    private $index;

    /**
     * the path to the XML description of this operation
     *
     * @var string
     */
    private $path;

    /**
     * true if the XML operation definition from which this operation was
     * constructed contained a 'nativeDataEncoding' element
     *
     * @var
     */
    private $hasNativeDataEncoding = true;

    /**
     * true if the XML operation definition from which this operation was
     * constructed contained a 'xmlEncoding' element
     *
     * @var
     */
    private $hasXmlEncodingEncoding = true;

    /**
     * true if the XML operation definition from which this operation was
     * constructed contained a 'dataMatching' element
     *
     * @var
     */
    private $hasDataMatching = true;
}
