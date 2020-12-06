<?php

/**
 * Defines the class CodeRage\WebService\Search.
 *
 * File:        CodeRage/WebService/Search.php
 * Date:        Sun Mar 20 13:25:04 MDT 2011
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\WebService;

use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;
use CodeRage\Util\Factory;


/**
 * Utility for building and executing queries with customizable filters,
 * sorting criteria, and bounds
 */
final class Search {

    /**
     * @var string
     */
    const MATCH_FIELD = '/^[_a-zA-Z][_a-zA-Z0-9]*(\.[_a-zA-Z][_a-zA-Z0-9]*)*$/';

    /**
     * @var string
     */
    const MATCH_FILTER = '/^([._a-zA-Z0-9]+),([a-z]+)(?:,(.*))?$/';

    /**
     * @var string
     */
    const MATCH_SORT_SPEC = '/^([-+]?)(.+)/';

    /**
     * @var string
     */
    const MATCH_TYPE_NAME =
        '/^([_a-zA-Z][_a-zA-Z0-9]*)(?:\[\s*([-.:_a-zA-Z0-9]+(\s*,\s*[-.:_a-zA-Z0-9]+)*)\s*\])?$/';

    /**
     * @var string
     */
    const MATCH_OPERATION_NAME = '/^[_a-zA-Z][_a-zA-Z0-9]*$/';

    /**
     * @var string
     */
    const MATCH_INT = '/^-?(0|[1-9][0-9]*)$/';

    /**
     * Constructs an instance of CodeRage\WebService\Search
     *
     * @param array $options The options array; supports the following options:
     *     fields - An associative array mapping field names to data types
     *       represented as strings or instances of
     *       CodeRage\WebService\Search\Type
     *     query - A SELECT query consisting of a SELECT FROM construct and a
     *       sequence of JOINS; its list of columns aliases must match the
     *       collection of field names
     *     queryParams - A list of parameter values for the query specified
     *       as the "query" option (optional)
     *     outputFields - The list of field names present in each row of the
     *       result set
     *     distinct - true if the search result should consist of unique rows
     *       (optional)
     *     transform - A callable to be applied to each row in the result set
     *       (optional)
     *     maxRows - The maximum number of search results to return
     *     db - A database connection (optional)
     */
    public function __construct(array $options)
    {
        Args::checkKey($options, 'fields', 'map', [
            'required' => true
        ]);
        foreach ($options['fields'] as $n => $t) {
            if (!preg_match(self::MATCH_FIELD, $n))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            'Invalid field name; expected dot-separated ' .
                            'identifier; found ' . Error::formatValue($n)
                    ]);
            Args::check($t, 'string|CodeRage\WebService\Search\Type', 'type');
            if (is_string($t))
                $options['fields'][$n] = self::loadType($t);
        }
        Args::checkKey($options, 'query', 'string', [
            'required' => true
        ]);
        $query = self::parseQuery($options['query']);
        Args::checkKey($options, 'queryParams', 'list[scalar]', [
            'default' => []
        ]);
        Args::checkKey($options, 'outputFields', 'list[string]');
        if (isset($options['outputFields'])) {
            foreach ($options['outputFields'] as $n)
                if (!isset($options['fields'][$n]))
                    throw new
                        Error([
                            'status' => 'OBJECT_DOES_NOT_EXIST',
                            'details' => "No such field: $n"
                        ]);
        } else {
            $options['outputFields'] = array_keys($options['fields']);
        }
        Args::checkKey($options, 'distinct', 'boolean', [
            'default' => false
        ]);
        Args::checkKey($options, 'transform', 'callable', [
            'default' => null
        ]);
        Args::checkKey($options, 'maxRows', 'int', [
            'required' => true
        ]);
        if ($options['maxRows'] <= 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Invalid 'maxRows': expected positive integer; found " .
                        $options['maxRows']
                ]);
        Args::checkKey($options, 'db', 'CodeRage\Db', [
            'default' => null
        ]);
        $this->fields =
            self::constructFields($options['fields'], $query['fields']);
        $this->queryBody = $query['body'];
        $this->queryParams = $options['queryParams'];
        $this->outputFields = $options['outputFields'];
        $this->distinct = $options['distinct'];
        $this->transform = $options['transform'];
        $this->maxRows = $options['maxRows'];
        $this->db = $options['db'];
    }

    /**
     * Executes this search with the given parameters and returns the results
     *
     * @param array $options The options array; supports the following options:
     *     from - The 1-based offset of the first row of results to be returned,
     *       within the collection of all results (optional)
     *     to - The 1-based offset of the last row of results to be returned,
     *       within the collection of all results (optional)
     *     filters - An array of strings of the form "field,op,value", where
     *       "field" is a field name, "op" is one of "eq", "ne", "gt", "ge",
     *       "lt", "le", "like", "notlike", "match" or "notmatch", or of the
     *       form "field,op", where "op" is "exists" or "notexists"
     *     sort - A comma-separated list of items of the form field, +field, or
     *       -field, or an associative array mapping field names to elements of
     *       the set {+, -1} (optional)
     *     profileOnly - true to return hook data only (optional)
     *     preQuery - A callable with the signature
     *       preQuery(CodeRage\Db\Hook $hook, string $sql) (optional)
     *     postQuery - A callable with the signature
     *       postQuery(CodeRage\Db\Hook $hook, string $sql) (optional)
     *
     *   The options "from" and "to" must be specified together
     * @param array $options
     * @return array An associative array with the given keys:
     *     items - The search results, as a list of associative arrays, or
     *       as a list of return values of the option "transform", if supplied
     *     offset - The 1-based offset of the first row of results to be returned,
     *       within the collection of all results
     *     total - The size of the collection of all results
     *     hookData - The array of information collected by the preQuery and
     *       postQuery hooks
     */
    public function execute(array $options)
    {
        $this->processOptions($options);

        // Execute queries
        list($totalQuery, $itemsQuery, $params) =
            $this->constructQueries($options);
        $db = $this->db();
        $hook = null;
        if (isset($options['preQuery']) || isset($options['postQuery']))
            $hook = $db->registerHook($options);
        $total = $result = null;
        try {
            $total = $db->fetchValue($totalQuery, $params);
            $result = $db->query($itemsQuery, $params);
        } finally {
            if ($hook !== null)
                $db->unregisterHook($hook);
        }

        // Construct item list
        $transform = $this->transform;
        $items = [];
        while ($row = $result->fetchRow()) {
            $values = [];
            foreach ($this->outputFields as $i => $f) {
                $v = $row[$i];
                $values[$f] = $v !== null ?
                    $this->fields[$f]->type()->toExternal($v) :
                    null;
            }
            $items[] = $transform !== null ?
                $transform($values) :
                $values;
        }

        // Construct return value
        $result = !empty($items) ?
            [
                'items' => $items,
                'offset' => $options['from'],
                'total' => $total
            ] :
            ['total' => 0];
        if ($hook !== null)
            $result['hookData'] = $hook->data();
        if ($options['profileOnly'])
            foreach (['items', 'offset', 'total'] as $opt)
                unset($result[$opt]);
        return $result;
    }

    /**
     * Validates and processes options for execute()
     *
     * @param array $options The options array passed to execute()
     */
    private function processOptions(array &$options)
    {
        // Process bounds
        if (isset($options['from']) != isset($options['to']))
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options 'from' and 'to' must be specified " .
                        "together"
                ]);
        $this->processIntOption($options, 'from', [
            'default' => 1
        ]);
        if ($options['from'] <= 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid lower bound: expected positive ' .
                        'integer; found ' . $options['from']
                ]);
        $this->processIntOption($options, 'to', [
            'default' => $options['from'] + $this->maxRows - 1
        ]);
        if ($options['to'] <= 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Invalid upper bound: expected positive ' .
                        'integer; found ' . $options['to']
                ]);
        if ($options['from'] > $options['to'])
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        'The lower bound is greater than the upper bound'
                ]);
        if ($options['to'] - $options['from'] + 1 > $this->maxRows)
            $options['to'] = $options['from'] + $this->maxRows - 1;

        // Process filters
        Args::checkKey($options, 'filters', 'list[string]', [
            'default' => []
        ]);
        foreach ($options['filters'] as $i => $filter)
            $options['filters'][$i] = $this->processFilter($filter);

        // Process sort
        Args::checkKey($options, 'sort', 'string');
        if (isset($options['sort'])) {
            $options['sort'] = preg_split('/\s*,\s*/', trim($options['sort']));
            foreach ($options['sort'] as $i => $spec)
                $options['sort'][$i] = $this->processSortSpec($spec);
        } else {
            $options['sort'] = [];
        }

        // Process profiling options
        $profileOnly =
            Args::checkKey($options, 'profileOnly', 'boolean', [
                'default' => false
            ]);
        $preQuery = Args::checkKey($options, 'preQuery', 'callable');
        $postQuery = Args::checkKey($options, 'postQuery', 'callable');
        if ($profileOnly && $preQuery === null && $postQuery === null)
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing preQuery or postQuery hooks'
                ]);
    }

    /**
     * Validates and processes the given filter
     *
     * @param string $filter A string of the form "field,op,value"
     * @return array An associative array with keys "field", "op", and "value"
     */
    private function processFilter($filter)
    {
        $m = \CodeRage\Text\Regex::match(self::MATCH_FILTER, $filter);
        if ($m === null)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'Malformed filter: ' . Error::formatValue($filter)
                ]);
        list($all, $field, $op) = $m;
        $value = isset($m[3]) ? $m[3] : null;
        if (!preg_match(self::MATCH_FIELD, $field))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid filter '$filter': field '$field' is not a " .
                        "dot-separated identifier"
                ]);
        if (!isset($this->fields[$field]))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid filter '$filter': unsupported field '$field'"
                ]);
        $op = self::loadOperation($op);
        if (($op->flags() & Search\Operation::FLAG_UNARY) != 0) {
            if ($value !== null)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid filter '$filter': the operation '" .
                            $op->name() . "' is unary"
                    ]);
        } else {
            if ($value === null)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid filter '$filter': missing value"
                    ]);
        }
        $type = $this->fields[$field]->type();
        if (($type->flags() & $op->flags() & Search\Operation::FLAG_TYPE) == 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid filter '$filter': the field '$field' does " .
                        "not support the operation '" . $op->name() . "'"
                ]);
        $textual =
            ($op->flags() & Search\Operation::FLAG_TEXTUAL) != 0 ||
            ($type->flags() && Search\Operation::FLAG_TEXTUAL) != 0;
        if ( $value !== null && $textual &&
             !mb_check_encoding($value, 'ascii') )
        {
            $filter = Text::stripAccents($filter);
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid filter '$filter': filter values must be ASCII"
                ]);
        }
        return ['field' => $field, 'op' => $op, 'value' => $value];
    }

    /**
     * Validates and processes the given sort specification
     *
     * @param string $spec A string of the form field, +field, or -field
     * @return array An associative array with keys "field" and "direction"
     */
    private function processSortSpec($spec)
    {
        $m = \CodeRage\Text\Regex::match(self::MATCH_SORT_SPEC, $spec, [1, 2]);
        if ($m === null)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        'Malformed sort specification: ' . Error::formatValue($spec)
                ]);
        list($dir, $field) = $m;
        if ($dir == '')
            $dir = '+';
        if (!preg_match(self::MATCH_FIELD, $field))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid sort specification '$spec': field name must " .
                        "be a dot-separated identifier"
                ]);
        if (!isset($this->fields[$field]))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "Invalid sort specification '$spec': unsupported " .
                        "field '$field'"
                ]);
        $flags = $this->fields[$field]->type()->flags();
        if (($flags & Search\Type::FLAG_UNSORTABLE) !== 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' =>
                        "The field '$field' may not appear in a sort " .
                        "specification"
                ]);
        return ['field' => $field, 'direction' => $dir];
    }

    /**
     * Transforms a wildcard expression using the placeholders '*' and '?' into
     * a string suitable for use with the SQL LIKE operator and the escape
     * character "\"
     *
     * @param string $value The wildcard expression
     * @return string The transformed value
     */
    private static function processWildcards($value)
    {
        $result = '';
        $esc = false;
        for ($i = 0, $n = strlen($value); $i < $n; ++$i) {
            $c = $value[$i];
            if ($c == '\\') {
                if ($esc) {
                    $result .= '\\\\';
                    $esc = false;
                } else {
                    $esc = true;
                }
            } else {
                if ($esc) {
                    if ($c != '*' && $c != '?')
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Unsupported escape sequence \\$c in " .
                                    "wildcard expression '$value'"
                            ]);
                    $result .= $c;
                    $esc = false;
                } else {
                    if ($c == '*') {
                        $result .= '%';
                    } elseif ($c == '?') {
                        $result .= '_';
                    } elseif ($c == '%' || $c == '_') {
                        $result .= "\\$c";
                    } else {
                        $result .= $c;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Constructs the queries used to compute the size of the collection of
     * search results, and to list the rows within the specified bounds
     *
     * @param array $options The options array passed to execute(), after
     *   processing
     * @return array A triple [$total, $items, $params] consisting of two SQL
     *   queries that the list of query parameter values
     */
    private function constructQueries(array $options)
    {
        $query = $this->queryBody;
        $params = $this->queryParams;
        if (!empty($options['filters'])) {
            $conditions = [];
            foreach ($options['filters'] as $f) {
                $field = $this->fields[$f['field']];
                list($cond, $prms) =
                    $f['op']->translate($field, $f['value'], $this->db());
                $conditions[] = $cond;
                foreach ($prms as $p)
                    $params[] = $p;
            }
            $query .= ' WHERE ' . join(' AND ', $conditions);
        }
        if (!empty($options['sort'])) {
            $query .=
                ' ORDER BY ' .
                Array_::map(function ($spec)
                {
                    $field = $this->fields[$spec['field']];
                    return $field->definition() .
                           ( $spec['direction'] == '-' ?
                                 ' DESC' :
                                 '' );
                }, $options['sort'], ', ');
        }
        $fields =
            Array_::map(function($f) use($options)
            {
                $field = $this->fields[$f];
                return $field->definition() . ' {' .
                       $field->type()->specifier() . '}';
            }, $this->outputFields, ',');
        $total = $this->distinct ?
            "SELECT COUNT(DISTINCT $fields) {i} $query" :
            "SELECT COUNT(*) {i} $query";
        $items =
            "SELECT " .  ($this->distinct ? 'DISTINCT ' : '')  . "$fields $query
             LIMIT " . ($options['from'] - 1) . ",
                   " . ($options['to'] - $options['from'] + 1);
        return [$total, $items, $params];
    }

    /**
     * Processes the named option, which is expected to be an int or the string
     * representation of an int, and converts it to an int if it a string
     *
     * @param array $options The options array
     * @param The option name $name
     * @param array $params The parameters; supports the same parameters as
     *   CodeRage\Util\processOption
     */
    private function processIntOption(&$options, $name, array $params = [])
    {
        Args::checkKey($options, $name, 'int|string', $params);
        if (!isset($options[$name]))
            return;
        $value = $options[$name];
        if (is_string($value)) {
            if (!preg_match(self::MATCH_INT, $value)) {
                $label = isset($params['label']) ? $params['label'] : $name;
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid $label",
                        'details' =>
                            "Invalid $label: expected integral value; found " .
                            "'$value'"
                    ]);
            }
            $options[$name] = (int)$value;
        }
    }

    /**
     * Returns the database connection
     *
     * @return CodeRage\Db
     */
    private function db()
    {
        if ($this->db === null)
            $this->db = new \CodeRage\Db;
        return $this->db;
    }

    /**
     * Returns an instance of CodeRage\WebService\Search\Type representing the
     * named operation
     *
     * @param string $name The operation name
     * @return CodeRage\WebService\Search\Operation
     */
    private static function loadOperation($name)
    {
        static $ops = [];
        if (!isset($ops[$name])) {
            $match = null;
            if (!preg_match(self::MATCH_OPERATION_NAME, $name, $match))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Invalid operation name: $name"
                    ]);
            $title = ucfirst($name);
            $class = "CodeRage\\WebService\\Search\\Operation\\{$title}_";
            if (!Factory::classExists($class))
                throw new
                    Error([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'message' => "No such operation: $name",
                        'details' =>
                            "The class '$class' implementing operation " .
                            "'$name' does not exist"
                    ]);
            $refl = new \ReflectionClass($class);
            if (!$refl->implementsInterface('CodeRage\WebService\Search\Operation'))
                throw new
                    Error([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'message' => "No such operation: $name",
                        'details' =>
                            "The class '$class' does not implement an operation"
                    ]);
            $op = new $class;
            $count = 0;
            foreach (
                [ Search\Operation::FLAG_DISTINGUISHED,
                  Search\Operation::FLAG_ORDERED,
                  Search\Operation::FLAG_TEXTUAL ]
                as $f)
            {
                if (($op->flags() & $f) != 0)
                    ++$count;
            }
            if ($count != 1)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETERS',
                        'details' =>
                            "Invalid operation '$name': flags must include " .
                            "exactly one of the constants FLAG_DISTINGUISHED," .
                            " FLAG_ORDERED, and FLAG_TEXTUAL"
                    ]);
            $ops[$name] = new $op;
        }
        return $ops[$name];
    }

    /**
     * Returns an instance of CodeRage\WebService\Search\Type representing the
     * named type
     *
     * @param string $name An identifier or an expression of the form
     *   "type[V1,V2,V3,...]
     * @return CodeRage\WebService\Search\Type
     */
    private static function loadType($name)
    {
        static $types = [];
        if (!isset($types[$name])) {
            $match = null;
            if (!preg_match(self::MATCH_TYPE_NAME, $name, $match))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Invalid type name: $name"
                    ]);
            $title = ucfirst($match[1]);
            $class = "CodeRage\\WebService\\Search\\Type\\{$title}_";
            if (!Factory::classExists($class))
                throw new
                    Error([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'message' => "No such type: $name",
                        'details' =>
                            "The class '$class' implementing type '$name' " .
                            "does not exist"
                    ]);
            $refl = new \ReflectionClass($class);
            if (!$refl->implementsInterface('CodeRage\WebService\Search\Type'))
                throw new
                    Error([
                        'status' => 'OBJECT_DOES_NOT_EXIST',
                        'message' => "No such type: $name",
                        'details' =>
                            "The class '$class' does not implement a type"
                    ]);
            $type = null;
            if (isset($match[2])) {
                $params = preg_split('/\s*,\s*/', trim($match[2]));
                if (!method_exists($class, 'fromParameterList'))
                    throw new
                        Error([
                            'status' => 'OBJECT_DOES_NOT_EXIST',
                            'details' =>
                                "The type '$name' does not support parameters"
                        ]);
                $type = $class::fromParameterList($params);
            } else {
                $type = new $class;
            }
            $types[$name] = $type;
        }
        return $types[$name];
    }

    /**
     * Parses the given SQL query and returns an associative array containing
     * the portion of thg query after the initial column list and an associative
     * array mapping field names to field definitions
     *
     * @param string $query A SELECT query consisting of a SELECT FROM construct
     *   and a sequence of JOINS
     */
    private static function parseQuery($query)
    {
        $match = null;
        if (!preg_match('/\s*SELECT\s+(.*?)\s+(FROM\s+.*)/is', $query, $match))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Failed parsing query'
                ]);
        $columns = $match[1];
        $body = $match[2];
        $ident = '[_a-z][a-z0-9]*';
        $compound = "$ident(\s*\.\s*$ident)*";
        $q1 = self::dbQuotePairs('Q1');
        $q2 = self::dbQuotePairs('Q2');
        $q3 = self::dbQuotePairs('Q3');
        $item =
            "/ (?J) (
                 (?P<DEF>(?:{$q1[0]}$ident{$q1[1]}\s*\.\s*)?{$q2[0]}(?P<FIELD>$ident){$q2[1]}) |
                 (?P<DEF>.*?)\s+AS\s+{$q3[0]}(?P<FIELD>$compound){$q3[1]}
                ) /isx";
        $sep = '/\s*,\s*/isx';
        $matches = self::matchListItems($item, $sep, $columns);
        $fields = [];
        foreach ($matches as $m) {
            if (isset($fields[$m['FIELD']]))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "Invalid query: the field '{$m['FIELD']}' occurs " .
                            "more than once"
                    ]);
            $fields[$m['FIELD']] = $m['DEF'];
        }
        return ['body' => $body, 'fields' => $fields];
    }

    /**
     * Helper for parseQuery()
     *
     * @param string $item A regular expression matching a list item
     * @param string $sep A regular expression matching a list separator
     * @param string The list to parse $list
     * @return array A list of regular expression match arrays of the type
     *   used by preg_match()
     */
    private static function matchListItems($item, $sep, $list)
    {
        // Process arguments
        Args::check($item, 'regex', 'list item pattern');
        Args::check($sep, 'regex', 'list separator pattern');
        Args::check($list, 'string', 'list');
        if ($item[0] !== $sep[0])
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        'List item and separator patterns must use the same ' .
                        'delimeter'
                ]);
        $delim = $item[0];
        if ( substr($item, strrpos($item, $delim) + 1) !==
                 substr($sep, strrpos($sep, $delim) + 1) )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        'List item and separator patterns must use the same ' .
                        'flags'
                ]);
        }
        $flags = substr($item, strrpos($item, $delim) + 1);
        $iBody = substr($item, strlen($delim), strlen($item) - 2 - strlen($flags));
        $sBody = substr($sep, strlen($delim), strlen($sep) - 2 - strlen($flags));

        // Process items, last to first
        $matchList = "$delim^(?J)(?:$iBody)(?P<__tail>(?:$sBody)(?P<__item>(?:$iBody)))*$$delim$flags";
        $matchItem = "$delim^(?J)$iBody$$delim$flags";
        $result = [];
        while (true) {
            $match = null;
            if (!preg_match($matchList, $list, $match)) {
                $count = count($result);
                throw new
                    Error([
                        'status' => 'INCONSISTENT_PARAMETERS',
                        'details' =>
                            "List does not match pattern ($count item(s) " .
                            "successfully processed)"
                    ]);
            }
            if (isset($match['__tail'])) {
                $tail = $match['__tail'];
                $i = $match['__item'];
                $match = null;
                if (!preg_match($matchItem, $i, $match))
                    throw new
                        Error([
                            'status' => 'INTERNAL_ERROR',
                            'details' =>
                                "List item '$i' does not match list item pattern"
                        ]);
                array_unshift($result, $match);
                $list = substr($list, 0, strlen($list) - strlen($tail));
            } else {
                $match = null;
                if (!preg_match($matchItem, $list, $match)) {
                    $count = count($result);
                    throw new
                        Error([
                            'status' => 'INCONSISTENT_PARAMETERS',
                            'details' =>
                                "List does not match pattern ($count item(s) " .
                                "successfully processed)"
                        ]);
                }
                array_unshift($result, $match);
                break;
            }
        }

        return $result;
    }

    /**
     * Returns a pair of regular expressions, without delimiters, suitable for
     *   matching the opening an closing quotation marks for quoted identifiers
     *   in various database systems
     * @param string $pre A unique prefix
     * @return array
     */
    static function dbQuotePairs($pre)
    {
        $quote = '[`"]';
        $open = "(?:(?P<{$pre}1>$quote)?|(?<{$pre}2>\[)?)";
        $close = "(?({$pre}1)(?P={$pre}1)|(?({$pre}2)\]|))";
        return [$open, $close];
    }

    /**
     * Returns an associate array of field objects
     *
     * @param array $types An associative array mapping field names to data
     *   types represented as strings or instances of
     *   CodeRage\WebService\Search\Type
     * @param array $definitions An associative array mapping field names to
     *   SQL expressions
     * @return array An associative array mapping field names to instances of
     *   of CodeRage\WebService\Search\Field
     */
    private static function constructFields(array $types, array $definitions)
    {
        foreach (array_keys($types) as $n)
            if (!isset($definitions[$n]))
                throw new
                    Error([
                        'status' => 'INCONSISTENT_PARAMETERS',
                        'details' => "Missing definition for field '$n'"
                    ]);
        foreach (array_keys($definitions) as $n)
            if (!isset($types[$n]))
                throw new
                    Error([
                        'status' => 'INCONSISTENT_PARAMETERS',
                        'details' => "Missing type for field '$n'"
                    ]);
        $fields = [];
        foreach ($types as $n => $t)
            $fields[$n] = new Search\Field($t, $definitions[$n]);
        return $fields;
    }

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $queryBody;

    /**
     * @var string
     */
    private $queryParams;

    /**
     * @var array
     */
    private $outputFields;

    /**
     * @var bool
     */
    private $distinct;

    /**
     * @var string
     */
    private $transform;

    /**
     * @var array
     */
    private $maxRows;

    /**
     * The database connection; access using the method db()
     *
     * @var CodeRage\Db
     */
    private $db;
}
